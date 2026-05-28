<?php
/** Configure error reporting */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

/** Function to read names.csv bypassing Excel BOM corruption */
function read_names_csv($filename) {
    $rows = [];
    if (!file_exists($filename)) {
        return $rows;
    }
    
    if (($handle = fopen($filename, "r")) !== FALSE) {
        fgetcsv($handle, 1000, ",");
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (empty(array_filter($data))) continue;
            if (count($data) >= 2) {
                $rows[] = [
                    'card' => trim($data[0]),
                    'name' => trim($data[1])
                ];
            }
        }
        fclose($handle);
    }
    return $rows;
}

/** 1. LOAD EMPLOYEES MAP (names.csv) **/
$employeesRaw = read_names_csv('names.csv');
$employeeMap = [];
foreach ($employeesRaw as $emp) {
    $cardKey = $emp['card'] ?? '';
    $nameVal = $emp['name'] ?? '';
    if (!empty($cardKey)) {
        $employeeMap[strtolower(trim($cardKey))] = trim($nameVal);
    }
}

/** 2. PARSE AND MAP TIMESTAMPS (times.csv) **/
$combinedData = [];
if (file_exists('times.csv') && ($handle = fopen('times.csv', 'r')) !== FALSE) {
    while (($line = fgets($handle)) !== FALSE) {
        $line = trim($line);
        if (empty($line)) continue;

        if (preg_match('/\[\s*(\'0x[^\']+\'(?:\s*,\s*\'0x[^\']+\')*)\s*\]\s*,\s*[^,]+\s*,\s*\'([^\']+)\'/', $line, $matches)) {
            $hexPart = $matches[1]; 
            $timestamp = $matches[2]; 
            
            $hexElements = array_map(function($el) {
                return str_replace(["'", "0x", " "], "", $el);
            }, explode(',', $hexPart));
            
            $cardStr = strtolower(implode('', $hexElements));
            
            if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $timestamp, $timeMatches)) {
                $timestamp = $timeMatches[0];
            }

            if (!empty($cardStr) && !empty($timestamp)) {
                $timeObj = strtotime($timestamp);
                $isMatched = isset($employeeMap[$cardStr]);
                $employeeName = $isMatched ? $employeeMap[$cardStr] : 'Unknown Card';
                
                $combinedData[] = [
                    'year' => (int)date('Y', $timeObj),
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
    }
    fclose($handle);
}

/** 3. MULTI-COLUMN CHRONOLOGICAL SORTING ENGINE **/
if (!empty($combinedData)) {
    $dateOnly = []; $names = []; $timestamps = [];
    foreach ($combinedData as $key => $row) {
        $dateOnly[$key]   = $row['date_only'];
        $names[$key]      = strtolower($row['name']);
        $timestamps[$key] = $row['timestamp'];
    }
    array_multisort(
        $dateOnly, SORT_DESC, SORT_STRING,
        $names, SORT_ASC,  SORT_STRING,
        $timestamps, SORT_ASC,  SORT_STRING,
        $combinedData
    );
}

/** 4. GROUP DATA BY DAY AND SUBGROUP BY EMPLOYEE **/
$groupedData = [];
foreach ($combinedData as $row) {
    $dayKey = $row['date_only'] . '|' . $row['formatted_day'] . '|' . $row['week'] . '|' . $row['year'];
    $empKey = $row['card'];
    
    if (!isset($groupedData[$dayKey])) {
        $groupedData[$dayKey] = [];
    }
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

/** 5. OUTPUT HTML FRAGMENTS **/
foreach ($groupedData as $dayKey => $employees) {
    list($dateOnly, $formattedDay, $weekNum, $yearNum) = explode('|', $dayKey);
    ?>
    <div class="day-card" data-date="<?php echo $dateOnly; ?>">
        <div class="day-header">
            <div class="day-title"><?php echo htmlspecialchars($formattedDay); ?></div>
            <div class="day-meta">
                <span class="year"><?php echo $yearNum; ?></span> 
                <span class="week">Wk <?php echo $weekNum; ?></span>
            </div>
        </div>
        <div class="day-content">
            <?php foreach ($employees as $empCard => $empInfo): ?>
            <div class="employee-row" data-name="<?php echo htmlspecialchars(strtolower($empInfo['name'])); ?>" data-card="<?php echo htmlspecialchars(strtolower($empInfo['card'])); ?>">
                <div class="employee-info">
                    <div class="avatar" style="<?php echo !$empInfo['is_matched'] ? 'background: #b0bec5;' : ''; ?>">
                        <?php echo htmlspecialchars(substr($empInfo['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div>
                            <?php if ($empInfo['is_matched']): ?>
                                <span class="employee-name"><?php echo htmlspecialchars($empInfo['name']); ?></span>
                            <?php else: ?>
                                <span class="badge-unknown">Unknown Card</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-id-sub">
                            ID: <code><?php echo htmlspecialchars($empInfo['card']); ?></code>
                        </div>
                    </div>
                </div>
                <div class="punch-times">
                    <?php foreach ($empInfo['punches'] as $punch): ?>
                        <span class="time-badge"><?php echo htmlspecialchars($punch); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>
