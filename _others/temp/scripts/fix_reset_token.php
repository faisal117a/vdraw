<?php
// fix_reset_token.php
require_once 'frontend/PyViz/auth/db.php';

$conn = DB::connect();

// Add reset_token and reset_expires_at columns to users table
$sqls = [
    "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL AFTER password",
    "ALTER TABLE users ADD COLUMN reset_expires_at DATETIME NULL AFTER reset_token"
];

foreach ($sqls as $sql) {
    try {
        $conn->query($sql);
        echo "Executed: $sql <br>";
    } catch (Exception $e) {
        echo "Error/Skipped: " . $e->getMessage() . " <br>";
    }
}
echo "Done.";
?>
