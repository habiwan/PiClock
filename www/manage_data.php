<?php
$db = new PDO("mysql:host=db;dbname=lampapp", "root", "rootpassword");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ensure tables exist
$db->exec("CREATE TABLE IF NOT EXISTS names (id INT AUTO_INCREMENT PRIMARY KEY, CardID VARCHAR(12) NOT NULL UNIQUE, name VARCHAR(100) DEFAULT NULL)");
$db->exec("CREATE TABLE IF NOT EXISTS times (UID VARCHAR(12) NOT NULL, temp FLOAT, timestamp DATETIME)");

// --- BACKUP FUNCTION ---
if (isset($_POST['backup'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    // Example: Exporting names
    $res = $db->query("SELECT * FROM names");
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) { fputcsv($out, $row); }
    fclose($out);
    exit;
}

// --- IMPORT FUNCTION ---
if (isset($_POST['import']) && isset($_FILES['csv_file'])) {
    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) >= 2) {
            // Using INSERT IGNORE to prevent duplicate errors
            $stmt = $db->prepare("INSERT IGNORE INTO names (CardID, name) VALUES (?, ?)");
            $stmt->execute([$data[0], $data[1]]);
        }
    }
    fclose($handle);
    echo "<p>Import completed successfully.</p>";
}
?>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit" name="import">Load & Merge CSV</button>
    <button type="submit" name="backup">Backup Current DB to CSV</button>
</form>
