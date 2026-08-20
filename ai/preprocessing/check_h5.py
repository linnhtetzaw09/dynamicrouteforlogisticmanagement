import h5py
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
H5_FILE = BASE_DIR / "dataset" / "traffic.h5"

with h5py.File(H5_FILE, "r") as f:
    print("Keys in file:")
    for key in f.keys():
        print(key)

    print("\nFull structure:")
    def show(name, obj):
        print(name, type(obj), getattr(obj, "shape", ""))

    f.visititems(show)