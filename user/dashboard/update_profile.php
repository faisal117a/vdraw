<?php
// frontend/PyViz/user/dashboard/update_profile.php
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/Auth.php';

if (!Auth::isLoggedIn()) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        
        // Validation: New password length
        if (strlen($new) < 6) {
            header("Location: index.php?msg=" . urlencode("Error: New Password too short (min 6 chars)"));
            exit;
        }

        $uid = Auth::user()['id'];
        
        // Fetch Current Hash Freshly
        $stmt = DB::query("SELECT password_hash FROM users WHERE id = ?", [$uid], "i");
        $userRow = null;
        
        if ($stmt && $res = $stmt->get_result()) {
            $userRow = $res->fetch_assoc();
        }

        if (!$userRow) {
            header("Location: index.php?msg=" . urlencode("Error: User not found"));
            exit;
        }
        
        $currentHash = $userRow['password_hash'] ?? '';
        if (empty($currentHash)) {
            // Edge case: User has no password set (e.g. Oauth? not applicable here). Prevent update via this form?
            // Or allow if they verify nothing? No, dangerous.
            header("Location: index.php?msg=" . urlencode("Error: No password set for account"));
            exit;
        }

        // Verify Current
        if (!password_verify($cur, $currentHash)) {
            error_log("UpdatePass FAIL: User $uid provided incorrect password. InputLen: " . strlen($cur));
            header("Location: index.php?msg=" . urlencode("Error: Invalid Current Password"));
            exit;
        }
        error_log("UpdatePass PASS: User $uid provided correct password.");
        
        // Update
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $update = DB::query("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $uid], "si");
        
        if ($update) {
            header("Location: index.php?msg=" . urlencode("Password Changed Successfully"));
        } else {
            header("Location: index.php?msg=" . urlencode("Error: Dabatase Update Failed"));
        }
        exit;
    }

    if ($action === 'upgrade_teacher') {
        $uid = Auth::user()['id'];
        DB::query("UPDATE users SET role = 'teacher' WHERE id = ?", [$uid], "i");
        
        // Update Session
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user'])) $_SESSION['user']['role'] = 'teacher';
        if (isset($_SESSION['role'])) $_SESSION['role'] = 'teacher';

        header("Location: index.php?msg=" . urlencode("Account upgraded to Teacher"));
        exit;
    }
}
header("Location: index.php");
?>
