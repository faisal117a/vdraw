<?php
// recreate_security_tables.php
require_once __DIR__ . '/frontend/PyViz/auth/db.php';

$conn = DB::connect();
echo "Dropping bad tables...<br>";

$conn->query("DROP TABLE IF EXISTS blocked_ips");
$conn->query("DROP TABLE IF EXISTS blocked_countries");

echo "Recreating tables...<br>";

// Blocked IPs
$sql = "CREATE TABLE blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    reason VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) echo "Table blocked_ips Created OK.<br>";
else echo "Error creating blocked_ips: " . $conn->error . "<br>";

// Blocked Countries
$sql = "CREATE TABLE blocked_countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_code VARCHAR(2) NOT NULL UNIQUE,
    reason VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) echo "Table blocked_countries Created OK.<br>";
else echo "Error creating blocked_countries: " . $conn->error . "<br>";

echo "Done. Please reload pages.";
?>
