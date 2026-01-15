<?php
require_once __DIR__ . '/frontend/PyViz/auth/db.php';
$conn = DB::connect();
echo "<h1>Columns in blocked_ips</h1>";
$res = $conn->query("SHOW COLUMNS FROM blocked_ips");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
} else {
    echo "Table blocked_ips likely missing: " . $conn->error;
}
?>
