<?php
// Start a secure session
session_start();

// 1. CHOOSE YOUR MANAGEMENT PASSWORD HERE:
define('STAFF_PASSWORD', 'simplepassword'); 
define('TIMEOUT_SECONDS', 86400); // Inactivity threshold

// Handle Explicit Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['authenticated']);
    unset($_SESSION['last_activity']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['x_secure_token'])) {
    if ($_POST['x_secure_token'] === STAFF_PASSWORD) {
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time(); 
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Incorrect password access denied.";
    }
}

// Server-Side Inactivity Check
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > TIMEOUT_SECONDS)) {
        unset($_SESSION['authenticated']);
        unset($_SESSION['last_activity']);
        header("Location: " . $_SERVER['PHP_SELF'] . "?reason=timeout");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    unset($_SESSION['authenticated']);
    unset($_SESSION['last_activity']);
}

// If user is not authenticated, show the login gate
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    $display_msg = "Staff Access";
    if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
        $login_error = "Logged out due to inactivity.";
    }
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Login</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        /* 1. Change body to flex-column */
        body { 
            background: linear-gradient(180deg, #1c3344, #102a33, #000000); 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            align-items: center; /* Keeps it centered horizontally */
        }

        /* 2. Push login box to the center of available space */
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
        input[type="password"] { width: 100%; padding: 15px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; transition: all 0.3s; }
        input[type="password"]:focus { outline: none; border-color: #1976d2; box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.15); }
        button { width: 100%; padding: 15px; background-color: #1976d2; color: white; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        button:hover { background-color: #1565c0; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(25, 118, 210, 0.3); }
        .error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; font-size: 0.9rem; border: 1px solid #ffcdd2; }
        
        /* 3. Footer stays at the bottom */
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
            <input type="password" name="x_secure_token" placeholder="Enter Password" autocomplete="new-password" required autofocus>
            <button type="submit">Log In</button>
        </form>
    </div>
    <div class="footer">
        'PiClock' NFC Timestamp Viewer <span style="display: inline-block; transform: rotateY(180deg);">&copy;</span> 2025-<?php echo date('Y');?><br>made with &hearts; by F.Javier "<a href="mailto:habiwan@me.com" style="color: #fff;">habiwan</a>" Puig Diaz
    </div>
</body>
</html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PiClock Dashboard</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(180deg, #1c3344, #102a33, #000000); min-height: 100vh; padding: 30px 20px; color: #fff; }
        .container { max-width: 1200px; margin: 0 auto; }
        header { text-align: center; padding: 30px 0; margin-bottom: 30px; }
        header h1 { font-size: 2.8rem; margin-bottom: 15px; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        header p { font-size: 1.2rem; opacity: 0.9; }
        
        .controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 20px; }
        .search-box { flex: 1; min-width: 150px; max-width: 250px; }
        .search-box input { width: 100%; padding: 14px 20px; border-radius: 50px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.15); color: white; font-size: 1rem; }
        .search-box input:focus { outline: none; background: rgba(255, 255, 255, 0.25); border-color: #64b5f6; }
        
        .date-filters { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .date-group { display: flex; align-items: center; gap: 8px; }
        .date-group label { font-size: 0.9rem; color: #bbdefb; font-weight: 500; }
        .date-group input[type="date"] { padding: 11px 15px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.15); color: white; font-size: 0.95rem; font-family: inherit; color-scheme: dark; }
        .date-group input[type="date"]:focus { outline: none; background: rgba(255, 255, 255, 0.25); border-color: #64b5f6; }

        .timeline-container { display: flex; flex-direction: column; gap: 25px; margin-bottom: 20px; }
        .day-card { background: rgba(255, 255, 255, 0.95); border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .day-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; background: #1976d2; color: white; }
        .day-title { font-size: 1.2rem; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.2); }
        .day-meta { display: flex; gap: 10px; align-items: center; }
        .day-meta .year, .day-meta .week { padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; background: rgba(255, 255, 255, 0.2); color: white; font-weight: 500; }
        
        .day-content { display: flex; flex-direction: column; }
        .employee-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; border-bottom: 1px solid #e0e0e0; gap: 20px; }
        .employee-row:last-child { border-bottom: none; }
        .employee-row:hover { background-color: #f5f9ff; }
        
        .employee-info { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(45deg, #2196f3, #64b5f6); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.1rem; }
        .employee-name { font-weight: 600; font-size: 1.05rem; color: #263238; }
        .card-id-sub { font-size: 0.85rem; color: #666; margin-top: 2px; }
        .badge-unknown { background-color: #ffebee; color: #c62828; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; border: 1px solid #ffcdd2; display: inline-block; }
        code { background-color: #f0f1f4; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: #1976d2; font-size: 0.85rem; }
        
        .punch-times { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; max-width: 60%; }
        .time-badge { background: #e3f2fd; color: #0d47a1; padding: 6px 14px; border-radius: 30px; font-size: 0.9rem; font-weight: 600; border: 1px solid #bbdefb; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        
        .table-header { display: flex; justify-content: space-between; padding: 15px 25px; background: #0d47a1; color: white; font-weight: 500; border-radius: 12px; margin-bottom: 15px; position: relative; z-index: 2; }
        .table-header a, .table-header a:link, .table-header a:visited { color: #ffffffff !important; text-decoration: none; cursor: default; }
        .footer { text-align: center; margin-top: 40px; color: rgba(255, 255, 255, 0.7); font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search employee or Card ID">
            </div>
            <div class="date-filters">
                <div class="date-group">
                    <label for="startDate">From:</label>
                    <input type="date" id="startDate">
                </div>
                <div class="date-group">
                    <label for="endDate">To:</label>
                    <input type="date" id="endDate">
                </div>
            </div>
        </div>

        <div class="table-header">
            <div><a href="/staff_names.php" target="_blank">Grouped Shift Logs</a></div>
            <div id="recordCounter">Loading NFC Card swipes...</div>
        </div>
        
        <div class="timeline-container" id="live-data">
            </div>
    <div class="footer">
        'PiClock' NFC Timestamp Viewer <span style="display: inline-block; transform: rotateY(180deg);">&copy;</span> 2025-<?php echo date('Y');?><br>made with &hearts; by F.Javier "<a href="mailto:habiwan@me.com" style="color: #fff;">habiwan</a>" Puig Diaz
    </div>
    <script>
        // --- FILTER LOGIC ---
        function filterDashboard() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            const dayCards = document.querySelectorAll('.day-card');
            let totalVisiblePunches = 0;
            
            dayCards.forEach(card => {
                const dayDate = card.getAttribute('data-date');
                
                let dateMatches = true;
                if (startDate && dayDate < startDate) dateMatches = false;
                if (endDate && dayDate > endDate) dateMatches = false;
                
                if (!dateMatches) {
                    card.style.display = 'none';
                    return;
                }
                
                let visibleEmployeesInDay = 0;
                const rows = card.querySelectorAll('.employee-row');
                
                rows.forEach(row => {
                    const empName = row.getAttribute('data-name');
                    const empCard = row.getAttribute('data-card');
                    
                    const textMatches = empName.includes(searchTerm) || empCard.includes(searchTerm);
                    
                    if (textMatches) {
                        row.style.display = '';
                        visibleEmployeesInDay++;
                        totalVisiblePunches += row.querySelectorAll('.time-badge').length;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                if (visibleEmployeesInDay > 0) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            document.getElementById('recordCounter').textContent = 'Total: ' + totalVisiblePunches + ' swipes';
        }

        document.getElementById('searchInput').addEventListener('input', filterDashboard);
        document.getElementById('startDate').addEventListener('change', filterDashboard);
        document.getElementById('endDate').addEventListener('change', filterDashboard);


        // --- LIVE UPDATE LOGIC ---
        let currentVersion = '';

        // Fetches the HTML from the backend and updates the container
        async function fetchTableData() {
            try {
                // Ensure this points to the new PHP script you created in the first step
                const response = await fetch('get-table-data.php?t=' + Date.now());
                if (!response.ok) return;
                const htmlRows = await response.text();
                
                // Inject the new HTML
                document.getElementById('live-data').innerHTML = htmlRows;
                
                // Re-run the filter immediately so new data obeys active search/dates
                filterDashboard();
                
            } catch (error) {
                console.error('Error fetching table data:', error);
            }
        }

        // Checks the database for new row counts
        async function checkServerForUpdates() {
            try {
                const response = await fetch('check_update.php?t=' + Date.now());
                if (!response.ok) {
                    console.warn('Update background check failed with status:', response.status);
                    return;
                }
                
                const latestVersion = await response.text();

                // Initial load scenario
                if (!currentVersion) {
                    currentVersion = latestVersion;
                    fetchTableData(); // Fetch the data for the first time
                    return;
                }

                // If a change happened in the MySQL Database
                if (latestVersion !== currentVersion) {
                    console.log('Database change detected! Fetching new data...');
                    currentVersion = latestVersion;
                    fetchTableData(); 
                }
            } catch (error) {
                console.error('Error checking for updates:', error);
            }
        }

        // Check for updates every 3000 milliseconds (3 seconds)
        checkServerForUpdates(); 
        setInterval(checkServerForUpdates, 3000);
    </script>
</body>
</html>
