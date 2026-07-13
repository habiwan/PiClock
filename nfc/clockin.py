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

importimport RPi.GPIO as GPIO
import vcgencmd
import mysql.connector
from datetime import datetime
from time import sleep
from pn532 import *

# --- DATABASE CONFIGURATION ---
db_config = {
    'host': 'db',          # Or your Pi's IP/localhost
    'user': 'root',
    'password': 'rootpassword',
    'database': 'lampapp'
}

def log_to_db(uid_hex, timestamp):
    """Inserts the swipe event directly into MySQL."""
    try:
        # Convert list of hex to a clean string, e.g., "0xAA 0xBB 0xCC"
        uid_str = " ".join([hex(i) for i in uid_hex])
        
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        sql = "INSERT INTO times (UID, timestamp) VALUES (%s, %s)"
        cursor.execute(sql, (uid_str, timestamp))
        
        conn.commit()
        cursor.close()
        conn.close()
        print(f"Successfully logged {uid_str} to database.")
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
        sleep(0.1)
        GPIO.output(buzzer, GPIO.LOW)
        
        print('Waiting for RFID/NFC card...')
        
        while True:
            uid = pn532.read_passive_target(timeout=0.5)
            if uid is None:
                continue

            # Log to DB
            current_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            log_to_db(uid, current_time)

            # Buzzer feedback
            GPIO.output(buzzer, GPIO.HIGH)
            sleep(0.1)
            GPIO.output(buzzer, GPIO.LOW)
            
            # Debounce
            sleep(1.7)

    except Exception as e:
        print(f"System error: {e}")
    finally:
        GPIO.cleanup()
