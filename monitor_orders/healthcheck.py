import os, redis, mysql.connector

try:
    r = redis.Redis(host=os.getenv("REDIS_HOST", "localhost"), port=int(os.getenv("REDIS_PORT", 6379)))
    r.ping()

    db = mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_DATABASE", "zerodha")
    )
    cursor = db.cursor()
    cursor.execute("SELECT 1")
    print("OK")
except Exception as e:
    print("FAIL", e)
    exit(1)
