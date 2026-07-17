<?php
// Define defaults so they are never "undefined"
$rows = [];
$columns = [];
$total_rows = 0;
$total_pages = 0;
$page = 1;
$sort_col = ''; 
$sort_dir = 'ASC';

// Start a secure session
session_start();

// 1. CHOOSE YOUR DEV PASSWORD HERE:
define('DEV_PASSWORD', 'D3v3l0p3rP4$$w0rd');
define('DEV_TIMEOUT_SECONDS', 1800); // 30 min Inactivity threshold

// Handle Explicit Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['DEV_PASSWORD_authenticated']);
    unset($_SESSION['DEV_last_activity']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['x_secure_token'])) {
    if ($_POST['x_secure_token'] === DEV_PASSWORD) {
        $_SESSION['DEV_PASSWORD_authenticated'] = true;
        $_SESSION['DEV_last_activity'] = time();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Incorrect password access denied.";
    }
}

// Server-Side Inactivity Check
if (isset($_SESSION['DEV_PASSWORD_authenticated']) && $_SESSION['DEV_PASSWORD_authenticated'] === true) {
    if (isset($_SESSION['DEV_last_activity']) && (time() - $_SESSION['DEV_last_activity'] > DEV_TIMEOUT_SECONDS)) {
        unset($_SESSION['DEV_PASSWORD_authenticated']);
        unset($_SESSION['DEV_last_activity']);
        header("Location: " . $_SERVER['PHP_SELF'] . "?reason=timeout");
        exit;
    }
    $_SESSION['DEV_last_activity'] = time();
}

if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    unset($_SESSION['DEV_PASSWORD_authenticated']);
    unset($_SESSION['DEV_last_activity']);
}

