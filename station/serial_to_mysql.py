import serial
import mysql.connector
from mysql.connector import Error

# =========================
# CONFIGURATION
# =========================
SERIAL_PORT = 'COM7'
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
        print("Connecté à MySQL")
        cursor = conn.cursor()

except Error as e:
    print("Erreur connexion MySQL :", e)
    exit()

# =========================
# CONNEXION PORT SÉRIE
# =========================
try:
    ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)
    print(f"Connecté au port série {SERIAL_PORT}")
except Exception as e:
    print("Erreur port série :", e)
    exit()

print("Lecture des données...\n")

# =========================
# BOUCLE PRINCIPALE
# =========================
while True:
    try:
        line = ser.readline().decode('utf-8').strip()

        if not line:
            continue

        print("Reçu :", line)

        parts = line.split(',')

        if len(parts) != 3:
            print("Format invalide, ligne ignorée")
            continue

        temperature = float(parts[0])
        distance = float(parts[1])
        buzzer = int(parts[2])

        sql = f"""
            INSERT INTO {TABLE_NAME} (temperature, distance, buzzer)
            VALUES (%s, %s, %s)
        """
        values = (temperature, distance, buzzer)

        cursor.execute(sql, values)
        conn.commit()

        print(f"ID:Inséré -> temp={temperature}, distance={distance}, buzzer={buzzer}")
        print(f"Dernier ID : {cursor.lastrowid}\n")

    except ValueError:
        print("Erreur conversion, ligne ignorée")
    except KeyboardInterrupt:
        print("\nArrêt du script")
        break
    except Exception as e:
        print("Erreur :", e)

# =========================
# FERMETURE
# =========================
ser.close()
cursor.close()
conn.close()
print("Connexions fermées")