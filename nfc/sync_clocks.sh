#!/bin/bash

# Paths to the files we are watching
TIMES_FILE="/home/YOURPIUSER/nfc/times.csv"
NAMES_FILE="/home/YOURPIUSER/nfc/names.csv"

echo "Watching $TIMES_FILE and $NAMES_FILE for changes..."

# Loop indefinitely
while inotifywait -e modify "$TIMES_FILE" "$NAMES_FILE"; do
    echo "Change detected! Copying files..."
    
    # copy commands that happen after any swipe (or NFC-card name change):
    cp /home/YOURPIUSER/nfc/*.csv /media/NAS/PiClock/nfc/    # make sure you mount these 2 on your pi's /etc/fstab
    cp /home/YOURPIUSER/nfc/*.csv /media/uvm/                # see nfc/pifolder.md for more info
    
    echo "Sync complete."
done
