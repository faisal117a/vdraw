<?php
echo "<h2>Migrating User Limits...</h2>";

$conn = new mysqli('127.0.0.1', 'root', '', 'pyviz_db7');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// 1. Add Columns (using direct query)
$cols = [
    'quota_daily' => "INT DEFAULT NULL",
    'quota_monthly' => "INT DEFAULT NULL"
];

foreach ($cols as $col => $def) {
    $res = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN $col $def");
        echo "Added column: $col <br>";
    } else {
        echo "Column $col already exists.<br>";
    }
}

// 2. Fetch Global Settings
$res = $conn->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('limit_daily_student', 'limit_daily_teacher', 'limit_daily_teacher_verified', 'limit_monthly_student', 'limit_monthly_teacher', 'limit_monthly_teacher_verified')");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = (int)$row['setting_value'];
}

$limits = [
    'student' => [
        'd' => $settings['limit_daily_student'] ?? 20,
        'm' => $settings['limit_monthly_student'] ?? 200
    ],
    'teacher' => [
        'd' => $settings['limit_daily_teacher'] ?? 100,
        'm' => $settings['limit_monthly_teacher'] ?? 500
    ],
    'verified_teacher' => [
        'd' => $settings['limit_daily_teacher_verified'] ?? 500,
        'm' => $settings['limit_monthly_teacher_verified'] ?? 2000
    ]
];

// 3. Migrate Users
function migrate($conn, $role, $verified, $d, $m) {
    if ($role === 'teacher') {
        $stmt = $conn->prepare("UPDATE users SET quota_daily = ?, quota_monthly = ? WHERE role = ? AND teacher_verified = ? AND quota_daily IS NULL");
        $stmt->bind_param("iisi", $d, $m, $role, $verified);
    } else {
        $stmt = $conn->prepare("UPDATE users SET quota_daily = ?, quota_monthly = ? WHERE role = ? AND quota_daily IS NULL");
        $stmt->bind_param("iis", $d, $m, $role);
    }
    $stmt->execute();
    echo "Updated " . $stmt->affected_rows . " $role users.<br>";
    $stmt->close();
}

migrate($conn, 'student', 0, $limits['student']['d'], $limits['student']['m']);
migrate($conn, 'teacher', 0, $limits['teacher']['d'], $limits['teacher']['m']);
migrate($conn, 'teacher', 1, $limits['verified_teacher']['d'], $limits['verified_teacher']['m']);

echo "Done.";
