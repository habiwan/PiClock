#!/bin/bash

# Paths to the files we are watching
TIMES_FILE="/home/javier/nfc/times.csv"
NAMES_FILE="/home/javier/nfc/names.csv"

echo "Watching $TIMES_FILE and $NAMES_FILE for changes..."

# Loop indefinitely
while inotifywait -e modify "$TIMES_FILE" "$NAMES_FILE"; do
    echo "Change detected! Copying files..."
    
    # copy commands
    cp /home/javier/nfc/*.csv /media/NAS/PiClock/nfc/
    cp /home/javier/nfc/*.csv /media/uvm/
    
    echo "Sync complete."
done
