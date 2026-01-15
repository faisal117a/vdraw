<?php
ob_start();
ini_set('display_errors', 1); error_reporting(E_ALL);

require_once __DIR__ . '/frontend/PyViz/auth/db.php';
$conn = DB::connect();

echo "<h3>Fixing Missing Columns...</h3>";

// Add updated_at to teacher_verification_requests
$sql = "ALTER TABLE teacher_verification_requests ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
try {
    if ($conn->query($sql)) {
        echo "Added updated_at column to teacher_verification_requests.<br>";
    } else {
        echo "Column updated_at add skipped/failed (maybe exists): " . $conn->error . "<br>";
    }
} catch (Throwable $e) {
    echo "Error adding updated_at: " . $e->getMessage() . "<br>";
}

// Just in case, check submitted_at
$sql = "ALTER TABLE teacher_verification_requests ADD COLUMN submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP";
try {
   $conn->query($sql); // Ignore error if exists
} catch (Throwable $e) { }

echo "<hr>Done. Please try uploading again.";
?>
