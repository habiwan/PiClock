#!/bin/bash
# do sudo crontab -e to add it to nightly checks and trims of old data, e.g:
# 0 2 * * * /bin/bash /home/javier/nfc/clean_old_logs.sh >/dev/null 2>&1

TIMES_FILE="/home/javier/nfc/times.csv"

# 1. Get the date 6 months ago (for testing)
LOG_CUTOFF=$(date -d "6 months ago" +%Y-%m-%d)

# 2. Filter using universally compatible literal matching
awk -v cutoff="$LOG_CUTOFF" '
{
    if (match($0, /[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]/)) {
        date_part = substr($0, RSTART, RLENGTH)
        if (date_part < cutoff) {
            next
        }
    }
    print $0
}' "$TIMES_FILE" > "${TIMES_FILE}.tmp"

# 3. Safely replace the file
mv "${TIMES_FILE}.tmp" "$TIMES_FILE"

# ADD THIS LINE: Force the sync script to grab the new file inode
systemctl restart nfc-sync.service
