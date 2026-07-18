"""
This example shows connecting to the PN532 with I2C (requires clock
stretching support), SPI, or UART. SPI is best, it uses the most pins but
is the most reliable and universally supported.
After initialization, try waving various 13.56MHz RFID cards over it!
Modified and enhanced by F.Javier "habiwan" Puig Diaz in May/June 2025
Remember to adjust the paths!
It writes the raw times.csv file which is then used with names.csv later during processing.
This example is production ready and has been working since June 2025. 
We were using an excel file that lists the date swiped and name of employee, 
but now I developed a php webapp with the same functionality and nicer look.
Copyleft by F.Javier Puig Diaz, July 2026
UPDATE: mysql for lamp usage (pip3 install mysql-connector-python first of course...)
"""
import RPi.GPIO as GPIO
import vcgencmd
import mysql.connector
from datetime import datetime
import time # Imported time module directly for our debounce math
from pn532 import *

# --- DATABASE CONFIGURATION ---
db_config = {
    'host': 'db',          # Or your Pi's IP/localhost
    'user': 'root',
    'password': 'rootpassword',
    'database': 'lampapp'
}

# --- DEBOUNCE CONFIGURATION ---
last_swipe_times = {}
DEBOUNCE_SECONDS = 60

def get_cpu_temp():
    """Fetches the CPU temperature using vcgencmd."""
    try:
        # Standard approach for vcgencmd on Raspberry Pi
        temp = vcgencmd.measure_temp()
        # Returns a string like "temp=56.0'C", we need to extract the float
        temp_value = float(temp.replace("temp=", "").replace("'C", ""))
        return temp_value
    except:
        return 0.0 # Fallback if reading fails

def ensure_tables_exist(cursor):
    """Creates tables if they are missing."""
    # Create 'names' table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS names (
            id INT AUTO_INCREMENT PRIMARY KEY,
            CardID VARCHAR(12) NOT NULL UNIQUE,
            name VARCHAR(100) DEFAULT NULL
        )
    """)
    # Create 'times' table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS times (
            UID VARCHAR(12) NOT NULL,
            temp FLOAT,
            timestamp DATETIME
        )
    """)

def log_to_db(uid_str, timestamp):
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        # --- NEW: Ensure structure exists before inserting ---
        ensure_tables_exist(cursor)
        
        cpu_temp = get_cpu_temp()
        
        sql = "INSERT INTO times (UID, temp, timestamp) VALUES (%s, %s, %s)"
        cursor.execute(sql, (uid_str, cpu_temp, timestamp))
        
        conn.commit()
        cursor.close()
        conn.close()
    except mysql.connector.Error as err:
        print(f"Database error: {err}")

if __name__ == '__main__':
    try:
        pn532 = PN532_I2C(debug=False, reset=20, req=16)
        ic, ver, rev, support = pn532.get_firmware_version()
        print('Found PN532 with firmware version: {0}.{1}'.format(ver, rev))
        pn532.SAM_configuration()

        GPIO.setwarnings(False)
        GPIO.setmode(GPIO.BCM)
        buzzer = 23
        GPIO.setup(buzzer, GPIO.OUT)

        # Boot beep
        GPIO.output(buzzer, GPIO.HIGH)
        time.sleep(0.1)
        GPIO.output(buzzer, GPIO.LOW)
        
        print('Waiting for RFID/NFC card...')
        
        while True:
            uid = pn532.read_passive_target(timeout=0.5)
            if uid is None:
                continue
            
            # Convert list of hex to a clean string immediately
            uid_str = " ".join([hex(i) for i in uid])
            current_epoch = time.time()

            # --- Per-Card Debounce Logic ---
            if uid_str in last_swipe_times:
                time_since_last = current_epoch - last_swipe_times[uid_str]
                if time_since_last < DEBOUNCE_SECONDS:
                    # Ignore the card if it was swiped less than 60 seconds ago
                    continue 
            
            # Record the new swipe time for this specific card
            last_swipe_times[uid_str] = current_epoch
            # -------------------------------

            # Log to DB
            current_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            log_to_db(uid_str, current_time)

            # Buzzer feedback
            GPIO.output(buzzer, GPIO.HIGH)
            time.sleep(0.1)
            GPIO.output(buzzer, GPIO.LOW)

    except Exception as e:
        print(f"System error: {e}")
    finally:
        GPIO.cleanup()
