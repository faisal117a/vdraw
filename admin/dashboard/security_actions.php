<?php
// frontend/PyViz/admin/dashboard/security_actions.php
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/Auth.php';
require_once __DIR__ . '/../../auth/Security.php';

// Ensure Admin
if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$ip = $_POST['ip'] ?? '';

if (!$ip && $action !== 'get_ips') {
    echo json_encode(['status' => 'error', 'message' => 'IP is required']);
    exit;
}

if ($action === 'block') {
    $reason = $_POST['reason'] ?? 'Manual Block';
    Security::blockIP($ip, $reason);
    echo json_encode(['status' => 'success', 'message' => "IP $ip blocked."]);
} elseif ($action === 'unblock') {
    Security::unblockIP($ip);
    echo json_encode(['status' => 'success', 'message' => "IP $ip unblocked."]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
