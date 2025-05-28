import os
import io
import csv
import json
import time
import redis
import requests
from datetime import datetime, timedelta
from kiteconnect import KiteTicker

# --- Configuration ---
API_KEY = os.getenv("KITE_API_KEY")
ACCESS_TOKEN = os.getenv("KITE_ACCESS_TOKEN")
REDIS_HOST = os.getenv("REDIS_HOST", "localhost")
REDIS_PORT = int(os.getenv("REDIS_PORT", 6379))
REDIS_WATCHLIST_KEY = "watchlist:symbols" # The Redis key Laravel will write to
CACHE_DIR = "instrument_cache"

FETCH_INTERVAL_HOURS = 24
WATCHLIST_CHECK_SECONDS = 15 # Check for watchlist changes every 15 seconds

# --- Initial Checks & Setup ---
if not API_KEY or not ACCESS_TOKEN:
    raise ValueError("Please set KITE_API_KEY and KITE_ACCESS_TOKEN environment variables.")
if not os.path.exists(CACHE_DIR):
    os.makedirs(CACHE_DIR)

# --- Global State ---
token_to_symbol_map = {}
subscribed_tokens = set()
last_fetch_time = None
# No longer a hardcoded WATCHLIST variable

# --- Functions ---

def get_instruments_with_caching():
    # ... (This function remains unchanged from the previous version) ...
    today_str = datetime.now().strftime('%Y-%m-%d')
    filename = os.path.join(CACHE_DIR, f"instruments_{today_str}.csv")
    if os.path.exists(filename):
        with open(filename, 'r', newline='') as f:
            return list(csv.DictReader(f))
    print("Fetching from Kite API...")
    try:
        response = requests.get("https://api.kite.trade/instruments", timeout=15)
        response.raise_for_status()
        with open(filename, 'w', newline='') as f:
            f.write(response.text)
        return list(csv.DictReader(io.StringIO(response.text)))
    except requests.RequestException as e:
        print(f"ERROR fetching instruments from API: {e}")
        return None

def get_watchlist_from_redis(redis_client):
    """Fetches the current watchlist from the Redis SET."""
    try:
        # SMEMBERS returns all members of the set. Returns a set of strings.
        watchlist = redis_client.smembers(REDIS_WATCHLIST_KEY)
        return list(watchlist) # Convert to list for processing
    except Exception as e:
        print(f"ERROR reading watchlist from Redis: {e}")
        return [] # Return empty list on error
HARDCODED_WATCHLIST = ["NSE:RELIANCE", "NSE:TCS", "NSE:HDFCBANK"] 
def update_subscriptions(kws_instance, redis_client):
    """
    The main update function. It checks for instrument and watchlist changes
    and updates WebSocket subscriptions accordingly.
    """
    global token_to_symbol_map, subscribed_tokens, last_fetch_time

    # --- Step 1: Handle Daily Instrument Fetch ---
    # Check if it's time for the daily instrument refresh
    if last_fetch_time is None or (datetime.now() - last_fetch_time > timedelta(hours=FETCH_INTERVAL_HOURS)):
        print("--- Running daily instrument update ---")
        all_instruments = get_instruments_with_caching()
        if all_instruments:
            new_token_map = {}
            for inst in all_instruments:
                key = f"{inst['exchange']}:{inst['tradingsymbol']}"
                new_token_map[int(inst['instrument_token'])] = key
            token_to_symbol_map = new_token_map # Update global map
            print("Instrument map updated.")
        else:
            print("Could not update instrument map, using existing one.")
        last_fetch_time = datetime.now()

    # --- Step 2: Get Latest Watchlist from Redis ---
    current_watchlist = get_watchlist_from_redis(redis_client)
    if not token_to_symbol_map:
        print("WARN: Instrument map is not loaded. Cannot process watchlist.")
        return

    # Invert map to get tokens for symbols
    symbol_to_token_map = {v: k for k, v in token_to_symbol_map.items()}
    
    # --- Step 3: Calculate and Apply Subscription Changes ---
    new_tokens_to_subscribe = {symbol_to_token_map.get(s.upper()) for s in current_watchlist if symbol_to_token_map.get(s.upper())}

    # Only proceed if there's a change
    if new_tokens_to_subscribe == subscribed_tokens:
        return # No change, do nothing

    print(f"Watchlist change detected. New count: {len(new_tokens_to_subscribe)}, Old count: {len(subscribed_tokens)}")

    if kws_instance and kws_instance.is_connected():
        tokens_to_add = list(new_tokens_to_subscribe - subscribed_tokens)
        tokens_to_remove = list(subscribed_tokens - new_tokens_to_subscribe)
        
        if tokens_to_add:
            print(f"Subscribing to: {tokens_to_add}")
            kws_instance.subscribe(tokens_to_add)
            kws_instance.set_mode(kws_instance.MODE_FULL, tokens_to_add)
        if tokens_to_remove:
            print(f"Unsubscribing from: {tokens_to_remove}")
            kws_instance.unsubscribe(tokens_to_remove)

    subscribed_tokens = new_tokens_to_subscribe
    print(f"Subscription update complete. Now subscribed to {len(subscribed_tokens)} tokens.")


# --- WebSocket Callbacks (No changes needed) ---
def on_ticks(ws, ticks):
    # ... same as before ...
    for tick in ticks:
        token = tick['instrument_token']
        symbol = token_to_symbol_map.get(token, "UNKNOWN")
        data = {"instrument_token": token, "symbol": symbol, "last_price": tick.get('last_price'), "timestamp": tick.get('exchange_timestamp', time.time())}
        r.publish('kite_ticks', json.dumps(data))

def on_connect(ws, response):
    # ... same as before ...
    print("WebSocket Connected. Running initial subscription check...")
    # The main loop will handle the first subscription
    
def on_close(ws, code, reason):
    # ... same as before ...
    print(f"WebSocket Closed: {code} - {reason}")

# --- Main Execution ---
r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, decode_responses=True)
kws = KiteTicker(API_KEY, ACCESS_TOKEN)
kws.on_ticks = on_ticks
kws.on_connect = on_connect
kws.on_close = on_close

try:
    # Run an initial update to load instruments before connecting
    update_subscriptions(kws_instance=None, redis_client=r)
    
    print("Starting WebSocket connection...")
    kws.connect(threaded=True)
    time.sleep(5) # Wait for connection to establish

    # Main loop to periodically check for watchlist changes
    while True:
        update_subscriptions(kws, r)
        time.sleep(WATCHLIST_CHECK_SECONDS)

except KeyboardInterrupt:
    print("Shutting down.")
finally:
    if kws and kws.is_connected():
        kws.close()