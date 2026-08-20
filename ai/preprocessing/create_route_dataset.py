import h5py
import pandas as pd
import numpy as np
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
H5_FILE = BASE_DIR / "dataset" / "traffic.h5"
OUTPUT_CSV = BASE_DIR / "traffic_routes.csv"

origin = {
    "name": "Presentation_Place",
    "lat": 21.939561499970416,
    "lon": 96.10572903158405
}

destinations = [
    "Airport", "Hospital", "University", "School", "Market",
    "Railway_Station", "Bus_Terminal", "Shopping_Mall", "Pagoda",
    "Hotel", "Bank", "Police_Station", "Fire_Station", "Park", "Stadium"
]

routes = ["Route1", "Route2", "Route3"]

route_distances = {
    "Route1": 8.5,
    "Route2": 10.2,
    "Route3": 12.0
}

with h5py.File(H5_FILE, "r") as f:
    values = f["df"]["block0_values"][:]
    timestamps = f["df"]["axis1"][:]

timestamps = pd.to_datetime(timestamps, unit="ns")

rows = []
sensor_index = 0

for destination in destinations:
    for route in routes:
        sensor_speed_data = values[:, sensor_index]
        sensor_index += 1

        distance_km = route_distances[route]

        for i, speed_mph in enumerate(sensor_speed_data):
            if np.isnan(speed_mph) or speed_mph <= 0:
                continue

            time = timestamps[i]
            speed_kmh = speed_mph * 1.60934
            travel_time_min = (distance_km / speed_kmh) * 60

            if travel_time_min <= 20:
                traffic_level = "Light"
            elif travel_time_min <= 40:
                traffic_level = "Moderate"
            else:
                traffic_level = "Heavy"

            rows.append({
                "Date": time.strftime("%Y-%m-%d"),
                "Hour": time.hour,
                "DayOfWeek": time.isoweekday(),
                "Origin": origin["name"],
                "Origin_Lat": origin["lat"],
                "Origin_Lon": origin["lon"],
                "Destination": destination,
                "Route": route,
                "Distance_km": round(distance_km, 2),
                "Speed_kmh": round(speed_kmh, 2),
                "Travel_Time_Min": round(travel_time_min, 2),
                "Traffic_Level": traffic_level,
                "Best_Route": "No"
            })

df = pd.DataFrame(rows)

for (date, hour, destination), group in df.groupby(["Date", "Hour", "Destination"]):
    best_index = group["Travel_Time_Min"].idxmin()
    df.loc[best_index, "Best_Route"] = "Yes"

df.to_csv(OUTPUT_CSV, index=False)

print("traffic_routes.csv created successfully")
print("Total rows:", len(df))
print("Saved at:", OUTPUT_CSV)