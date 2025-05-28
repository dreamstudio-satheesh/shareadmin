import os
import json
import redis
import mysql.connector
from datetime import datetime
from kiteconnect import KiteTicker
import threading
import time
import logging

# --- Logging Setup ---
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# --- Configuration ---
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

# --- Load Instruments ---
def load_instruments():
    try:
        conn = mysql.connector.connect(**MYSQL_CONFIG)
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT instrument_token, exchange, tradingsymbol FROM instruments")
        instruments = cursor.fetchall()
        cursor.close()
        conn.close()
        return {
            int(inst['instrument_token']): f"{inst['exchange']}:{inst['tradingsymbol']}"
            for inst in instruments
        }
    except mysql.connector.Error as e:
        logging.error(f"MySQL error: {e}")
        return {}

# --- Redis Sync Thread ---
def sync_watchlist():
    global subscribed_tokens
    while True:
        try:
            current_watchlist = {s.decode('utf-8') for s in r.smembers(REDIS_WATCHLIST_KEY)}
            target_tokens = {
                symbol_to_token_map[s] for s in current_watchlist if s in symbol_to_token_map
            }

            if target_tokens != subscribed_tokens:
                to_add = list(target_tokens - subscribed_tokens)
                to_remove = list(subscribed_tokens - target_tokens)

                if to_add:
                    kws.subscribe(to_add)
                    kws.set_mode(kws.MODE_FULL, to_add)
                    logging.info(f"Subscribed to: {to_add}")

                if to_remove:
                    kws.unsubscribe(to_remove)
                    logging.info(f"Unsubscribed from: {to_remove}")

                subscribed_tokens = target_tokens
            time.sleep(0.5)
        except Exception as e:
            logging.error(f"Watchlist sync failed: {e}")
            time.sleep(2)

# --- Ticks Processing ---
def on_ticks(ws, ticks):
    try:
        pipe = r.pipeline()
        for tick in ticks:
            token = tick['instrument_token']
            symbol = token_to_symbol_map.get(token, "UNKNOWN")
            data = {
                'lp': str(tick.get('last_price', 0.0)),
                'ts': str(int(tick.get('exchange_timestamp', datetime.now()).timestamp()))
            }
            pipe.hset(f"tick:{token}", mapping=data)
        pipe.execute()
    except Exception as e:
        logging.error(f"Tick processing error: {e}")

# --- Lifecycle Hooks ---
def on_connect(ws, response):
    logging.info("Connected to WebSocket")
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
            logging.info(f"Initial tokens subscribed: {list(initial_tokens)}")
    except Exception as e:
        logging.error(f"Initial subscription failed: {e}")

def on_close(ws, code, reason):
    logging.warning(f"WebSocket closed: {code}, Reason: {reason}")

# --- Main ---
if __name__ == "__main__":
    r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT)
    token_to_symbol_map = load_instruments()
    symbol_to_token_map = {v: k for k, v in token_to_symbol_map.items()}

    if not token_to_symbol_map:
        logging.critical("Failed to load instruments. Exiting.")
        exit()

    kws = KiteTicker(API_KEY, ACCESS_TOKEN)
    kws.on_ticks = on_ticks
    kws.on_connect = on_connect
    kws.on_close = on_close

    threading.Thread(target=sync_watchlist, daemon=True).start()
    kws.connect(threaded=True)
