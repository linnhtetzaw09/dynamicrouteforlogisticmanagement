from flask import Flask, request, jsonify
from flask_cors import CORS
import pandas as pd
import numpy as np
import pickle
from pathlib import Path
from tensorflow.keras.models import load_model

app = Flask(__name__)
CORS(app)

BASE_DIR = Path(__file__).resolve().parent.parent

CSV_FILE = BASE_DIR / "traffic_routes.csv"
MODEL_FILE = BASE_DIR / "model" / "lstm_model.keras"
SCALER_FILE = BASE_DIR / "model" / "scaler.pkl"
ENCODER_FILE = BASE_DIR / "model" / "encoders.pkl"

model = load_model(MODEL_FILE)

with open(SCALER_FILE, "rb") as f:
    scaler = pickle.load(f)

with open(ENCODER_FILE, "rb") as f:
    encoders = pickle.load(f)

features = [
    "Hour",
    "DayOfWeek",
    "Destination",
    "Route",
    "Distance_km",
    "Speed_kmh",
    "Traffic_Level",
    "Travel_Time_Min"
]

@app.route("/predict", methods=["POST"])
def predict():
    data = request.json
    destination = data.get("destination")

    df = pd.read_csv(CSV_FILE)
    df = df[df["Destination"] == destination]

    if df.empty:
        return jsonify({"error": "Destination not found in CSV"})

    predictions = []

    for route in ["Route1", "Route2", "Route3"]:
        route_df = df[df["Route"] == route].tail(12)

        if len(route_df) < 12:
            continue

        route_df = route_df.copy()

        for col in ["Destination", "Route", "Traffic_Level"]:
            route_df[col] = encoders[col].transform(route_df[col])

        values = route_df[features].values
        scaled = scaler.transform(values)

        X = scaled[:, :-1]
        X = np.array([X])

        predicted_scaled = model.predict(X, verbose=0)[0][0]

        dummy = np.zeros((1, len(features)))
        dummy[0, -1] = predicted_scaled

        predicted_real = scaler.inverse_transform(dummy)[0, -1]

        predictions.append({
            "route": route,
            "predicted_time_min": round(float(predicted_real), 2)
        })

    if not predictions:
        return jsonify({"error": "Not enough data for prediction"})

    best = min(predictions, key=lambda x: x["predicted_time_min"])

    return jsonify({
        "destination": destination,
        "predictions": predictions,
        "best_route": best
    })

@app.route("/", methods=["GET"])
def home():
    return "LSTM API is running"

if __name__ == "__main__":
    app.run(debug=True, port=5000)