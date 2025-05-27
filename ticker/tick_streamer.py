import os
import io
import csv
import json
import time
import redis
import requests
import random
from datetime import datetime

# --- Configuration ---
REDIS_HOST = os.getenv("REDIS_HOST", "redis")
REDIS_PORT = int(os.getenv("REDIS_PORT", 6379))
REDIS_WATCHLIST_KEY = "watchlist:symbols"
CACHE_DIR = "instrument_cache"

DEFAULT_WATCHLIST_SYMBOLS = [
    "NSE:RELIANCE",
    "NSE:SBIN",
    "NSE:HDFCBANK"
]
SIMULATION_INTERVAL_SECONDS = 1
PRICE_VOLATILITY = 0.005
INITIAL_PRICE_FALLBACK = 100.00

# --- Redis Connection ---
def connect_redis():
    try:
        r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, decode_responses=True)
        r.ping()
        print(f"✅ Connected to Redis at {REDIS_HOST}:{REDIS_PORT}")
        return r
    except redis.exceptions.ConnectionError as e:
        print(f"❌ Redis connection failed: {e}")
        return None

# --- Watchlist from Redis ---
def get_watchlist_symbols_from_redis(redis_client):
    try:
        watchlist = redis_client.smembers(REDIS_WATCHLIST_KEY)
        if watchlist:
            print(f"📋 Watchlist loaded from Redis: {list(watchlist)}")
            return list(watchlist)
        print(f"⚠️ Redis watchlist empty — using fallback.")
        return DEFAULT_WATCHLIST_SYMBOLS
    except Exception as e:
        print(f"❌ Error reading Redis watchlist: {e}")
        return DEFAULT_WATCHLIST_SYMBOLS

# --- Instrument Fetching and Caching ---
def get_instruments_with_caching():
    if not os.path.exists(CACHE_DIR):
        os.makedirs(CACHE_DIR)

    today_str = datetime.now().strftime('%Y-%m-%d')
    filename = os.path.join(CACHE_DIR, f"instruments_{today_str}.csv")

    if os.path.exists(filename):
        print(f"✅ Using cached instrument file: {filename}")
        with open(filename, 'r', newline='', encoding='utf-8') as f:
            return list(csv.DictReader(f))

    print("📡 Fetching instrument dump from Kite API...")
    try:
        response = requests.get("https://api.kite.trade/instruments", timeout=15)
        response.raise_for_status()
        with open(filename, 'w', newline='', encoding='utf-8') as f:
            f.write(response.text)
        print(f"✅ Saved Kite instruments to: {filename}")
        return list(csv.DictReader(io.StringIO(response.text)))
    except Exception as e:
        print(f"⚠️ API fetch failed: {e}")
        print("🛠️ Using fallback instrument data.")

        fallback_data = [
            {"exchange": "NSE", "tradingsymbol": "RELIANCE", "instrument_token": "123456", "last_price": "2500.00", "name": "Reliance"},
            {"exchange": "NSE", "tradingsymbol": "SBIN", "instrument_token": "123457", "last_price": "540.00", "name": "SBI"},
            {"exchange": "NSE", "tradingsymbol": "HDFCBANK", "instrument_token": "123458", "last_price": "1500.00", "name": "HDFC Bank"},
        ]

        with open(filename, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=["exchange", "tradingsymbol", "instrument_token", "last_price", "name"])
            writer.writeheader()
            writer.writerows(fallback_data)

        print(f"✅ Fallback instrument file created: {filename}")
        return fallback_data

# --- Price Simulation ---
def simulate_price_update(current_price):
    if current_price <= 0:
        current_price = INITIAL_PRICE_FALLBACK
    change_factor = 1 + random.uniform(-PRICE_VOLATILITY, PRICE_VOLATILITY)
    return round(max(0.01, current_price * change_factor), 2)

def store_tick(redis_client, token, ltp):
    key = f"tick:{token}"
    payload = {
        "token": token,
        "ltp": ltp,
        "time": datetime.now().isoformat()
    }
    # Store current price in Redis (for monitoring or API use)
    redis_client.set(key, json.dumps(payload))
    # Publish to 'ticks' channel (for Laravel broadcasting)
    redis_client.publish('ticks', json.dumps(payload))


# --- Main Simulator ---
def run_simulator():
    print("\n--- 🧪 Starting Tick Streamer Simulator ---")
    redis_client = connect_redis()

    watchlist = get_watchlist_symbols_from_redis(redis_client)
    instruments = get_instruments_with_caching()

    token_map = {}
    active_tokens = []

    # Prepare token mapping
    for inst in instruments:
        symbol = f"{inst['exchange'].upper()}:{inst['tradingsymbol'].upper()}"
        if symbol in watchlist:
            token = int(inst['instrument_token'])
            ltp = float(inst.get('last_price') or INITIAL_PRICE_FALLBACK)
            token_map[token] = {
                "symbol": symbol,
                "current_price": ltp,
                "name": inst.get("name", inst["tradingsymbol"])
            }
            active_tokens.append(token)
            print(f"🎯 Tracking {symbol} (Token: {token}, LTP: {ltp})")

    if not active_tokens:
        print("❌ No valid tokens found for simulation. Exiting.")
        return

    print("\n▶️ Streaming ticks every", SIMULATION_INTERVAL_SECONDS, "second(s)\n")
    try:
        while True:
            for token in active_tokens:
                current = token_map[token]["current_price"]
                new_price = simulate_price_update(current)
                token_map[token]["current_price"] = new_price
                store_tick(redis_client, token, new_price)
                print(f"Tick: {token_map[token]['symbol']} → ₹{new_price:.2f}")
            time.sleep(SIMULATION_INTERVAL_SECONDS)
    except KeyboardInterrupt:
        print("\n⛔ Simulator stopped by user.")
    finally:
        print("--- 🛑 Tick Streamer Finished ---")

if __name__ == "__main__":
    run_simulator()
