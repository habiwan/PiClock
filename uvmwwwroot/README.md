Use this folder as an example of what should be in your www root folder.

this folder in my example is a persistent volume of a docker compose LAMP setup.

I used a decomissioned but "upcycled" consumer grade PC to install PVE on it and this is a Ubuntu VM for me...

in my example I mount this folder on the pi's /etc/fstab so the scripts therein can copy

names.csv and times.csv here 

(I also pi-mounted a "NAS" wannabe... horrible consumer grade WD MyCloud for backups)

EDIT: Added barcode for the staff names managment table in case unique barcodes wanted to be scanned

UPDATE: modified so no more dirty raw csvs are used, but pure mysql from an updaated lamp that can run on pies. table structure has to be:
CREATE TABLE IF NOT EXISTS times (
        UID VARCHAR(12),
        temp FLOAT,
        timestamp DATETIME

and 

CREATE TABLE names (
        id INT AUTO_INCREMENT PRIMARY KEY,
        CardID VARCHAR(50),
        Name VARCHAR(100)
