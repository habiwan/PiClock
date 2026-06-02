Use this folder as an example of what should be in your working "NFC" python folder on your pi.
This was the folder that was generated after all the compliling of the NFC manufacturer software.
(broadly explained in the main README.md...)
This folder, in my example also has a few shell scripts that I run via services...
in my example I mounted a "NAS" and a "www" folder on the pi's /etc/fstab so the scripts therein can copy
names.csv and times.csv on those locations with simple cp commands...
You could say that IF you want to implement your own version and want to do it my way, this a PREREQUISITE then:

mount on the pi's /etc/fstab 2 targets:
 - //192.168.X.X/NASFOLDER /media/NAS cifs credentials=/root/.NASCREDS,uid=1000,gid=1000,noauto,x-systemd.automount,x-systemd.mount-timeout=30 0 0
 - YOURUVMUSER@192.168.X.X:/home/YOURUVMUSER/docker/docker-compose-lamp/www /media/uvm fuse.sshfs noauto,x-systemd.automount,_netdev,allow_other,IdentityFile=/root/.ssh/id_rsa,StrictHostKeyChecking=accept-new,reconnect,ServerAliveInterval=15,ServerAliveCountMax=3 0 0

For the SMB CIFS I added the NASCREDS file with username and password in the RPi /root/ folder (root runs these scripts in services! that's why... room for improvement though)

For the SSHFS UVM www folder mount on the pi, first apt install sshfs then you got to exchange keys and have them in ../html/www/.ssh (I know is not optimal, I am working on hardening see TODO.md)

I also wanted to keep my samba mount for local dev on the php code an the UMV machine but not as guest so I had to run smbpasswd command on the UVM (UbuntuVM).

If you also want to keep the /etc/samba/smb.conf on the UVM you still want to keep samba with password secured:

   [www]
   
    path = /home/YOURUVMUSER/docker/docker-compose-lamp/www
    browseable = yes
    writable = yes
    read only = no
    public = no
    guest ok = no
    valid users = YOURUVMUSER
    force user = YOURUVMUSER
    create mask = 0664
    directory mask = 0775

- mount it manually once on the pi, it will ask for password then it should be using the ssh keys... this should definitely be improved for production.

The scripts on the pi run as a service by root, so they can copy names.csv and times.csv to the "NAS" and "UVM"

In my example I used a "NAS" wannabe... horrible consumer grade WD MyCloud for backups and where the Excel got it's data from
and "UVM" which was a decomissioned now "upclycled" consumer-grade PC that I installed pve on... 

If you want to use this like me, the rationale was to have the pi as lean as possible only processing the nfc card "scans" and copying the data over to
the "webapp - UbuntuVM - PVE machine" and to the "NAS" for backups / disaster recovery... The processing goes to Excel clients or php / LAMP on the "PC"... 
