import os
import json
import redis
import mysql.connector
from datetime import datetime
from kiteconnect import KiteTicker
import threading
import time
import logging
import pytz

# --- Logging Setup ---
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# --- ENV Configuration ---
API_KEY = os.getenv("KITE_API_KEY")
ACCESS_TOKEN = os.getenv("KITE_ACCESS_TOKEN")
REDIS_HOST = os.getenv("REDIS_HOST", "localhost")
REDIS_PORT = int(os.getenv("REDIS_PORT", 6379))
REDIS_WATCHLIST_KEY = "watchlist:symbols"

MYSQL_CONFIG = {
    'host': os.getenv("DB_HOST", "localhost"),
    'user': os.getenv("DB_USERNAME", "root"),
    'password': os.getenv("DB_PASSWORD", ""),
    'database': os.getenv("DB_DATABASE", "zerodha"),
}

# --- Global State ---
token_to_symbol_map = {}
symbol_to_token_map = {}
subscribed_tokens = set()

# --- Check Market Hours (India) ---
def is_market_open():
    now = datetime.now(pytz.timezone("Asia/Kolkata"))
    return now.weekday() < 5 and "09:15" <= now.strftime('%H:%M') <= "15:30"

# --- Load Instruments from MySQL ---
def load_instruments():
    try:
        conn = mysql.connector.connect(**MYSQL_CONFIG)
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT instrument_token, exchange, tradingsymbol FROM instruments")
        instruments = cursor.fetchall()
        cursor.close()
        conn.close()

        logging.info(f"✔ Loaded {len(instruments)} instruments from DB")

        return {
            int(inst['instrument_token']): f"{inst['exchange']}:{inst['tradingsymbol']}"
            for inst in instruments
        }
    except mysql.connector.Error as e:
        logging.error(f"MySQL error: {e}")
        return {}

# --- Redis Watchlist Sync Thread ---
def sync_watchlist():
    global subscribed_tokens
    while True:
        try:
            if not is_market_open():
                logging.info("⏸ Market is closed — skipping sync_watchlist cycle")
                time.sleep(30)
                continue

            current_watchlist = {s.decode('utf-8') for s in r.smembers(REDIS_WATCHLIST_KEY)}
            logging.info(f"[Watchlist] Current: {current_watchlist}")

            target_tokens = {
                symbol_to_token_map[symbol] for symbol in current_watchlist if symbol in symbol_to_token_map
            }

            if target_tokens != subscribed_tokens:
                to_add = list(target_tokens - subscribed_tokens)
                to_remove = list(subscribed_tokens - target_tokens)

                if to_add:
                    kws.subscribe(to_add)
                    kws.set_mode(kws.MODE_FULL, to_add)
                    logging.info(f"[Subscribe] + {to_add}")

                if to_remove:
                    kws.unsubscribe(to_remove)
                    logging.info(f"[Unsubscribe] - {to_remove}")

                subscribed_tokens = target_tokens

            time.sleep(3)
        except Exception as e:
            logging.error(f"[Sync Error] {e}")
            time.sleep(5)

# --- Tick Handler ---
def on_ticks(ws, ticks):
    logging.info(f"[Ticks] Received {len(ticks)} ticks")
    try:
        pipe = r.pipeline()
        for tick in ticks:
            token = tick['instrument_token']
            symbol = token_to_symbol_map.get(token, "UNKNOWN")
            key = f"tick:{token}"

            tick_data = {
                'lp': str(tick.get('last_price', 0.0)),
                'ts': str(int(tick.get('exchange_timestamp', datetime.now()).timestamp())),
                'symbol': symbol,
                'market_open': str(is_market_open())
            }

            if r.type(key) != b'hash':
                r.delete(key)

            pipe.hset(key, mapping=tick_data)
            pipe.expire(key, 172800)  # 2-day expiry
            pipe.publish("ticks", json.dumps({
                'token': token,
                **tick_data
            }))

            logging.info(f"[Tick] {symbol} => {tick_data}")
        pipe.execute()
    except Exception as e:
        logging.error(f"[Tick Error] {e}")

# --- WebSocket Events ---
def on_connect(ws, response):
    logging.info("✔ WebSocket connected")

    if not is_market_open():
        logging.warning("⏸ Market is closed — skipping subscription")
        return

    try:
        current_watchlist = {s.decode('utf-8') for s in r.smembers(REDIS_WATCHLIST_KEY)}
        initial_tokens = {
            symbol_to_token_map[s] for s in current_watchlist if s in symbol_to_token_map
        }

        if initial_tokens:
            ws.subscribe(list(initial_tokens))
            ws.set_mode(ws.MODE_FULL, list(initial_tokens))
            global subscribed_tokens
            subscribed_tokens = initial_tokens
            logging.info(f"[Init Sub] {list(initial_tokens)}")
    except Exception as e:
        logging.error(f"[Init Sub Error] {e}")

def on_close(ws, code, reason):
    logging.warning(f"✖ WebSocket closed: Code={code}, Reason={reason}")

# --- Main Entry ---
if __name__ == "__main__":
    r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT)

    token_to_symbol_map = load_instruments()
    symbol_to_token_map = {v: k for k, v in token_to_symbol_map.items()}

    for test_symbol in ["NSE:TCS", "NSE:HDFCBANK", "NSE:IDEA"]:
        if test_symbol in symbol_to_token_map:
            logging.info(f"✔ Found in instrument map: {test_symbol}")
        else:
            logging.warning(f"⚠ Not found in instrument map: {test_symbol}")

    if not token_to_symbol_map:
        logging.critical("❌ Failed to load instruments. Exiting.")
        exit(1)

    logging.info(f"📅 Market open: {is_market_open()}")

    kws = KiteTicker(API_KEY, ACCESS_TOKEN)
    kws.on_ticks = on_ticks
    kws.on_connect = on_connect
    kws.on_close = on_close

    threading.Thread(target=sync_watchlist, daemon=True).start()
    kws.connect(threaded=True)

    while True:
        time.sleep(1)
        