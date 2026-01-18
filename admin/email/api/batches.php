<?php
// admin/email/api/batches.php
require_once __DIR__ . '/../../../auth/Auth.php';
require_once __DIR__ . '/../../../auth/db.php';
require_once __DIR__ . '/../models/EmailBatch.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            echo json_encode(['status' => 'success', 'data' => EmailBatch::getAll()]);
            break;

        case 'get':
            $id = $_POST['id'] ?? 0;
            $data = EmailBatch::get($id);
            if ($data) {
                // Also get stats if they exist
                $stats = EmailBatch::getSnapshotStats($id);
                $data['stats'] = $stats;
                echo json_encode(['status' => 'success', 'data' => $data]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Not found']);
            }
            break;

        case 'save':
            $id = $_POST['id'] ?? 0;
            $title = $_POST['title'] ?? '';
            $desc = $_POST['description'] ?? '';
            $source = $_POST['email_list_source'] ?? '';

            if (empty($title)) throw new Exception("Title is required");

            if ($id > 0) {
                EmailBatch::update($id, $title, $desc, $source);
                echo json_encode(['status' => 'success', 'message' => 'Batch updated']);
            } else {
                EmailBatch::create($title, $desc, $source);
                echo json_encode(['status' => 'success', 'message' => 'Batch created']);
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            EmailBatch::delete($id);
            echo json_encode(['status' => 'success', 'message' => 'Batch deleted']);
            break;

        case 'search_users':
            $role = $_POST['role_filter'] ?? '';
            $dStart = $_POST['date_start'] ?? null;
            $dEnd = $_POST['date_end'] ?? null;
            $search = $_POST['search'] ?? '';
            $active = ($_POST['active_only'] ?? 'false') === 'true';
            $verified = ($_POST['verified_only'] ?? 'false') === 'true';
            $limit = (int)($_POST['limit'] ?? 100);
            $offset = (int)($_POST['offset'] ?? 0);

            if ($limit === -1) $limit = 100000; // ALL

            $users = EmailBatch::getFilteredUsers($role, $dStart, $dEnd, $search, $active, $verified, $offset, $limit);
            $total = EmailBatch::countFilteredUsers($role, $dStart, $dEnd, $search, $active, $verified);

            echo json_encode(['status' => 'success', 'data' => $users, 'total' => $total]);
            break;

        case 'init_snapshot':
            $id = $_POST['id'] ?? 0;
            $count = EmailBatch::createSnapshot($id);
            echo json_encode(['status' => 'success', 'message' => "Snapshot created with $count emails.", 'count' => $count]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
