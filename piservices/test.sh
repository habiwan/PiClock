#!/bin/bash

# Configuration
CONTAINER_NAME="lamp-db"
DB_NAME="lampapp"
DB_USER="root"
DB_PASS="rootpassword"

# --- Debounce Logic ---
DEBOUNCE_FILE="/tmp/last_swipe_trigger"
DEBOUNCE_SECONDS=60
CURRENT_EPOCH=$(date +%s)

# Check if the debounce file exists
if [[ -f "$DEBOUNCE_FILE" ]]; then
    LAST_EPOCH=$(cat "$DEBOUNCE_FILE")
    TIME_DIFF=$((CURRENT_EPOCH - LAST_EPOCH))

    # If less than 60 seconds have passed, exit silently without logging
    if [[ "$TIME_DIFF" -lt "$DEBOUNCE_SECONDS" ]]; then
        exit 0
    fi
fi

# Update the debounce file with the new trigger time
echo "$CURRENT_EPOCH" > "$DEBOUNCE_FILE"
# ----------------------

# Get the current time from the Pi host (Europe/London)
CURRENT_TIME=$(date '+%Y-%m-%d %H:%M:%S')

# SQL Logic
QUERY="
CREATE TABLE IF NOT EXISTS names (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CardID VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS times (
    UID VARCHAR(255) NOT NULL,
    temp FLOAT,
    timestamp DATETIME
);

/* Insert the test into names ONLY if it doesn't already exist */
INSERT INTO names (CardID, name)
SELECT 'test001', 'zz_manual_test' FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM names WHERE CardID = 'test001'
);

/* Always log the time entry */
INSERT INTO times (UID, temp, timestamp) VALUES ('test001', 56.0, '$CURRENT_TIME');
"

# Execute inside the container
docker exec "$CONTAINER_NAME" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$QUERY"
