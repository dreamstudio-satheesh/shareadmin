import requests
import csv

url = "https://api.kite.trade/instruments"
response = requests.get(url)
reader = csv.DictReader(response.text.splitlines())

equities = []

for row in reader:
    if row["instrument_type"] == "EQ" and row["segment"] in ["NSE", "BSE"]:
        equities.append({
            "instrument_token": row["instrument_token"],
            "tradingsymbol": row["tradingsymbol"],
            "name": row["name"],
            "exchange": row["exchange"]
        })

# Write to CSV
with open("all_equities.csv", "w", newline="") as f:
    writer = csv.DictWriter(f, fieldnames=["instrument_token", "tradingsymbol", "name", "exchange"])
    writer.writeheader()
    writer.writerows(equities)

print(f"Saved {len(equities)} equity instruments to all_equities.csv")
