<?php
// register_nfc.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    $raw_uid = $data['uid'] ?? null;
    $name = $data['name'] ?? null;

    if (!$raw_uid || !$name) { 
        die(json_encode(['status' => 'error', 'message' => 'Missing data'])); 
    }

    // Normalize UID (Matching your existing logic)
    $clean_uid = "";
    foreach (explode(':', $raw_uid) as $part) { $clean_uid .= dechex(hexdec($part)); }
    
    try {
        $db = new PDO("mysql:host=db;dbname=lampapp", "root", "rootpassword");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 1. Check if it already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM names WHERE CardID = ?");
        $stmt->execute([$clean_uid]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Card already registered']);
            exit;
        }

        // 2. Insert the new card
        $stmt = $db->prepare("INSERT INTO names (CardID, name) VALUES (?, ?)");
        $stmt->execute([$clean_uid, $name]);
        
        echo json_encode(['status' => 'success', 'name' => $name]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
    }
}
?>
