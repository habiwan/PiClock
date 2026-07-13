<?php
// import-all.php
$dbHost = 'db';
$dbName = 'lampapp';
$dbUser = 'root';
$dbPass = 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 1. Reset Tables (Drop old names table to enforce the new schema)
    $pdo->exec("DROP TABLE IF EXISTS names");
    
    // Create new names table with an auto-increment ID to maintain physical order
    $pdo->exec("CREATE TABLE names (
        id INT AUTO_INCREMENT PRIMARY KEY,
        CardID VARCHAR(50),
        Name VARCHAR(100)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS times (
        UID VARCHAR(12),
        temp FLOAT,
        timestamp DATETIME
    )");

    echo "Tables reset and ready.<br>";

    // 2. Import names.csv
    if (($handle = fopen('names.csv', 'r')) !== false) {
        fgetcsv($handle); // Skip header row
        // We do NOT specify 'id' here; MySQL handles the auto-increment sequentially
        $stmt = $pdo->prepare("INSERT INTO names (CardID, Name) VALUES (?, ?)");
        
        while (($data = fgetcsv($handle)) !== false) {
            // Skip the LOGOUT row
            if ($data[0] === 'n/a') continue; 
            
            // Execute in order
            $stmt->execute([$data[0], $data[1]]);
        }
        fclose($handle);
        echo "Names imported with physical order maintained.<br>";
    }

    // 3. Import times.csv
    if (($handle = fopen('times.csv', 'r')) !== false) {
        $stmt = $pdo->prepare("INSERT INTO times (UID, temp, timestamp) VALUES (?, ?, ?)");
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/\[\s*\'UID:\s*\'\s*,\s*\[(.*?)\]\s*,\s*([\d\.]+)\s*,\s*\'(.*?)\'\s*\]/', $line, $matches)) {
                $hexList = $matches[1];
                $temp = (float)$matches[2];
                $rawTimestamp = $matches[3];
                
                $uid = strtolower(str_replace(["0x", " ", "'", ","], "", $hexList));
                $cleanTimestamp = explode('.', $rawTimestamp)[0];

                $stmt->execute([$uid, $temp, $cleanTimestamp]);
            }
        }
        fclose($handle);
        echo "Times imported successfully.<br>";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
