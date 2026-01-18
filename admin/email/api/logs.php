<?php
// admin/email/api/logs.php
require_once __DIR__ . '/../../../auth/Auth.php';
require_once __DIR__ . '/../../../auth/db.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $sql = "SELECT l.id, b.title as batch_title, t.template_title, l.started_at, l.ended_at, l.total_emails, l.sent_count, l.failed_count 
                    FROM email_logs l 
                    LEFT JOIN email_batches b ON l.batch_id=b.bid 
                    LEFT JOIN email_templates t ON l.template_id=t.tid 
                    ORDER BY l.created_at DESC LIMIT 100";
            $res = DB::query($sql)->get_result();
            echo json_encode(['status' => 'success', 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
            break;

        case 'get':
            $id = $_POST['id'] ?? 0;
            $res = DB::query("SELECT * FROM email_logs WHERE id = ?", [$id], "i")->get_result();
            if ($row = $res->fetch_assoc()) {
                echo json_encode(['status' => 'success', 'data' => $row]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Log not found']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
