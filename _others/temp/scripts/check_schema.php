<?php
require_once __DIR__ . '/frontend/PyViz/auth/db.php';
$conn = DB::connect();

function desc($t) {
    global $conn;
    echo "\nDESCRIBE $t:\n";
    try {
        $r = $conn->query("DESCRIBE $t");
        if($r) {
            while($row = $r->fetch_assoc()) {
                echo $row['Field'] . " | " . $row['Type'] . "\n";
            }
        } else echo "Table $t not found or error.\n";
    } catch(Exception $e) { echo $e->getMessage(); }
}

desc('users');
desc('user_activity_log');
desc('ip_usage_monthly');
desc('blocked_ips'); // Guessing table name
desc('settings');
?>