// Display Login Screen if not authenticated
if (!isset($_SESSION['DEV_PASSWORD_authenticated']) || $_SESSION['DEV_PASSWORD_authenticated'] !== true) {
    $display_msg = "Developer Access";
    if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
        $login_error = "Logged out due to 30 min of inactivity.";
    }
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>Developer Login</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(180deg, #1c3344, #102a33, #000000); min-height: 100vh; display: flex; flex-direction: column; align-items: center; }
        .login-box { margin: auto; background: rgba(255, 255, 255, 0.95); padding: 40px 30px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); width: 100%; max-width: 380px; text-align: center; }
        h2 { margin-bottom: 25px; color: #00796b; font-size: 1.8rem; font-weight: 600; }
        input[type="password"] { width: 100%; padding: 15px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; }
        button { width: 100%; padding: 15px; background-color: #00897b; color: white; border: none; border-radius: 8px; font-size: 1.1rem; cursor: pointer; transition: 0.3s; }
        button:hover { background-color: #00695c; }
        .error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffcdd2; }
        .footer { text-align: center; padding-bottom: 20px; color: rgba(255, 255, 255, 0.7); font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2><?= htmlspecialchars($display_msg) ?></h2>
        <?php if (isset($login_error)): ?>
            <div class="error"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <input type="password" name="x_secure_token" placeholder="Enter Dev Password" required autofocus>
            <button type="submit">Secure Log In</button>
        </form>
    </div>
    <div class="footer">
        'PiClock' DB Manager <span style="display: inline-block; transform: rotateY(180deg);">&copy;</span> 2026
    <br>made with &hearts; by F.Javier "<a href="mailto:habiwan@me.com" style="color: #fff;">habiwan</a>" Puig Diaz
    </div>
</body>
</html>
    <?php exit;
}

/** Database Connection **/
$dbHost = 'db'; $dbName = 'lampapp'; $dbUser = 'root'; $dbPass = 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { die("Database connection failed: " . $e->getMessage()); }

$message = "";
$current_table = (isset($_GET['table']) && $_GET['table'] === 'times') ? 'times' : 'names';
// Identify PK: 'UID' for times, 'id' for everything else
$pk = ($current_table === 'times') ? 'UID' : 'id';

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['backup_all'])) {
            $pdo->exec("DROP TABLE IF EXISTS names_backup; CREATE TABLE names_backup AS SELECT * FROM names;");
            $pdo->exec("DROP TABLE IF EXISTS times_backup; CREATE TABLE times_backup AS SELECT * FROM times;");
            $message = "<div class='msg-success'>Full Database Backup created!</div>";
        } elseif (isset($_POST['restore_names'])) {
            try {
                // Ensure table exists first
                $pdo->exec("CREATE TABLE IF NOT EXISTS names (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    CardID VARCHAR(12) NOT NULL UNIQUE,
                    name VARCHAR(100) DEFAULT NULL
                )");
                // Now restore
                $pdo->exec("TRUNCATE TABLE names; INSERT INTO names SELECT * FROM names_backup;");
                $message = "<div class='msg-success'>'names' table restored and re-created!</div>";
            } catch (Exception $e) {
                $message = "<div class='msg-error'>Error restoring 'names': " . $e->getMessage() . "</div>";
            }
        } elseif (isset($_POST['restore_times'])) {
            try {
                // Ensure table exists first
                $pdo->exec("CREATE TABLE IF NOT EXISTS times (
                    UID VARCHAR(12) NOT NULL,
                    temp FLOAT,
                    timestamp DATETIME
                )");
                // Now restore
                $pdo->exec("TRUNCATE TABLE times; INSERT INTO times SELECT * FROM times_backup;");
                $message = "<div class='msg-success'>'times' table restored and re-created!</div>";
            } catch (Exception $e) {
                $message = "<div class='msg-error'>Error restoring 'times': " . $e->getMessage() . "</div>";
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'save_changes') {
            // ONLY proceed if 'rows' exists and is an array
            if (isset($_POST['rows']) && is_array($_POST['rows'])) {
                $pdo->beginTransaction();
                foreach ($_POST['rows'] as $pk_val => $columns) {
                    $setClauses = []; $params = [];
                    foreach ($columns as $col => $val) {
                        $setClauses[] = "`$col` = ?";
                        $params[] = trim($val);
                    }
                    $params[] = $pk_val;
                    $stmt = $pdo->prepare("UPDATE `$current_table` SET " . implode(", ", $setClauses) . " WHERE `$pk` = ?");
                    $stmt->execute($params);
                }
                $pdo->commit();
                $message = "<div class='msg-success'>Changes saved successfully!</div>";
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_selected') {
            if (!empty($_POST['selected_ids'])) {
                $ids = $_POST['selected_ids'];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM `$current_table` WHERE `$pk` IN ($placeholders)");
                $stmt->execute($ids);
                $message = "<div class='msg-success'>Rows deleted successfully!</div>";
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack(); 
        $message = "<div class='msg-error'>Error: " . $e->getMessage() . "</div>"; 
    }
}

// --- FETCH DATA ---
$rows = [];
$columns = [];
$total_rows = 0;
$total_pages = 0;

// Only attempt to fetch data if the table actually exists
try {
    // Check if table exists by trying to select one row
    $pdo->query("SELECT 1 FROM `$current_table` LIMIT 1");
    
    // If we reach here, table exists, proceed with standard queries
    $colStmt = $pdo->query("SHOW COLUMNS FROM `$current_table`");
    $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);

    $sort_col = (isset($_GET['sort']) && in_array($_GET['sort'], $columns)) ? $_GET['sort'] : $pk;
    $sort_dir = (isset($_GET['dir']) && in_array($_GET['dir'], ['ASC', 'DESC'])) ? $_GET['dir'] : 'ASC';

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $countStmt = $pdo->query("SELECT COUNT(*) FROM `$current_table`");
    $total_rows = $countStmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    $stmt = $pdo->prepare("SELECT * FROM `$current_table` ORDER BY `$sort_col` $sort_dir LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Table doesn't exist, leave $rows, $columns, etc., as empty arrays
    // The page will render, but the table section will be empty/blank
}

function buildUrl($params = []) { return "?" . http_build_query(array_merge($_GET, $params)); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Developer Tools</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(180deg, #1c3344, #102a33, #000000); min-height: 100vh; padding: 40px 20px; color: #fff; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header-container { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); margin-bottom: 20px; }
        .header-container h2 { margin: 0; color: #00bfa5; }
        .header-buttons { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .btn { padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; border: none; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .btn-fs { background-color: #4caf50; color: white; }
        .btn-logout { background-color: #e53935; color: white; }
        .btn-backup { background-color: #9c27b0; color: white; }
        .btn-restore { background-color: #ff9800; color: white; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }
        .tabs { display: flex; margin-bottom: 20px; gap: 10px; }
        .tabs a { padding: 12px 24px; background: rgba(255,255,255,0.1); color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; transition: 0.3s; }
        .tabs a:hover { background: rgba(255,255,255,0.2); }
        .tabs a.active { background: #00897b; color: white; box-shadow: 0 4px 10px rgba(0,137,123,0.4); }
        .msg-success { color: #81c784; background: rgba(129, 199, 132, 0.1); border: 1px solid #81c784; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; }
        .msg-error { color: #e57373; border: 1px solid #e57373; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .card-table { background: #fff; border-radius: 12px; color: #333; overflow-x: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); margin-bottom: 20px;}
        table { border-collapse: collapse; width: 100%; min-width: 800px; }
        th { background-color: #00695c; color: white; padding: 16px; text-align: left; }
        td { padding: 8px 12px; border-bottom: 1px solid #e0e0e0; vertical-align: middle; }
        tr:hover { background-color: #f5f5f5; }
        th a { color: #ffffff; text-decoration: none; display: block; }
        input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; font-family: monospace; }
        .bottom-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; flex-wrap: wrap; gap: 15px; }
        .btn-delete { background-color: #d32f2f; color: white; padding: 12px 20px; font-size: 1.1rem;}
        .btn-save { background-color: #0288d1; color: white; padding: 12px 40px; font-size: 1.1rem; }
        .pagination { display: flex; gap: 10px; align-items: center; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 8px; }
        .pagination a { color: #4fc3f7; text-decoration: none; font-weight: bold; }
        .footer { text-align: center; margin-top: 40px; color: rgba(255, 255, 255, 0.7); font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-container">
        <h2>🛠️ Developer DB Management</h2>
        <div class="header-buttons">
            <form method="POST" style="display:inline;" onsubmit="return confirm('Backup ALL tables now? This overwrites existing backups.');">
                <button type="submit" name="backup_all" class="btn btn-backup">Backup All</button></form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('WARNING: This drops current NAMES and restores from backup!');">
                <button type="submit" name="restore_names" class="btn btn-restore">Restore Names</button></form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('WARNING: This drops current TIMES and restores from backup!');">
                <button type="submit" name="restore_times" class="btn btn-restore">Restore Times</button></form>
            <a href="?logout=1" class="btn btn-logout">Log Out</a>
        </div>
    </div>
    <?= $message ?>
    <div class="tabs">
        <a href="?table=names" class="<?= $current_table === 'names' ? 'active' : '' ?>">Names Table</a>
        <a href="?table=times" class="<?= $current_table === 'times' ? 'active' : '' ?>">Times Table</a>
    </div>
    <form method="POST" id="dbForm">
        <input type="hidden" name="action" id="formAction" value="save_changes">
        <div class="card-table">
            <table>
                <tr>
                    <th style="width: 5%; text-align: center;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                    <?php if (!empty($columns)): ?>
                    <?php foreach ($columns as $col): ?>
                        <th>
                            <?php 
                                $next_dir = ($sort_col === $col && $sort_dir === 'ASC') ? 'DESC' : 'ASC';
                                $sortUrl = buildUrl(['sort' => $col, 'dir' => $next_dir]); 
                            ?>
                            <a href="<?= htmlspecialchars($sortUrl) ?>"><?= htmlspecialchars($col) ?> <?= ($sort_col === $col) ? ($sort_dir === 'ASC' ? '↑' : '↓') : '' ?></a>
                        </th>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <th>No columns found (Table empty)</th>
                    <?php endif; ?>
                </tr>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td style="text-align: center;"><input type="checkbox" name="selected_ids[]" class="rowCheckbox" value="<?= htmlspecialchars($row[$pk]) ?>"></td>
                    <?php foreach ($columns as $col): ?>
                    <td>
                        <?php if ($col === $pk): ?> <strong><?= htmlspecialchars($row[$col]) ?></strong>
                        <?php else: ?> <input type="text" name="rows[<?= htmlspecialchars($row[$pk]) ?>][<?= $col ?>]" value="<?= htmlspecialchars($row[$col]) ?>">
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div class="bottom-actions">
            <button type="button" class="btn btn-delete" onclick="submitForm('delete_selected')">Delete Selected</button>
            <div class="pagination">
                <span>Total: <?= $total_rows ?></span> | 
                <?php if ($page > 1): ?><a href="<?= buildUrl(['page' => $page - 1]) ?>">« Prev</a><?php endif; ?>
                <span>Page <?= $page ?></span>
                <?php if ($page < $total_pages): ?><a href="<?= buildUrl(['page' => $page + 1]) ?>">Next »</a><?php endif; ?>
            </div>
            <button type="button" class="btn btn-save" onclick="submitForm('save_changes')">Save All Edits</button>
        </div>
    </form>
    <div class="footer">
        'PiClock' DB Manager <span style="display: inline-block; transform: rotateY(180deg);">&copy;</span> 2026
    <br>made with &hearts; by F.Javier "<a href="mailto:habiwan@me.com" style="color: #fff;">habiwan</a>" Puig Diaz
    </div>
</div>
<script>
    $rows = [];
    $columns = [];
    $total_rows = 0;
    $total_pages = 0;
    $page = 1; // Initialize this!
    function toggleSelectAll() { const cb = document.getElementById('selectAll'); document.querySelectorAll('.rowCheckbox').forEach(i => i.checked = cb.checked); }
    function submitForm(action) { document.getElementById('formAction').value = action; document.getElementById('dbForm').submit(); }
</script>
</body>
</html>
