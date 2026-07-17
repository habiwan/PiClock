<?php
date_default_timezone_set('Europe/London');
$log_file = 'nfc_debug.log';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    $raw_uid = $data['uid'] ?? null;
    
    if (!$raw_uid) { die("No UID"); }

    // Normalize UID
    $clean_uid = "";
    foreach (explode(':', $raw_uid) as $part) { $clean_uid .= dechex(hexdec($part)); }
    
    // --- SERVER-SIDE DEBOUNCE LOGIC ---
    $debounce_file = sys_get_temp_dir() . '/nfc_swipe_debounce_' . $clean_uid . '.txt';
    $debounce_seconds = 60;
    $current_time = time();

    if (file_exists($debounce_file)) {
        $last_time = (int)file_get_contents($debounce_file);
        if (($current_time - $last_time) < $debounce_seconds) {
            // Return a safe JSON response so the front-end doesn't crash if this catches a glitch
            echo json_encode(['status' => 'ignored', 'name' => 'Already Logged', 'time' => date('H:i:s')]);
            exit;
        }
    }
    file_put_contents($debounce_file, $current_time);
    // ----------------------------------
    
    try {
        $db = new PDO("mysql:host=db;dbname=lampapp", "root", "rootpassword");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // --- AUTOMATIC TABLE CREATION ---
        $db->exec("CREATE TABLE IF NOT EXISTS names (
            id INT AUTO_INCREMENT PRIMARY KEY,
            CardID VARCHAR(12) NOT NULL UNIQUE,
            name VARCHAR(100) DEFAULT NULL
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS times (
            UID VARCHAR(12) NOT NULL,
            temp FLOAT,
            timestamp DATETIME
        )");
        // --------------------------------
        
        // 1. Check if the card already exists in the names table
        $stmt = $db->prepare("SELECT name FROM names WHERE CardID = ?");
        $stmt->execute([$clean_uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Card is known, grab the existing name
            $name = $user['name'];
        } else {
            // Card is UNKNOWN. Auto-register it.
            $maxStmt = $db->query("SELECT MAX(id) FROM names");
            $max_id = $maxStmt->fetchColumn();
            // If the table is empty, start at 1, otherwise use the highest + 1
            $next_id = ($max_id !== null) ? ($max_id + 1) : 1;
            
            // Format the name to zz_UNASSIGNED-XX
            $name = sprintf("zz_UNASSIGNED-%02d", $next_id);
            
            // Insert the new card into the database
            $insertStmt = $db->prepare("INSERT INTO names (CardID, name) VALUES (?, ?)");
            $insertStmt->execute([$clean_uid, $name]);
        }

        // 2. Log the swipe in the times table
        $stmt = $db->prepare("INSERT INTO times (UID, temp, timestamp) VALUES (?, 56.0, ?)");
        $stmt->execute([$clean_uid, date('Y-m-d H:i:s')]);

        // 3. Send the result back to the front-end
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'name' => $name, 'time' => date('H:i:s')]);

    } catch (PDOException $e) {
        file_put_contents($log_file, "DB ERROR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
    }
}
?>
