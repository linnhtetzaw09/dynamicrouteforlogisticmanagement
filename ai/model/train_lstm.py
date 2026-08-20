import pandas as pd
import numpy as np
import pickle
from pathlib import Path
from sklearn.preprocessing import MinMaxScaler, LabelEncoder
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense, Dropout
from tensorflow.keras.callbacks import EarlyStopping

BASE_DIR = Path(__file__).resolve().parent.parent

CSV_FILE = BASE_DIR / "traffic_routes.csv"
MODEL_FILE = BASE_DIR / "model" / "lstm_model.keras"
SCALER_FILE = BASE_DIR / "model" / "scaler.pkl"
ENCODER_FILE = BASE_DIR / "model" / "encoders.pkl"

print("Loading CSV...")
df = pd.read_csv(CSV_FILE)

# Use fewer rows first for testing
df = df.head(200000)

encoders = {}

for col in ["Destination", "Route", "Traffic_Level"]:
    encoder = LabelEncoder()
    df[col] = encoder.fit_transform(df[col])
    encoders[col] = encoder

features = [
    "Hour",
    "DayOfWeek",
    "Destination",
    "Route",
    "Distance_km",
    "Speed_kmh",
    "Traffic_Level"
]

target = "Travel_Time_Min"

data = df[features + [target]].values

scaler = MinMaxScaler()
scaled_data = scaler.fit_transform(data)

sequence_length = 12

X = []
y = []

print("Creating sequences...")

for i in range(sequence_length, len(scaled_data)):
    X.append(scaled_data[i-sequence_length:i, :-1])
    y.append(scaled_data[i, -1])

X = np.array(X)
y = np.array(y)

print("X shape:", X.shape)
print("y shape:", y.shape)

model = Sequential()
model.add(LSTM(64, return_sequences=True, input_shape=(X.shape[1], X.shape[2])))
model.add(Dropout(0.2))
model.add(LSTM(32))
model.add(Dropout(0.2))
model.add(Dense(1))

model.compile(
    optimizer="adam",
    loss="mse",
    metrics=["mae"]
)

early_stop = EarlyStopping(
    monitor="loss",
    patience=5,
    restore_best_weights=True
)

print("Training LSTM...")

model.fit(
    X,
    y,
    epochs=20,
    batch_size=64,
    callbacks=[early_stop]
)

model.save(MODEL_FILE)

with open(SCALER_FILE, "wb") as f:
    pickle.dump(scaler, f)

with open(ENCODER_FILE, "wb") as f:
    pickle.dump(encoders, f)

print("Training complete")
print("Model saved:", MODEL_FILE)