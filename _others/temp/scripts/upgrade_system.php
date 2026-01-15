<?php
ob_start();
ini_set('display_errors', 1); error_reporting(E_ALL);

require_once __DIR__ . '/frontend/PyViz/auth/db.php';
$conn = DB::connect();

echo "<h3>Upgrading System...</h3>";

// 1. Alter Table
$sql = "ALTER TABLE teacher_verification_requests ADD COLUMN document_path VARCHAR(255) AFTER user_id";
try {
    if ($conn->query($sql)) {
        echo "Added document_path column.<br>";
    } else {
        // Might fail if exists
        echo "Column add skipped/failed: " . $conn->error . "<br>";
    }
} catch (Throwable $e) {
    echo "Column check: " . $e->getMessage() . "<br>";
}

// 2. Create Directory
$dir = __DIR__ . '/frontend/PyViz/user/docs';
if (!is_dir($dir)) {
    if (mkdir($dir, 0755, true)) {
        echo "Created directory: frontend/PyViz/user/docs<br>";
    } else {
        echo "Failed to create directory.<br>";
    }
} else {
    echo "Directory exists.<br>";
}

echo "<hr>Done. Please try uploading again.";
?>
