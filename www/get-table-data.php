<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

$dbHost = 'db'; 
$dbName = 'lampapp';
$dbUser = 'root';
$dbPass = 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed.");
}

/** 1. LOAD EMPLOYEES MAP **/
$employeeMap = [];
$stmt = $pdo->query("SELECT CardID, Name FROM names");
while ($row = $stmt->fetch()) {
    $cardKey = trim($row['CardID'] ?? '');
    $nameVal = trim($row['Name'] ?? '');
    if (!empty($cardKey)) {
        $employeeMap[strtolower($cardKey)] = $nameVal;
    }
}

/** 2. FETCH AND MAP TIMESTAMPS (Directly from columns) **/
$combinedData = [];
// Select the columns directly
$stmt = $pdo->query("SELECT UID, timestamp FROM times");
while ($row = $stmt->fetch()) {
    $cardStr = strtolower(trim($row['UID']));
    $timestamp = $row['timestamp'];

    if (!empty($cardStr) && !empty($timestamp)) {
        $timeObj = strtotime($timestamp);
        $isMatched = isset($employeeMap[$cardStr]);
        $employeeName = $isMatched ? $employeeMap[$cardStr] : 'Unknown Card';
        
        $combinedData[] = [
            'year' => (int)date('Y', $timeObj),
            'iso_year' => (int)date('o', $timeObj), // Added ISO year for accurate week calculation
            'week' => (int)date('W', $timeObj),
            'date_only' => date('Y-m-d', $timeObj),
            'formatted_day' => date('l - M j, Y', $timeObj), 
            'time_only' => date('H:i', $timeObj),         
            'card' => $cardStr,
            'name' => $employeeName,
            'timestamp' => $timestamp,
            'is_matched' => $isMatched
        ];
    }
}

/** 3. MULTI-COLUMN CHRONOLOGICAL SORTING **/
if (!empty($combinedData)) {
    $dateOnly = array_column($combinedData, 'date_only');
    $names = array_map('strtolower', array_column($combinedData, 'name'));
    $timestamps = array_column($combinedData, 'timestamp');
    array_multisort($dateOnly, SORT_DESC, $names, SORT_ASC, $timestamps, SORT_ASC, $combinedData);
}

/** 4. GROUP DATA **/
$groupedData = [];
foreach ($combinedData as $row) {
    // Included ISO year in the grouping key for accurate Week math
    $dayKey = $row['date_only'] . '|' . $row['formatted_day'] . '|' . $row['week'] . '|' . $row['year'] . '|' . $row['iso_year'];
    $empKey = $row['card'];
    
    if (!isset($groupedData[$dayKey])) $groupedData[$dayKey] = [];
    if (!isset($groupedData[$dayKey][$empKey])) {
        $groupedData[$dayKey][$empKey] = [
            'name' => $row['name'],
            'card' => $row['card'],
            'is_matched' => $row['is_matched'],
            'punches' => []
        ];
    }
    $groupedData[$dayKey][$empKey]['punches'][] = $row['time_only'];
}

/** 5. OUTPUT HTML **/
foreach ($groupedData as $dayKey => $employees) {
    list($dateOnly, $formattedDay, $weekNum, $yearNum, $isoYear) = explode('|', $dayKey);
    
    // Calculate exact start and end dates for the week number
    $dto = new DateTime();
    $dto->setISODate((int)$isoYear, (int)$weekNum);
    $weekStart = $dto->format('Y-m-d'); // Monday
    $dto->modify('+6 days');
    $weekEnd = $dto->format('Y-m-d'); // Sunday

    echo '<div class="day-card" data-date="' . $dateOnly . '">';
    echo '<div class="day-header">';
    echo '<div class="day-title">' . htmlspecialchars($formattedDay) . '</div>';
    echo '<div class="day-meta">';
    echo '<span class="year" data-year="' . $yearNum . '">' . $yearNum . '</span> ';
    // Injected the start/end dates into data attributes for JS to read
    echo '<span class="week" data-start="' . $weekStart . '" data-end="' . $weekEnd . '">Wk ' . $weekNum . '</span>';
    echo '</div></div>';
    
    echo '<div class="day-content">';
    foreach ($employees as $empCard => $empInfo) {
        // Updated: Removed data-card attribute
        echo '<div class="employee-row" data-name="' . htmlspecialchars(strtolower($empInfo['name'])) . '">';
        
        // Employee Info Section
        echo '<div class="employee-info">';
        echo '<div class="avatar" style="' . (!$empInfo['is_matched'] ? 'background: #b0bec5;' : '') . '">' . htmlspecialchars(substr($empInfo['name'], 0, 1)) . '</div>';
        
        // Name/Badge Section (ID removed from here)
        echo '<div>' . ($empInfo['is_matched'] ? '<span class="employee-name">' . htmlspecialchars($empInfo['name']) . '</span>' : '<span class="badge-unknown">Unknown Card</span>') . '</div>';
        echo '</div>'; // Closes .employee-info
        
        // Punch Times Section
        echo '<div class="punch-times">';
        foreach ($empInfo['punches'] as $punch) { echo '<span class="time-badge">' . htmlspecialchars($punch) . '</span>'; }
        echo '</div>'; // Closes .punch-times
        
        echo '</div>'; // Closes .employee-row
    }
    echo '</div></div>';
}
?>
