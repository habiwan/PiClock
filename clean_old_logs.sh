#!/bin/bash

# (optional) If you sudo crontab -e on the pi it will do nightly checks and trim old data, e.g:
# 0 2 * * * /bin/bash /home/habiwan/nfc/clean_old_logs.sh >/dev/null 2>&1

TIMES_FILE="/home/habiwan/nfc/times.csv"

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
