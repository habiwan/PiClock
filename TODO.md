improved it yet a bit more with passwords for piclock and different password for the card editing bit. ...but still more to do:

Stretch Goals:

1. Environment Variables in Docker-Compose
Environment variables are the gold standard for passing secrets into Docker containers because they keep passwords completely out of your source code.
Here is how the conceptual flow works:

Step A: The docker-compose.yml file
Inside my compose file, I define the variables under my web service. I can hardcode them there, or better yet, have Docker pull them from a hidden .env file on my host machine (ubuntuvm).
YAML:
version: '3.8'services:web:image: php:8.2-apacheports:- "80:80"volumes:- ./html:/var/www/htmlenvironment:- STAFF_PASSWORD=supersecurestaff123- MANAGEMENT_PASSWORD=bossmode456

Step B: How PHP reads it
Inside my index.php or staff_names.php, I could completely remove the hardcoded password string and use PHP’s built-in getenv() function:
PHP:
// Old way: define('STAFF_PASSWORD', 'securepassword');// New way:
define('STAFF_PASSWORD', getenv('STAFF_PASSWORD') ?: 'fallback_if_empty');

Now, if someone grabs my PHP files, they only see getenv('STAFF_PASSWORD'). The actual passwords live strictly in the container's memory.

2. The www-data User & The "Web Root" Myth
I was thinking that www-data can only access the html folder. This is a very common misconception!
Inside the container, www-data is just a standard Linux user. It can access any folder inside the container as long as the file permissions allow it.
The restriction isn't what PHP can see; it's what the outside world can see.
Apache is configured to look at /var/www/html as the Document Root.
Anything inside /var/www/html is publicly accessible via a web browser (e.g., http://piclock.local/names.csv).
Anything outside of it (like /var/www/secrets/) is completely invisible to the internet, but PHP can still read and write to it perfectly.
3. Thoughts on CSV Security & Docker Volumes
If names.csv and times.csv are sitting in the local public web root, my login gates are essentially useless because anyone who guesses the URL can just download the files directly.... (no biggie as they are constantly overwritten)....
To fix this, I don't necessarily need a whole new Docker volume, I just need to utilize the space above the web root.
The Secure Directory Strategy
Inside my Docker container inside my UbuntuVM inside my Proxmox PC, instead of keeping files in /var/www/html/, I could store them in /var/www/secure_data/.
In my Docker Compose: I map a folder from the Raspberry Pi to a private folder inside the container:
YAML:
volumes:- ./html:/var/www/html            # Public web files- ./nfc_data:/var/www/secure_data # Private CSV files

In my PHP Code: I could change my file path to point outside the web root:
$remote_file = "/var/www/secure_data/names.csv"; // Immune to web browsers!

By doing this, even if a user bypasses my login screens, Apache will throw a 404 Error if they try to type example.com/secure_data/names.csv because Apache doesn't even know that folder exists. But my PHP code can access it all day long!

If I got enough time, I shall improve it...
