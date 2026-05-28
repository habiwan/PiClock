# PiClock
Custom made replacement of traditional Punchcard-Systems with NFC cards and a Raspberry Pi instead.

![alt text](image.png)

Materials required:
 - Raspberry Pi2 (32bit) (when tested on Pi 4 or newer struggled as it's 64bit and too fast)
 - Pi2 PSU
 - Wifi dongle (Pi2 does not have wifi)
 - NFC HAT (e.g. PN532 NFC HAT)
 - LCD screen HAT (e.g. ELEGOO 3.5 Inch 480x320 TFT Display HAT)
 - mini speaker (buzzer)
 - 100x NFC cards (NTag215 the box they came in is perfect size for building a case cutting off the front to reveal the LCD screen and a slot on the side to "swipe" the NFC cards)

Steps I broadly took if memory serves, might need tweaking for different scenarios:
1. Install default Pi OS legacy 32 bit (bullseye)
2. sudo raspi-config to enable SSH,SPI,i2c,GPIO etc...
3. nano .ssh/authorized_keys to add your ssh pubkey for passwordless access (optional)
4. sudo apt update && sudo apt upgrade -y
5. sudo apt install python3-serial* libusb-dev libpcsclite-dev libtool automake autoconf gpiod pip samba ntp inotify-tools install setuptools -y
6. sudo pip3 install git+https://github.com/nicmcd/vcgencmd.git
7. sudo pip3 install -e .
8. wget https://files.waveshare.com/upload/8/85/Pn532test.zip
9. wget https://files.waveshare.com/upload/6/66/Pn532-nfc-hat-code.7z
10. git clone https://github.com/nfc-tools/libnfc.git
11. git clone https://github.com/nfc-tools/mfoc.git
12. autoreconf
13. ./configure --prefix=/usr --sysconfdir=/etc
14. make
15. sudo make install
16. gpioinfo (to check GPIO connections)
17. nfc-list (to check NFC HAT is working properly)
18. Edit py scripts to use I2C comms (Serial/UART and SPI did not work for me)
19. cp the example_get_uid.py into your clockin.py project file and adjust or check out my example
20. the script needs a times.csv file, it will add one line per "swipe" or "punch"

I created an Excel to prototype first then a php page on a different machine that presents the data. 
The Excel filters out the 3 columns we need (card id, day/month and time/hour:minute)
some formulas I used to strip unwanted repeated characters from the Card IDs and timestamps:

CARD =SUBSTITUTE(CONCAT(MID(B2,6,1),MID(C2,5,2),MID(D2,5,2),MID(E2,5,2),MID(F2,5,2),MID(G2,5,1),MID(H2,5,2)),"'","")

day  =CONCAT(MID(J2,11,2),"/",MID(J2,8,2))

time =MID(J2,14,5)

Name assignement: =XLOOKUP([@CARD],Info!$A$2:$A$101,Info!$B$2:$B$101,"UNREGISTERED")

Advanced Filter:

List Range =$K$1:$M$ (LastLineNr)

Criteria Rg= $K$2:$M$2

But alas you build you own... I used a Pivot table and it was nice... good excersise...
But I added the examples of the php pages I created at the end which are better as no Excel Client needed...
Everyone has a browser! and if opened on mobile, can be a neat webapp... see the php examples for similar results

You want to keep the Pi as lean as possible, I edited the PiOS desktop:
 - desktop color: black
 - have only the top panel, background black, text white
 - strip everything from the top panel except the time
 - make the top panel as big as possible to fit the LDC screen
I also created some services on it:
 - Service that constantly reads the NFC reader and write it to times.csv (adapted python script from NFC manufacturer)
 - Service that makes a copy of the times.csv and names.csv to a NAS and to another PC with a LAMP docker install
(Optional) I created a crontab that runs a trim of old data, keeps only last 6 months, can be adjusted...

These Services keep the pi lean and the logic / webpage / Excel get their data from the NAS or from the PC. This way the pi only takes care of showing the time on the LCD,
reading the cards with a confirmation beep, and copying the csv files somewhere else for processing (the NAS and webserver aka. an UbuntuVM with docker-compose-lamp on a "PVE-PC" in my case).

In my python script I added the sensor temp of the CPU for monitoring / troubeshooting reasons, but is not shown anywhere in my example. In my experience the pi with a heatsink never got too hot (under 60 celsius all the time, even on hot days).

The PC that hosts the webapp (a php page really running on mounted volume of a docker-compose-lamp installed on an UbuntuVM...) takes care of all the logic. The Excel only needs access to the NAS and also takes care of all the logic. As mentioned above, the pi uploads the names and times csv files to them when NFC cards are swiped...

Prototyped, Proyected and Created by F.Javier "habiwan" Puig Diaz (https://github.com/habiwan).

All software used is GPL licenced and hence there is no warranty for this modified free software either.
