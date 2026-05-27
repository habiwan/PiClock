"""
This example shows connecting to the PN532 with I2C (requires clock
stretching support), SPI, or UART. SPI is best, it uses the most pins but
is the most reliable and universally supported.
After initialization, try waving various 13.56MHz RFID cards over it!
Modified and enhanced by habiwan May/June 2025
This example is production ready and has been working for a year now. Ready for use with Excel that lists the date swiped and name of employee
"""

import RPi.GPIO as GPIO # used for nfc and buzzer
import vcgencmd # added for CPU temperature readings
from datetime import datetime # added for timestamps
from time import sleep # added for buzzer
from pathlib import Path # added to 'touch' the csv if it does not exist yet or gets deleted

from pn532 import *
Path('/home/javier/nfc/times.csv').touch()

if __name__ == '__main__':
    try:
        pn532 = PN532_I2C(debug=False, reset=20, req=16)

        ic, ver, rev, support = pn532.get_firmware_version()
        print('Found PN532 with firmware version: {0}.{1}'.format(ver, rev))

        # Configure PN532 to communicate with MiFare cards
        pn532.SAM_configuration()

        #Disable warnings (optional)
        GPIO.setwarnings(False)
        #Select GPIO mode
        GPIO.setmode(GPIO.BCM)
        #Set buzzer - pin 23 as output
        buzzer=23
        GPIO.setup(buzzer,GPIO.OUT)
        # debug Beep on boot
        GPIO.output(buzzer,GPIO.HIGH)
        sleep(0.01)
        GPIO.output(buzzer,GPIO.LOW)
        print('Waiting for RFID/NFC card...')
        while True:
            # Check if a card is available to read
            uid = pn532.read_passive_target(timeout=0.5)
            print('', end="")
            # Try again if no card is available.
            if uid is None:
                continue
            temp = vcgencmd.measure_temp()
            s = ["UID: ", [hex(i) for i in uid], temp, str(datetime.now())]
            with open("/home/javier/nfc/times.csv", "a") as f:
                f.writelines(str(s))
                f.write("\n")
            print(s) # debug
            GPIO.output(buzzer,GPIO.HIGH)
            #print("Beep") # debug
            sleep(0.1) # Beep delay in seconds
            GPIO.output(buzzer,GPIO.LOW)
            sleep(1.7) # debounce
            f.close()
    except Exception as e:
        print(e)
    finally:
        GPIO.cleanup()
