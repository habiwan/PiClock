Even though I improved it yet a bit more with passwords for piclock and different password for the card editing bit

as well as adding barcodes support for the unique NFC cards labels ... there is still more to do:

Stretch Goals:

# 1. Hardening with fail2ban (can be done in NPM plus)
If I don't do this a brute force attack would be possible until the password are found? overkill? maybe... but without private data like names and working shifts could be gotten so DO NOT USE THIS EXPOSED TO THE INTERNET !! you have been warned....

# 2. Env. variables on docker compose for the passwords
Environment variables are the gold standard for passing secrets into Docker containers because they keep passwords completely out of the source code.

Here is how the conceptual flow works:

_Step A: The docker-compose.yml file_

Inside my compose file, I define the variables under my web service. I can hardcode them there, or better yet, have Docker pull them from a hidden .env file on my host machine (ubuntuvm).

e.g.: update the YAML part of the lamp apache with something like:
```
  version: '3.8'
   services:
    web:
     image: php:8.2-apache
      ports:
       - "80:80"
      volumes:
       - ./html:/var/www/html 
   environment:
    - STAFF_PASSWORD=weakpassword
    - MANAGEMENT_PASSWORD=stongpassword
```
Still more room for improvement as I like to add a "secret folder" for the csv data and move the .ssh away from there as well that staff names mgt. page uses...

_Step B: How PHP reads it_

Inside my index.php or staff_names.php, I could completely remove the hardcoded password string and use PHP’s built-in getenv() function:

PHP:
```
// Old way: define('STAFF_PASSWORD', 'securepassword');
// New way: define('STAFF_PASSWORD', getenv('STAFF_PASSWORD') ?: 'fallback_if_empty');
```
Now, if someone grabs my PHP files, they only see getenv('STAFF_PASSWORD'). The actual passwords live strictly in the container's memory.

# 3. The www-data User & The "Web Root" Myth

I was thinking that www-data can only access the html folder. This is a very common misconception!

Inside the container, www-data is just a standard Linux user. It can access any folder inside the container as long as the file permissions allow it.

The restriction isn't what PHP can see; it's what the outside world can see.

Apache is configured to look at /var/www/html as the Document Root.

Anything inside /var/www/html is publicly accessible via a web browser (e.g., http://piclock.local/names.csv).

Anything outside of it (like /var/www/secrets/) is completely invisible to the internet, but PHP can still read and write to it perfectly.

# 4. Thoughts on CSV Security & Docker Volumes

If names.csv and times.csv are sitting in the local public web root, my login gates are essentially useless because anyone who guesses the URL can just download the files directly.... (no biggie as they are constantly overwritten)....

To fix this, I don't necessarily need a whole new Docker volume, I just need to utilize the space above the web root.

The Secure Directory Strategy

Inside my Docker container inside my UbuntuVM inside my Proxmox PC, instead of keeping files in /var/www/html/, I could store them in /var/www/secure_data/.

In my Docker Compose: I map a folder from the Raspberry Pi to a private folder inside the container:

YAML:
```
volumes:
 - ./html:/var/www/html # Public web files
 - ./nfc_data:/var/www/secure_data # Private CSV files
```

In my PHP Code: I could change my file path to point outside the web root:

```
$remote_file = "/var/www/secure_data/names.csv"; // Immune to web browsers!
```

By doing this, even if a user bypasses my login screens, Apache will throw a 404 Error if they try to type example.com/secure_data/names.csv because Apache doesn't even know that folder exists. But my PHP code can access it all day long!

If I got enough time, I shall improve it... I have to remember to adjust the volume mounts, shell scripts that run as services as well as the php code!

UPDATE: I have modified the python that reads the card data and the php to use mysql database now, so some of the caveats above are no more like cross-copying files etc...
