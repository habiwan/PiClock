<?php
// Start a secure session
session_start();

// 1. CHOOSE YOUR MANAGEMENT PASSWORD HERE:
define('MANAGEMENT_PASSWORD', 'SuperSecurePassword'); 
define('MANAGEMENT_TIMEOUT_SECONDS', 7200); // Inactivity threshold

// Handle Explicit Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['MANAGEMENT_PASSWORD_authenticated']);
    unset($_SESSION['MANAGEMENT_last_activity']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['x_secure_token'])) {
    if ($_POST['x_secure_token'] === MANAGEMENT_PASSWORD) {
        $_SESSION['MANAGEMENT_PASSWORD_authenticated'] = true;
        $_SESSION['MANAGEMENT_last_activity'] = time();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Incorrect password access denied.";
    }
}

// Server-Side Inactivity Check
if (isset($_SESSION['MANAGEMENT_PASSWORD_authenticated']) && $_SESSION['MANAGEMENT_PASSWORD_authenticated'] === true) {
    if (isset($_SESSION['MANAGEMENT_last_activity']) && (time() - $_SESSION['MANAGEMENT_last_activity'] > MANAGEMENT_TIMEOUT_SECONDS)) {
        unset($_SESSION['MANAGEMENT_PASSWORD_authenticated']);
        unset($_SESSION['MANAGEMENT_last_activity']);
        header("Location: " . $_SERVER['PHP_SELF'] . "?reason=timeout");
        exit;
    }
    $_SESSION['MANAGEMENT_last_activity'] = time();
}

if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    unset($_SESSION['MANAGEMENT_PASSWORD_authenticated']);
    unset($_SESSION['MANAGEMENT_last_activity']);
}

if (!isset($_SESSION['MANAGEMENT_PASSWORD_authenticated']) || $_SESSION['MANAGEMENT_PASSWORD_authenticated'] !== true) {
    $display_msg = "Management Access";
    if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
        $login_error = "Logged out due to inactivity.";
    }
    ?>
    <!DOCTYPE html>
