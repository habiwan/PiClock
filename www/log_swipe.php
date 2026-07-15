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
    
    try {
        $db = new PDO("mysql:host=db;dbname=lampapp", "root", "rootpassword");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // --- AUTOMATIC TABLE CREATION ---
        $db->exec("CREATE TABLE IF NOT EXISTS names (
            id INT AUTO_INCREMENT PRIMARY KEY,
            CardID VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) DEFAULT NULL
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS times (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UID VARCHAR(255) NOT NULL,
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
            $countStmt = $db->query("SELECT COUNT(*) FROM names");
            $total_cards = $countStmt->fetchColumn();
            $next_id = $total_cards + 1;
            
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
