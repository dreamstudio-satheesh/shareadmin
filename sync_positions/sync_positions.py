import os
import mysql.connector
import requests
import redis
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
    'database': os.getenv('DB_DATABASE', 'db_admin'),
}

REDIS_HOST = os.getenv('REDIS_HOST', 'localhost')
REDIS_PORT = int(os.getenv('REDIS_PORT', 6379))

r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, decode_responses=True)
db = mysql.connector.connect(**DB_CONFIG)
cursor = db.cursor(dictionary=True)

logging.basicConfig(level=logging.INFO, format='%(asctime)s | %(levelname)s | %(message)s')

def is_market_open():
    now = datetime.now(TIMEZONE)
    # Mon-Fri, 9:00am to 3:45pm
    return now.weekday() < 5 and '09:00' <= now.strftime('%H:%M') <= '15:45'

def log_cron(status, message):
    cursor.execute(
        "INSERT INTO cron_logs (task, status, message, created_at) VALUES (%s, %s, %s, NOW())",
        ('sync_positions', status, message)
    )
    db.commit()

def get_ltp(symbol):
    key = f"tick:NSE:{symbol}"
    tick = r.hgetall(key)
    return float(tick['lp']) if 'lp' in tick else None

def sync_positions():
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
            # No cron log here, log only on market state change

        except Exception as e:
            err_msg = f"Sync failed for account {account['id']}: {e}"
            logging.error(err_msg)
            log_cron('error', err_msg)

if __name__ == '__main__':
    last_market_state = None
    while True:
        try:
            market_open = is_market_open()
            if market_open:
                if last_market_state != 'open':
                    log_cron('market_open', 'Market opened. Starting position sync.')
                    last_market_state = 'open'
                sync_positions()
            else:
                if last_market_state != 'closed':
                    log_cron('market_closed', 'Market closed. Position sync paused.')
                    last_market_state = 'closed'
            # Only logs on state change
        except Exception as e:
            err = traceback.format_exc()
            logging.error(err)
            log_cron('error', f"Fatal exception: {e}")
        time.sleep(60)


# This code syncs positions from Zerodha to a MySQL database, updating the last traded price and P&L.
# It runs periodically, checking if the market is open and logging events to a cron log table.  
# It uses Redis for caching live tick data to avoid excessive API calls.
# It handles errors gracefully and logs significant events, including market state changes.
# It also ensures that positions with zero quantity are not stored in the database.
# It uses environment variables for configuration, making it flexible for different environments.
# It is designed to run continuously, checking the market state every minute and syncing positions accordingly.
# This code is a standalone script that can be run as a cron job or in a long-running process.
