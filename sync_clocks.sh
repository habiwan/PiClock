#!/bin/bash

# don't forget to adjust the paths!
# in this example, I mounted the NAS and the UbuntuVM that runs the docker compose LAMP system 
# The UbuntuVM that runs the docker LAMP has it's volume mapped from /home/habiwan/docker/docker-compose-lamp/www to /var/www/html 
# e.g. - ${DOCUMENT_ROOT-./www}:/var/www/html:rw in order to be persistent and simple to maintain

# Paths to the files we are watching
TIMES_FILE="/home/habiwan/nfc/times.csv"
NAMES_FILE="/home/habiwan/nfc/names.csv"

echo "Watching $TIMES_FILE and $NAMES_FILE for changes..."

# Loop indefinitely
while inotifywait -e modify "$TIMES_FILE" "$NAMES_FILE"; do
    echo "Change detected! Copying files..."
    
    # copy commands
    cp /home/habiwan/nfc/*.csv /media/NAS/PiClock/nfc/
    cp /home/habiwan/nfc/*.csv /media/uvm/
    
    echo "Sync complete."
done
