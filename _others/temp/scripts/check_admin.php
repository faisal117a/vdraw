<?php
require_once 'auth/db.php';
$stmt = DB::query("SELECT email, password_hash FROM users WHERE role='admin' LIMIT 1");
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo "Admin exists: " . $res->fetch_assoc()['email'];
} else {
    // Create one
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    DB::query("INSERT INTO users (full_name, email, password_hash, role, status, quota_daily, quota_monthly) VALUES ('Admin', 'admin@vdraw.com', ?, 'admin', 'active', 1000, 10000)", [$pass], "s");
    echo "Created admin: admin@vdraw.com / admin123";
}
?>
