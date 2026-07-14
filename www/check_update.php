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
?>