<html>
<head>
    <title>Management Login</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        /* 1. Updated body to vertical flex column */
        body { 
            background: linear-gradient(180deg, #1c3344, #102a33, #000000); 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }
        
        /* 2. Margin: auto pushes the box to the center of remaining space */
        .login-box { 
            margin: auto; 
            background: rgba(255, 255, 255, 0.95); 
            padding: 40px 30px; 
            border-radius: 12px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.5); 
            width: 100%; 
            max-width: 380px; 
            text-align: center; 
        }
        
        h2 { margin-bottom: 25px; color: #0d47a1; font-size: 1.8rem; font-weight: 600; }
        input[type="password"] { width: 100%; padding: 15px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; }
        button { width: 100%; padding: 15px; background-color: #1976d2; color: white; border: none; border-radius: 8px; font-size: 1.1rem; cursor: pointer; }
        .error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffcdd2; }
        
        /* 3. Footer fixed at bottom */
        .footer { 
            text-align: center; 
            padding-bottom: 20px; 
            color: rgba(255, 255, 255, 0.7); 
            font-size: 0.9rem; 
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2><?= htmlspecialchars($display_msg) ?></h2>
        <?php if (isset($login_error)): ?>
            <div class="error"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <input type="password" name="x_secure_token" placeholder="Enter Password" required autofocus>
            <button type="submit">Secure Log In</button>
        </form>
    </div>
    <div class="footer">
        'PiClock' NFC Timestamp Manager <span style="display: inline-block; transform: rotateY(180deg);">&copy;</span> 2025-<?php echo date('Y');?><br>made with &hearts; by F.Javier "<a href="mailto:habiwan@me.com" style="color: #fff;">habiwan</a>" Puig Diaz
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

// 1. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cards'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE names SET CardID = ?, Name = ? WHERE id = ?");
        
        foreach ($_POST['cards'] as $id => $data) {
            $stmt->execute([trim($data['CardID']), trim($data['Name']), $id]);
        }
        $pdo->commit();
        $message = "<div style='color: #81c784; background: rgba(129, 199, 132, 0.1); border: 1px solid #81c784; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;'>Database updated successfully!</div>";
    } catch (Exception $e) { $pdo->rollBack(); $message = "<div style='color: #e57373; border: 1px solid #e57373; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>Error: " . $e->getMessage() . "</div>"; }
}

// 2. Fetch data with Sorting Logic
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'name') ? 'Name ASC' : 'id ASC';
$stmt = $pdo->query("SELECT id, CardID, Name FROM names ORDER BY $sort");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage NFC Cards</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(180deg, #1c3344, #102a33, #000000); min-height: 100vh; padding: 40px 20px; color: #fff; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header-container { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); margin-bottom: 20px; }
        .sort-nav { margin-bottom: 20px; }
        .sort-nav a { color: #fff; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.1); border-radius: 6px; margin-right: 10px; font-size: 0.9rem; }
        .sort-nav a.active { background: #1976d2; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 1rem; border: none; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .btn-logout { background-color: #e53935; color: white; }
        .btn-logout:hover { background-color: #c62828; transform: translateY(-2px); }
        .btn-save { background-color: #1976d2; color: white; width: 100%; padding: 16px; font-size: 1.2rem; margin-top: 10px; }
        .btn-save:hover { background-color: #1565c0; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(25, 118, 210, 0.4); }
        .btn-save { background-color: #1976d2; color: white; width: 100%; padding: 16px; font-size: 1.2rem; border: none; margin-top: 10px; }
        .card-table { background: #fff; border-radius: 12px; padding: 1px; color: #333; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #0d47a1; color: white; padding: 16px; text-align: left; }
        td { padding: 12px 16px; border-bottom: 1px solid #e0e0e0; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; }
        .barcode { display: block; margin: auto; height: 35px; }
        .instructions { background: rgba(255, 255, 255, 0.1); padding: 20px 25px; border-radius: 12px; margin-bottom: 30px; border-left: 5px solid #ffb300; backdrop-filter: blur(5px); }
        .instructions p { margin-bottom: 8px; font-size: 1.05rem; line-height: 1.5; opacity: 0.9; }
        .instructions p:last-child { margin-bottom: 0; }
        .warning-text { color: #ffcc80; font-weight: bold; letter-spacing: 0.5px; }
        .footer { text-align: center; margin-top: 40px; color: rgba(255, 255, 255, 0.7); font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-container">
        <h2>Assign Card Names</h2>
        <a href="?logout=1" class="btn btn-logout">Log Out</a>        
    </div>

    <div class="instructions">
        <p>To update the employee names use the fields below to rename each NFC Card. 
	<br>Then scroll to the bottom and click "Save Changes".</p>
        <p class="warning-text">⚠️  WARNING: "Save Changes" writes ALL VALUES to the database!</p>
        <p style="font-size: 0.85rem; margin-top: 10px; opacity: 0.7;">🔒 For security, this session will automatically time out after 2 hours of inactivity.</p>
    </div>

    <?= $message ?>
    <!-- Sorting Toggle -->
    <div class="sort-nav">
        Sort by: 
        <a href="?" class="<?= (!isset($_GET['sort'])) ? 'active' : '' ?>">Physical Order</a>
        <a href="?sort=name" class="<?= (isset($_GET['sort']) && $_GET['sort'] === 'name') ? 'active' : '' ?>">Employee Name</a>
    </div>

    <?= $message ?>

    <form method="POST">
        <div class="card-table">
            <table>
                <tr>
                    <th style="width: 10%;">ID</th>
                    <th style="width: 20%;">Card ID</th>
                    <th style="width: 40%;">Employee Name</th>
                    <th style="text-align: center; width: 30%;">Barcode</th> 
                </tr>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><code><?= htmlspecialchars($row['id']) ?></code></td>
                    <td><code><?= htmlspecialchars($row['CardID']) ?></code></td>
                    <td>
                        <input type="text" name="cards[<?= $row['id'] ?>][CardID]" value="<?= htmlspecialchars($row['CardID']) ?>" style="display:none;">
                        <input type="text" name="cards[<?= $row['id'] ?>][Name]" value="<?= htmlspecialchars($row['Name']) ?>">
                    </td>
                    <td style="text-align: center;"><svg class="barcode" data-code="<?= htmlspecialchars($row['CardID']) ?>"></svg></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <button type="submit" class="btn btn-save">Save Changes</button>
    </form>
    <div class="footer">
        'PiClock' NFC Timestamp Manager <span style="display: inline-block; transform: rotateY(180deg);">&copy;</span> 2025-<?php echo date('Y');?><br>made with &hearts; by F.Javier "<a href="mailto:habiwan@me.com" style="color: #fff;">habiwan</a>" Puig Diaz
    </div>
</div>

<script src="JsBarcode.all.min.js" defer></script>
<script>
    window.addEventListener('load', function() {
        document.querySelectorAll('.barcode').forEach(function(element) {
            const code = element.getAttribute('data-code');
            try { JsBarcode(element, code, {format: "CODE128", width: 1.5, height: 35, displayValue: false, margin: 5}); } 
            catch (e) { console.error("Barcode failed", e); }
        });
    });
    
    (function() {
        const timeoutDuration = 120000;
        let idleTimer;
        function resetTimer() { clearTimeout(idleTimer); idleTimer = setTimeout(logoutUser, timeoutDuration); }
        function logoutUser() { window.location.href = window.location.pathname + "?reason=timeout"; }
        window.onload = resetTimer; document.onmousemove = resetTimer; document.onkeypress = resetTimer;
    })();
</script>
</body>
</html>
