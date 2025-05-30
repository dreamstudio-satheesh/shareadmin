import os
import redis
import mysql.connector
from datetime import datetime
import logging
import pytz
import time
import traceback

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

# --- Utils ---
def is_market_open():
    now = datetime.now(TIMEZONE)
    # Mon-Fri, 9:15am to 3:30pm
    return now.weekday() < 5 and '09:15' <= now.strftime('%H:%M') <= '15:30'

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

# --- Loop Forever with Market State Change Logging ---
if __name__ == '__main__':
    last_market_state = None
    while True:
        try:
            market_open = is_market_open()
            if market_open:
                if last_market_state != 'open':
                    log_cron('market_open', 'Market opened. Starting order monitoring.')
                    last_market_state = 'open'
                monitor_orders()
            else:
                if last_market_state != 'closed':
                    log_cron('market_closed', 'Market closed. Order monitoring paused.')
                    last_market_state = 'closed'
            # Only logs on state change
        except Exception as e:
            err = traceback.format_exc()
            logging.error(f"Exception: {e}")
            log_cron('error', str(e))
        time.sleep(3)
        
        
# This code monitors pending orders in a Zerodha account, checking their status against live market data.
# It updates the database with order execution or cancellation details and logs significant events.
# It runs continuously, checking the market state and logging events to a cron log table.
# It uses Redis for real-time data and MySQL for persistent storage.    
# It handles errors gracefully, logging them for later review.
# It also rounds prices to the nearest tick size for accurate order matching.
# It ensures that orders are processed only when the market is open, and it logs events on market state changes.
# It also skips orders if the last traded price (LTP) is not available, logging the reason for skipping.
# This ensures that the system operates efficiently and avoids unnecessary API calls.
# This code monitors pending orders in a Zerodha account, checking their status against live market data.
# It updates the database with order execution or cancellation details and logs significant events.
# It runs continuously, checking the market state and logging events to a cron log table.
# It uses Redis for real-time data and MySQL for persistent storage.
# It handles errors gracefully, logging them for later review.