<?php
// Start a secure session
session_start();

// 1. CHOOSE YOUR MANAGEMENT PASSWORD HERE:
define('MANAGEMENT_PASSWORD', 'BOOKSetc.8732!'); // find a better way if this bothers you. Hint: with a local file or sys variable instead, this could be improved... BY YOU!
define('MANAGEMENT_TIMEOUT_SECONDS', 120); // Inactivity threshold

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
        $_SESSION['MANAGEMENT_last_activity'] = time(); // Initialize activity timestamp
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Incorrect password access denied.";
    }
}

// Server-Side Inactivity Check (Fallback for security alignment)
if (isset($_SESSION['MANAGEMENT_PASSWORD_authenticated']) && $_SESSION['MANAGEMENT_PASSWORD_authenticated'] === true) {
    if (isset($_SESSION['MANAGEMENT_last_activity']) && (time() - $_SESSION['MANAGEMENT_last_activity'] > MANAGEMENT_TIMEOUT_SECONDS)) {
        unset($_SESSION['MANAGEMENT_PASSWORD_authenticated']);
        unset($_SESSION['MANAGEMENT_last_activity']);
        header("Location: " . $_SERVER['PHP_SELF'] . "?reason=timeout");
        exit;
    }
    $_SESSION['MANAGEMENT_last_activity'] = time(); // Refresh active timestamp on server interactions
}

// If the JavaScript idle timer triggered a timeout redirect, clear the access tokens immediately
if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    unset($_SESSION['MANAGEMENT_PASSWORD_authenticated']);
    unset($_SESSION['MANAGEMENT_last_activity']);
}

// If user is not authenticated, show the login gate
if (!isset($_SESSION['MANAGEMENT_PASSWORD_authenticated']) || $_SESSION['MANAGEMENT_PASSWORD_authenticated'] !== true) {
    $display_msg = "Management Access";
    if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
        $login_error = "Logged out due to 2 minutes of inactivity.";
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Management Login</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding-top: 100px; text-align: center; }
            .login-box { max-width: 320px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            h2 { margin-bottom: 20px; color: #333; }
            input[type="password"] { width: 90%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; }
            button { width: 97%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
            button:hover { background-color: #0056b3; }
            .error { color: #dc3545; font-weight: bold; margin-bottom: 15px; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2><?= htmlspecialchars($display_msg) ?></h2>
            <?php if (isset($login_error)): ?>
                <div class="error"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="password" name="x_secure_token" placeholder="Enter Password" autocomplete="new-password" required autofocus>
                <button type="submit">Log In</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Configuration: THIS NEEDS TO BE CHANGED!
$pi_ip = "192.168.X.X"; 
$pi_user = "YOURPIUSER";
$remote_file = "/home/YOURPIUSER/nfc/names.csv";
$local_tmp = "/tmp/names.csv";
$ssh_key = "/var/www/html/.ssh/id_ed25519";

$message = "";

// 1. Handle Form Submission (Save Data)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cards'])) {
    $fp = fopen($local_tmp, 'w');
    
    // FIX 1: ALWAYS write a proper header row at the top of the file first!
    fputcsv($fp, ['CardID', 'EmployeeName'], ",", "\"", "\\"); 
    
    foreach ($_POST['cards'] as $card_id => $name) {
        // Sanitize inputs
        $clean_id = trim(strip_tags($card_id));
        $clean_name = trim(strip_tags($name));
        fputcsv($fp, [$clean_id, $clean_name], ",", "\"", "\\");
    }
    fclose($fp);
    
    // SCP the updated file back to the Pi
    $scp_cmd = "scp -i $ssh_key $local_tmp $pi_user@$pi_ip:$remote_file 2>&1";
    shell_exec($scp_cmd);
    $message = "<div style='color: green; font-weight: bold; margin-bottom: 15px;'>File updated successfully!</div>";
}

// 2. Fetch the latest file from the Pi to display
$fetch_cmd = "scp -o StrictHostKeyChecking=no -i $ssh_key $pi_user@$pi_ip:$remote_file $local_tmp 2>&1";
$output = shell_exec($fetch_cmd);
if ($output) {
    echo "<pre style='background: #fee; padding: 10px; border: 1px solid #fcc;'>SCP Debug Output:\n" . htmlspecialchars($output) . "</pre>";
}

// 3. Parse the CSV
$csv_data = [];
if (($handle = fopen($local_tmp, "r")) !== FALSE) {
    
    // FIX 2: Safely check the first line. If it's a real card, DON'T skip it!
    $first_row = fgetcsv($handle, 1000, ",", "\"", "\\");
    if ($first_row !== FALSE) {
        // If the first row is NOT our defined header, it's a real card from your old file! Save it.
        if ($first_row[0] !== 'CardID') {
            $csv_data[$first_row[0]] = $first_row[1];
        }
    }
    
    // Loop through the remaining rows as normal
    while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
        if (count($data) >= 2) {
            $csv_data[$data[0]] = $data[1];
        }
    }
    fclose($handle);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage NFC Cards</title>
    <link rel="icon" href="favicon.svg">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; }
        .header-container { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .logout-btn { padding: 8px 12px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .logout-btn:hover { background-color: #bd2130; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        input[type="text"] { width: 90%; padding: 5px; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>

    <div class="header-container">
        <h2>Assign Card Names</h2>
        <a href="?logout=1" class="logout-btn">Log Out</a>
    </div>

    <p>Update the names below to replace the _UNASSIGNED values</p>
    <p>When done, scroll to the bottom and click on "Save Changes"</p></p>
    <p>WARNING! HANDLE WITH CARE! CHECK TWICE BEFORE SAVING!</p>
    <p>when clicking on "Save Changes" at the bottom ALL VALUES will be updated!</p></p>
    <p>This page will timeout in 2 minutes</p>

    <?= $message ?>

    <form method="POST">
        <table>
            <tr>
                <th>Card ID</th>
                <th>Employee</th>
            </tr>
            <?php foreach ($csv_data as $card_id => $name): ?>
            <tr>
                <td><?= htmlspecialchars($card_id) ?></td>
                <td>
                    <input type="text" 
                           name="cards[<?= htmlspecialchars($card_id) ?>]" 
                           value="<?= htmlspecialchars($name) ?>">
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="submit">Save Changes</button>
    </form>

    <script>
        (function() {
            const timeoutDuration = 20000; // 20 seconds in milliseconds
            let idleTimer;

            function resetTimer() {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(logoutUser, timeoutDuration);
            }

            function logoutUser() {
                // Redirect user to logout execution parameter with a reason string attached
                window.location.href = window.location.pathname + "?reason=timeout";
            }

            // Monitor continuous explicit interactions across the DOM space
            window.onload = resetTimer;
            document.onmousemove = resetTimer;
            document.onkeypress = resetTimer;
            document.onmousedown = resetTimer; 
            document.ontouchstart = resetTimer;
            document.onclick = resetTimer;
            document.onscroll = resetTimer;
        })();
    </script>

</body>
</html>
