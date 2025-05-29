import os
import mysql.connector
import requests
import redis
import json
from datetime import datetime
import logging
import time
import traceback
import pytz

# --- Setup ---
TIMEZONE = pytz.timezone('Asia/Kolkata')
BASE_URL = "https://api.kite.trade"

DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_DATABASE', 'zerodha'),
}

REDIS_HOST = os.getenv('REDIS_HOST', 'localhost')
REDIS_PORT = int(os.getenv('REDIS_PORT', 6379))

r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, decode_responses=True)
db = mysql.connector.connect(**DB_CONFIG)
cursor = db.cursor(dictionary=True)

logging.basicConfig(level=logging.INFO, format='%(asctime)s | %(levelname)s | %(message)s')

# --- In-memory Holiday Cache ---
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

def is_market_open():
    now = datetime.now(TIMEZONE)
    return now.weekday() < 5 and '09:00' <= now.strftime('%H:%M') <= '15:45'

def is_trading_holiday():
    today = datetime.now(TIMEZONE).strftime('%Y-%m-%d')
    return today in HOLIDAYS_CACHE

def log_cron(status, message):
    cursor.execute("INSERT INTO cron_logs (task, status, message, created_at) VALUES (%s, %s, %s, NOW())",
                   ('sync_positions', status, message))
    db.commit()

def get_ltp(symbol):
    key = f"tick:NSE:{symbol}"
    tick = r.hgetall(key)
    return float(tick['lp']) if 'lp' in tick else None

def sync_positions():
    if not is_market_open() or is_trading_holiday():
        msg = "Market closed or holiday. Skipping position sync."
        logging.info(msg)
        log_cron('skipped', msg)
        return

    cursor.execute("SELECT id, access_token, api_key FROM zerodha_accounts WHERE access_token IS NOT NULL")
    accounts = cursor.fetchall()

    for account in accounts:
        headers = {
            'Authorization': f'token {account["api_key"]}:{account["access_token"]}'
        }

        try:
            response = requests.get(f"{BASE_URL}/portfolio/positions", headers=headers)
            if response.status_code != 200:
                raise Exception(f"API error {response.status_code}: {response.text}")

            positions = response.json().get("data", {}).get("net", [])

            # Clear old positions
            cursor.execute("DELETE FROM positions WHERE zerodha_account_id = %s", (account['id'],))

            insert_count = 0
            for pos in positions:
                quantity = float(pos['quantity'])
                avg_price = float(pos['average_price'])
                symbol = pos['tradingsymbol']

                if quantity == 0:
                    continue

                ltp = get_ltp(symbol)
                pnl = round((ltp - avg_price) * quantity, 2) if ltp else None

                cursor.execute("""
                    INSERT INTO positions (
                        zerodha_account_id, symbol, quantity, average_price, last_price, pnl, created_at, updated_at
                    ) VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
                """, (account['id'], symbol, quantity, avg_price, ltp, pnl))
                insert_count += 1

            db.commit()
            msg = f"Account {account['id']} synced {insert_count} positions"
            logging.info(msg)
            log_cron('success', msg)

        except Exception as e:
            err_msg = f"Sync failed for account {account['id']}: {e}"
            logging.error(err_msg)
            log_cron('error', err_msg)

# --- Run Every Minute ---
if __name__ == '__main__':
    load_holidays_from_file()
    while True:
        try:
            sync_positions()
        except Exception as e:
            err = traceback.format_exc()
            logging.error(err)
            log_cron('error', f"Fatal exception: {e}")
        time.sleep(60)
# --- End of sync_positions.py ---