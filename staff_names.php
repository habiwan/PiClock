<?php
// Configuration 
// Remember to adjust the Pi IP address and user
$pi_ip = "192.168.X.X"; 
$pi_user = "habiwan";
$remote_file = "/home/habiwan/nfc/names.csv";
$local_tmp = "/tmp/names.csv";
// for the "UbuntuVM Docker LAMP machine" to be able to update names.csv on the Pi, I added a private key to root inside the docker machine
// (I had to apt install sudo and openssh tools to he docker container manually first)
$ssh_key = "/var/www/html/.ssh/id_ed25519"; // I added it to /var/www/html/.ssh (not recommended) but I just did not want to "recompose" the docker
// ... on the other hand, is good enough, to get to this key you first need local access anyways, and it is not supposed to run on the Internet!

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
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        input[type="text"] { width: 90%; padding: 5px; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>

    <h2>Assign Card Names</h2>
    <p>Update the names below to replace the _UNASSIGNED values</p>
    <p>When done, scroll to the bottom and click on "Save Changes"</p>
    <p>WARNING! HANDLE WITH CARE! CHECK TWICE BEFORE SAVING!</p>
    <p>when clicking on "Save Changes" at the bottom ALL VALUES will be updated!</p>

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

</body>
</html>
