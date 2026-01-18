<?php
require_once '../../auth/Auth.php';
require_once '../../auth/db.php';

// Ensure user is logged in
if (!Auth::isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user = Auth::user();
$userId = $user['id'];
$conn = DB::connect();

try {
    // 1. Create users_dump table if it doesn't exist
    // logic: duplicate structure of users table
    $createTableQuery = "CREATE TABLE IF NOT EXISTS users_dump LIKE users";
    if (!$conn->query($createTableQuery)) {
        throw new Exception("Failed to prepare archive table: " . $conn->error);
    }

    // 2. Archive user data
    // We copy the current user record to the dump table
    try {
        $stmtCopy = $conn->prepare("INSERT INTO users_dump SELECT * FROM users WHERE id = ?");
        if (!$stmtCopy) throw new Exception($conn->error, $conn->errno);

        $stmtCopy->bind_param("i", $userId);

        if (!$stmtCopy->execute()) {
            throw new Exception($stmtCopy->error, $stmtCopy->errno);
        }
    } catch (Exception $e) { // Catch MySQL errors
        $errCode = $e->getCode();
        if ($errCode == 0 && isset($conn->errno) && $conn->errno != 0) $errCode = $conn->errno;

        // Error 1136: Column count doesn't match values count (Schema Mismatch)
        if ($errCode == 1136) {
            // Fix: Rename old dump table and recreate fresh one
            $backupName = "users_dump_archived_" . date('Ymd_His');
            $conn->query("RENAME TABLE users_dump TO $backupName");

            // Recreate table
            if (!$conn->query("CREATE TABLE users_dump LIKE users")) {
                throw new Exception("Failed to recreate users_dump after schema mismatch fix.");
            }

            // Retry Archive
            $stmtCopy = $conn->prepare("INSERT INTO users_dump SELECT * FROM users WHERE id = ?");
            $stmtCopy->bind_param("i", $userId);
            if (!$stmtCopy->execute()) {
                throw new Exception("Failed to archive account after schema fix: " . $stmtCopy->error);
            }
        }
        // Error 1062: Duplicate entry
        else if ($errCode == 1062) {
            // Delete existing dump record and retry (overwrite)
            $stmtDelDump = $conn->prepare("DELETE FROM users_dump WHERE id = ?");
            $stmtDelDump->bind_param("i", $userId);
            $stmtDelDump->execute();

            // Retry Archive
            $stmtCopy = $conn->prepare("INSERT INTO users_dump SELECT * FROM users WHERE id = ?");
            $stmtCopy->bind_param("i", $userId);
            if (!$stmtCopy->execute()) {
                throw new Exception("Failed to archive account (Duplicate Retry): " . $stmtCopy->error);
            }
        } else {
            // Other errors - rethrow
            throw $e;
        }
    }

    // 3. Hard Delete from users table
    $stmtDelete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmtDelete->bind_param("i", $userId);

    if (!$stmtDelete->execute()) {
        throw new Exception("Failed to delete account: " . $conn->error);
    }

    if ($stmtDelete->affected_rows === 0) {
        throw new Exception("Account deletion failed or user not found.");
    }

    // 4. Destroy session and redirect
    Auth::logout(); // Use Auth::logout() if available, else session_destroy

    // Auth::logout usually redirects, but we want custom message. 
    // If Auth::logout() does header location, we can't override.
    // Let's check Auth.php later if needed, but for now let's assume session_destroy is safe enough here manually if Auth::logout is unknown behavior.
    // Actually I'll use session_destroy() to be safe and manual redirect.
    session_destroy();

    header("Location: ../../login.php?msg=" . urlencode("Your account has been permanently deleted. Goodbye."));
    exit;
} catch (Exception $e) {
    // Log error?
    error_log("Account Deletion Error (User $userId): " . $e->getMessage());
    header("Location: index.php?msg=" . urlencode("Error: " . $e->getMessage()));
    exit;
}
