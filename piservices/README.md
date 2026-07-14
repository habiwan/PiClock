Use this folder as an example of what services you should install in your pi:

- Docker (install Portainer too)
- avahi-alias (optional - create a hostname alias for LAN testing)
- clockpy.service (this runs the nfc script in memory ready to be read)
- emptycards.service (used initially to write a csv file with all NFC codes in it)
- nfc-sync.service (when NFS card swiped on pi-NFC-reader it copies csv files across NAS and www machine - obsolete)

During prototyping I ran the services on this folder on the pi so they run in term the shell scripts

as you can see from the nfc folder... subject to your own opinion / implementation...

UPDATE: the latest version can be run on the same pi via docker Portainer lamp stack:

Optional services you could run on your machine that runs the webapp.

For me, this is was decomissioned "upcycled" consumer grade PC that I installed PVE on for prototyping. But now with the latest version you can simply run www and mysql on the arm64 portainer docker compose and env...

This was initially on an Ubuntu VM for me that runs docker-compose-lamp with phpmyadmin. Since phpmyadmin could not run on my pi I decided to use adminer instead.

This gives me the quick and easy way to run php, but it was "overkill" as I was planning to use mysql, and now that I do I migrated to docker on pi.

and now! everybody! no more clucky ugly unsatisfactory silly csv files... running on MySQL now!!!

The additional service that I ended up wanting on my setup was to use the avahi capabilities to give the www enpoint

a "nickname" like e.g. http://piclock.local or just simple http://piclock works for me too...

At the same time it keeps your original "uvm" or "rpi" hostname endpoint alive, up to you if you want this or not...

UPDATE: modified so it runs on a Pi itself too... if you can run docker on the pi you can install Portainer and use this new lamp stack that uses adminer instead of phpmyadmin... that was the big stopper I unlocked.
