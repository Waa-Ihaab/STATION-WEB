import serial
import sqlite3
from datetime import datetime

SERIAL_PORT = '/dev/tty.usbmodem1201'
BAUD_RATE = 9600

conn = sqlite3.connect("project/sensor_data.db")
cursor = conn.cursor()

cursor.execute("""
               CREATE TABLE IF NOT EXISTS sensor_data (
                                                          id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                          temperature REAL,
                                                          distance REAL,
                                                          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
               )
               """)

conn.commit()

ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)

print("Start reading serial data...")

while True:
    try:
        line = ser.readline().decode('utf-8').strip()
        if not line:
            continue

        print("Received:", line)

        parts = line.split(",")
        if len(parts) != 2:
            print("Format invalide")
            continue

        temperature = float(parts[0])
        distance = float(parts[1])

        cursor.execute(
            "INSERT INTO sensor_data (temperature, distance) VALUES (?, ?)",
            (temperature, distance)
        )
        conn.commit()

        print(f"Inserted: temp={temperature}, distance={distance}")

    except KeyboardInterrupt:
        print("Stop.")
        break
    except Exception as e:
        print("Erreur:", e)