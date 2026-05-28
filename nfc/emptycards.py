"""
This example shows connecting to the PN532 with I2C (requires clock
stretching support), SPI, or UART. SPI is best, it uses the most pins but
is the most reliable and universally supported.
After initialization, try waving various 13.56MHz RFID cards over it!
Modified and enhanced by F.Javier "habiwan" Puig Diaz in May/June 2025
This optional set of script/service is to get an excel file file written with the codes of empty cards you swipe (whevever you get new cards) 
For example you could get an excel file written with the codes and matching names so a number can be written on empty cards
uses the production names.csv that I got from prescanning 100 cards and getting their unique ID on column 1 and _UNASSIGNEDXX on column 2
so that matching names to their unique ID is simple. I suggest writing with a pen a number to a label on those new empty cards...
This uses the same production names.csv file, new names like _UNASSIGNEDXX should be column 2 column 1 the unique ID stripped as shown in the get-table-data.php example:
Look for /** 2. PARSE AND MAP TIMESTAMPS (times.csv) **/ and see what $combinedData = []; does...
... or use an EXCEL with the formulae shown in the main README.md ....
GOAL is to see the results after swiping a card and writing a little number in the corner with the unassigned card number for future employees / temps...
This way the empty cards that all look the same will have the labelled number matched to their unique ID for later processing...
Remember to adjust the paths!
"""

import RPi.GPIO as GPIO # used for nfc and buzzer
import vcgencmd # added for CPU temperature readings
from datetime import datetime # added for timestamps
from time import sleep # added for buzzer
from pathlib import Path # added to 'touch' the csv if it does not exist yet or gets deleted

from pn532 import *
Path('/home/YOURPIUSER/nfc/emptycards.csv').touch()

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
            with open("/home/YOURPIUSER/nfc/emptycards.csv", "a") as f:
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
