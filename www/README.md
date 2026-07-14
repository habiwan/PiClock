Use this folder as an example of what should be in your www root folder.

this folder in my example is a persistent volume of a docker compose LAMP setup.

I used a decomissioned but "upcycled" consumer grade PC to install PVE on it and this is a Ubuntu VM for me...

in my example I mount this folder on the pi's /etc/fstab so the scripts therein can copy

names.csv and times.csv here 

(I also pi-mounted a "NAS" wannabe... horrible consumer grade WD MyCloud for backups)

EDIT: Added barcode for the staff names managment table in case unique barcodes wanted to be scanned

UPDATE: this now works on the same Pi with docker lamp Portainer stack (compose) - see uvmservices - and can also scan NFC cards with Chrome on Android via scanner.html. The two sample csv files can be imported to the mysql of the lamp with import-csv.php
