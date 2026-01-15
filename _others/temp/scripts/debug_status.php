<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/auth/db.php';

echo "--- DB Checks ---\n";

function checkTable($name) {
    echo "Checking $name... ";
    try {
        $q = "SELECT COUNT(*) as c FROM $name";
        $stmt = DB::query($q);
        if ($stmt && $res = $stmt->get_result()) {
            $row = $res->fetch_assoc();
            echo "Exists. Rows: " . $row['c'] . "\n";
        } else {
            echo "Query OK but no result.\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

checkTable('tracking_events');
checkTable('user_points');
checkTable('tracking_live_sessions');
checkTable('users');

echo "\n--- Sample Data ---\n";
// Check if user_points join works
try {
    echo "Top Earners Query: ";
    $q = "SELECT u.email, p.points FROM user_points p JOIN users u ON p.user_id = u.id ORDER BY p.points DESC LIMIT 5";
    $stmt = DB::query($q);
    if ($stmt && $res = $stmt->get_result()) {
        echo "Rows: " . $res->num_rows . "\n";
        while($r = $res->fetch_assoc()) {
            echo " - " . $r['email'] . ": " . $r['points'] . "\n";
        }
    } else {
        echo "Failed.\n";
    }
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

// Check Rankings
try {
    echo "Rankings Query: ";
    $q = "SELECT app_name, COUNT(*) as c FROM tracking_events WHERE event_type='visit' GROUP BY app_name LIMIT 5";
    $stmt = DB::query($q);
    if ($stmt && $res = $stmt->get_result()) {
        echo "Rows: " . $res->num_rows . "\n";
        while($r = $res->fetch_assoc()) echo " - " . $r['app_name'] . ": " . $r['c'] . "\n";
    }
} catch (Exception $e) { echo $e->getMessage() . "\n"; }
?>
