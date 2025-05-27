import time
import os
import redis
import mysql.connector
from datetime import datetime

def log(msg):
    print(f"[monitor_orders] {datetime.now()} - {msg}", flush=True)

def get_env(key, default=None):
    return os.getenv(key, default)

# Setup Redis
redis_client = redis.Redis(host=get_env("REDIS_HOST", "redis"), port=6379)

# Setup MySQL
db = mysql.connector.connect(
    host=get_env("DB_HOST", "mysql"),
    user=get_env("DB_USERNAME", "root"),
    password=get_env("DB_PASSWORD", "secret"),
    database=get_env("DB_DATABASE", "kiteadmin")
)

while True:
    try:
        # Example: read one token from Redis
        tick_data = redis_client.get("tick:123456")
        if tick_data:
            log(f"Tick found: {tick_data.decode()}")
        else:
            log("No tick data yet.")

        # Example: query orders
        cursor = db.cursor()
        cursor.execute("SELECT COUNT(*) FROM orders WHERE status = 'pending'")
        (count,) = cursor.fetchone()
        log(f"Pending orders: {count}")
        cursor.close()

    except Exception as e:
        log(f"Error: {e}")

    time.sleep(10)
