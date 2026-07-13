<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

$dbHost = 'db'; 
$dbName = 'lampapp';
$dbUser = 'root';
$dbPass = 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->prepare("SELECT UPDATE_TIME FROM information_schema.tables WHERE table_schema = ? AND table_name IN ('times', 'names')");
    $stmt->execute([$dbName]);
    $updateTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $namesCount = $pdo->query("SELECT COUNT(*) FROM names")->fetchColumn();
    $timesCount = $pdo->query("SELECT COUNT(*) FROM times")->fetchColumn();

    $fingerprint = implode('|', $updateTimes) . '|' . $namesCount . '|' . $timesCount;
    echo md5($fingerprint);
    
} catch (PDOException $e) {
    echo "0";
}
?><?php
// Since the CSVs are in the same folder as this script inside Docker
$times_file = __DIR__ . "/times.csv"; 
$names_file = __DIR__ . "/names.csv";

$times_mod = file_exists($times_file) ? filemtime($times_file) : 0;
$names_mod = file_exists($names_file) ? filemtime($names_file) : 0;

// Combine both timestamps into a single unique hash
echo md5($times_mod . $names_mod);
?>
