import time
import os
import mysql.connector
from datetime import datetime

def log(msg):
    print(f"[sync_positions] {datetime.now()} - {msg}", flush=True)

def get_env(key, default=None):
    return os.getenv(key, default)

# Setup MySQL
db = mysql.connector.connect(
    host=get_env("DB_HOST", "mysql"),
    user=get_env("DB_USERNAME", "root"),
    password=get_env("DB_PASSWORD", "secret"),
    database=get_env("DB_DATABASE", "kiteadmin")
)

while True:
    try:
        # Simulate syncing
        log("Syncing Zerodha positions...")

        # Example query (you can replace with actual API sync logic)
        cursor = db.cursor()
        cursor.execute("SELECT COUNT(*) FROM positions")
        (count,) = cursor.fetchone()
        log(f"Total positions: {count}")
        cursor.close()

    except Exception as e:
        log(f"Error: {e}")

    time.sleep(5)
