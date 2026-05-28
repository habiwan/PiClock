Use this folder as an example of what should be in your working "NFC" python folder on your pi.
This was the folder that was generated after all the compliling of the NFC manufacturer software.
(broadly explained in the main README.md...)
This folder, in my example also has a few shell scripts that I run via services...
in my example I mounted a "NAS" and a "www" folder on the pi's /etc/fstab so the scripts therein can copy
names.csv and times.csv on those locations with simple cp commands...
You could say that IF you want to implement your own version and want to do it my way, this a PREREQUISITE then:

mount on the pi's /etc/fstab 2 targets:
 - //192.168.X.X/NASFOLDER /media/NAS cifs credentials=/root/.NASCREDS,uid=1000,gid=1000,noauto,x-systemd.automount,x-systemd.mount-timeout=30 0 0
 - //192.168.X.Y/WWWROOTFOLDER /media/uvm cifs credentials=/root/.UVMCREDS,uid=1000,gid=1000,noauto,x-systemd.automount,x-systemd.mount-timeout=30 0 0

- make sure you have in the /root/folder the CREDS files with username and password for the "NAS" and "UVM"

The scripts therein run as a service by root, so they can copy names.csv and times.csv to the "NAS" and "UVM"

In my example I used a "NAS" wannabe... horrible consumer grade WD MyCloud for backups and where the Excel got it's data from
and "UVM" which was a decomissioned now "upclycled" consumer-grade PC that I installed pve on... 

If you want to use this like me, the rationale was to have the pi as lean as possible only processing the nfc card "scans" and copying the data over to
the "webapp - UbuntuVM - PVE machine" and to the "NAS" for backups / disaster recovery... The processing goes to Excel clients or php / LAMP on the PC... 
