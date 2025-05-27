# healthcheck.py
import redis, mysql.connector, sys

try:
    redis.Redis(host="redis", port=6379).ping()
    mysql.connector.connect(
        host="mysql",
        user="root",
        password="secret",
        database="kiteadmin"
    ).close()
    sys.exit(0)  # healthy
except Exception:
    sys.exit(1)  # unhealthy
