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
    cp /home/YOURPIUSER/nfc/*.csv /media/uvm/                # also have the CREDS files with username and password in the /root/ folder (root runs these scripts in services!) e.g.:
                                                             # //192.168.X.X/NASFOLDER /media/NAS cifs credentials=/root/.NASCREDS,uid=1000,gid=1000,noauto,x-systemd.automount,x-systemd.mount-timeout=30 0 0
                                                             # Or like I did: installed sshfs on the pi and ran on the ubuntuVM:~$ sudo smbpasswd -a YOURUVMUSER, as well as exchanged keys... so I could create it like this instead of samba: 
                                                             # YOURUVMUSER@192.168.X.X:/home/YOURUVMUSER/docker/docker-compose-lamp/www /media/uvm fuse.sshfs noauto,x-systemd.automount,_netdev,allow_other,IdentityFile=/root/.ssh/id_rsa,StrictHostKeyChecking=accept-new,reconnect,ServerAliveInterval=15,ServerAliveCountMax=3 0 0
                                                             # the /etc/samba/smb.conf on the UVM should be in case you still want to keep samba with password secured:
                                                             # [www]
                                                             #    path = /home/YOURUVMUSER/docker/docker-compose-lamp/www
                                                             #    browseable = yes
                                                             #    writable = yes
                                                             #    read only = no
                                                             #    public = no
                                                             #    guest ok = no
                                                             #    valid users = YOURUVMUSER
                                                             #    force user = YOURUVMUSER
                                                             #    create mask = 0664
                                                             #    directory mask = 0775
    echo "Sync complete."
done
