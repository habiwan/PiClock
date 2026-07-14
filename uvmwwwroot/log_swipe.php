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
        
        // Use the PHP date in London timezone instead of MySQL NOW()
        $stmt = $db->prepare("INSERT INTO times (UID, temp, timestamp) VALUES (?, 56.0, ?)");
        $stmt->execute([$clean_uid, date('Y-m-d H:i:s')]);
        
        $stmt = $db->prepare("SELECT name FROM names WHERE CardID = ?");
        $stmt->execute([$clean_uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $name = $user ? $user['name'] : "Unknown User";

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'name' => $name, 'time' => date('H:i:s')]);

    } catch (PDOException $e) {
        file_put_contents($log_file, "DB ERROR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
    }
}
?>
