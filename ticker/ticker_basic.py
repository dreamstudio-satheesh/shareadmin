import json
import time
from kiteconnect import KiteTicker

api_key = "fwl8jd4xcan3r27d"
access_token = "WeaP2sLqLqG29hk73vHvKZWyo02wlFols"

# Hardcoded token for RELIANCE (example)
tokens = [408065]  # Replace with your desired token(s)

kws = KiteTicker(api_key, access_token)

def on_ticks(ws, ticks):
    for tick in ticks:
        data = {
            "instrument_token": tick['instrument_token'],
            "last_price": tick.get('last_price'),
            "timestamp": tick.get('timestamp', time.time())
        }
        print(json.dumps(data))  # Output to terminal

def on_connect(ws, response):
    print("Connected. Subscribing...")
    ws.subscribe(tokens)

def on_close(ws, code, reason):
    print("Connection closed:", reason)

kws.on_ticks = on_ticks
kws.on_connect = on_connect
kws.on_close = on_close

try:
    kws.connect(threaded=True)
    while True:
        time.sleep(1)
except KeyboardInterrupt:
    print("Interrupted.")
