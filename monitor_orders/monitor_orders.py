import os
import redis
import mysql.connector
from datetime import datetime
import logging
import pytz
import time
import traceback
import json

# --- Setup ---
TIMEZONE = pytz.timezone('Asia/Kolkata')

REDIS_HOST = os.getenv('REDIS_HOST', 'localhost')
REDIS_PORT = int(os.getenv('REDIS_PORT', 6379))
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_DATABASE', 'zerodha'),
}

r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, decode_responses=True)
db = mysql.connector.connect(**DB_CONFIG)
cursor = db.cursor(dictionary=True)

logging.basicConfig(level=logging.INFO, format='%(asctime)s | %(levelname)s | %(message)s')

# --- Load Holidays from JSON ---
HOLIDAYS_CACHE = set()

def load_holidays_from_file():
    global HOLIDAYS_CACHE
    try:
        with open('trading_holidays.json') as f:
            holidays = json.load(f)
            HOLIDAYS_CACHE = {
                datetime.strptime(h['Date'], '%d/%m/%Y').strftime('%Y-%m-%d')
                for h in holidays
            }
            logging.info(f"Loaded {len(HOLIDAYS_CACHE)} holidays from file.")
    except Exception as e:
        logging.error(f"Failed to load holidays file: {e}")
        HOLIDAYS_CACHE = set()

# --- Utils ---
def is_market_open():
    now = datetime.now(TIMEZONE)
    return now.weekday() < 5 and '09:15' <= now.strftime('%H:%M') <= '15:30'

def is_trading_holiday():
    today = datetime.now(TIMEZONE).strftime('%Y-%m-%d')
    return today in HOLIDAYS_CACHE

def round_to_tick(price, tick_size):
    return round(round(price / tick_size) * tick_size, 2)

def log_cron(status, message):
    cursor.execute("INSERT INTO cron_logs (task, status, message, created_at) VALUES (%s, %s, %s, NOW())",
                   ('monitor_orders', status, message))
    db.commit()

def log_order_event(order_id, action, message):
    cursor.execute("INSERT INTO order_logs (order_id, action, message, created_at) VALUES (%s, %s, %s, NOW())",
                   (order_id, action, message))
    db.commit()

# --- Main Logic ---
def monitor_orders():
    if not is_market_open() or is_trading_holiday():
        logging.info("Market closed or holiday. Skipping.")
        log_cron('skipped', 'Market closed or holiday')
        return

    cursor.execute("""
        SELECT po.*, z.access_token
        FROM pending_orders po
        JOIN zerodha_accounts z ON z.id = po.zerodha_account_id
        WHERE po.status = 'pending'
    """)
    orders = cursor.fetchall()

    for order in orders:
        order_id = order['id']
        symbol = f"NSE:{order['symbol']}"

        if not order['access_token']:
            reason = 'Missing access token'
            cursor.execute("UPDATE pending_orders SET status='failed', reason=%s WHERE id=%s", (reason, order_id))
            db.commit()
            log_order_event(order_id, 'fail', reason)
            continue

        tick = r.hgetall(f"tick:{symbol}")
        if not tick or 'lp' not in tick:
            log_order_event(order_id, 'skip', f"No LTP for {symbol}")
            continue

        ltp = float(tick['lp'])

        # Fetch tick size
        cursor.execute("SELECT tick_size FROM instruments WHERE tradingsymbol=%s AND exchange='NSE' LIMIT 1", (order['symbol'],))
        row = cursor.fetchone()
        tick_size = float(row['tick_size']) if row else 0.05

        ltp = round_to_tick(ltp, tick_size)
        target = round_to_tick(order['target_price'], tick_size)
        stoploss = round_to_tick(order['stoploss_price'], tick_size)

        now = datetime.now()

        if ltp >= target:
            cursor.execute("""
                UPDATE pending_orders
                SET status='executed', executed_price=%s, executed_at=%s
                WHERE id=%s
            """, (ltp, now, order_id))
            db.commit()
            log_order_event(order_id, 'executed', f"Executed at {ltp}")
            continue

        if ltp <= stoploss:
            cursor.execute("""
                UPDATE pending_orders
                SET status='cancelled', stoploss_triggered_at=%s, reason=%s
                WHERE id=%s
            """, (now, 'Stoploss hit', order_id))
            db.commit()
            log_order_event(order_id, 'cancelled', f"Cancelled due to stoploss at {ltp}")

    log_cron('success', 'Cycle complete')

# --- Loop Forever ---
if __name__ == '__main__':
    load_holidays_from_file()
    while True:
        try:
            monitor_orders()
        except Exception as e:
            err = traceback.format_exc()
            logging.error(f"Exception: {e}")
            log_cron('error', str(e))
        time.sleep(3)
