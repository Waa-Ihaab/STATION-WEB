import serial
import mysql.connector
from mysql.connector import Error

# =========================
# CONFIGURATION
# =========================
SERIAL_PORT = 'COM4'
BAUD_RATE = 9600

DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASSWORD = ''
DB_NAME = 'sensor'
TABLE_NAME = 'sensor_data'

# =========================
# CONNEXION MYSQL
# =========================
try:
    conn = mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME
    )
    if conn.is_connected():
        print("[OK] Connecté à MySQL")
        cursor = conn.cursor()
except Error as e:
    print("[ERREUR] Connexion MySQL :", e)
    exit()

# =========================
# CONNEXION PORT SÉRIE
# =========================
try:
    ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)
    print(f"[OK] Connecté au port série {SERIAL_PORT}")
except Exception as e:
    print("[ERREUR] Port série :", e)
    exit()

print("--> En attente des données tactiques...\n")

# =========================
# BOUCLE PRINCIPALE
# =========================
while True:
    try:
        line = ser.readline().decode('utf-8', errors='ignore').strip()
        
        if not line:
            continue

        print(f"[RECU] {line}")   # debug

        parts = line.split(',')
        if len(parts) != 4:
            print(f"[IGNORÉ] Format invalide : {line}")
            continue

        angle = int(parts[0])
        distance = float(parts[1])
        temperature = float(parts[2])
        buzzer = int(parts[3])

        sql = f"INSERT INTO {TABLE_NAME} (angle, temperature, distance, buzzer) VALUES (%s, %s, %s, %s)"
        values = (angle, temperature, distance, buzzer)

        cursor.execute(sql, values)
        conn.commit()

        print(f"OK -> Angle: {angle}° | Temp: {temperature}°C | Dist: {distance}cm | Alerte: {buzzer}")

    except KeyboardInterrupt:
        print("\n[!] Arrêt du système.")
        break
    except Exception as e:
        print(f"[ERREUR] : {e}")

ser.close()
cursor.close()
conn.close()