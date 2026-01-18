<?php
// api/changelog.php - Public API to fetch visible change logs
require_once __DIR__ . '/../auth/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'list') {
        // Pagination support
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        // Get total count first
        $countStmt = DB::query("SELECT COUNT(*) as total FROM app_change_log WHERE is_visible = 1");
        $totalCount = 0;
        if ($countStmt && $countRes = $countStmt->get_result()) {
            $totalCount = $countRes->fetch_assoc()['total'];
        }

        // Fetch visible change logs with pagination
        $sql = "SELECT id, title, description, change_type, app_names, button_title, version_tag, created_date 
                FROM app_change_log 
                WHERE is_visible = 1 
                ORDER BY priority_order DESC, created_date DESC
                LIMIT ? OFFSET ?";
        $stmt = DB::query($sql, [$limit, $offset], "ii");
        $logs = [];
        if ($stmt && $res = $stmt->get_result()) {
            while ($row = $res->fetch_assoc()) {
                $row['app_names'] = json_decode($row['app_names'], true) ?: [];
                $logs[] = $row;
            }
        }

        // Get the button title from the latest visible entry (for Home Page button)
        $buttonTitle = "What's New";
        if ($offset === 0 && !empty($logs)) {
            $buttonTitle = $logs[0]['button_title'] ?: "What's New";
        }

        $hasMore = ($offset + count($logs)) < $totalCount;

        echo json_encode([
            'status' => 'success',
            'logs' => $logs,
            'button_title' => $buttonTitle,
            'has_entries' => $totalCount > 0,
            'total' => $totalCount,
            'has_more' => $hasMore
        ]);
    } elseif ($action === 'check') {
        // Quick check if any visible entries exist (for button display)
        $stmt = DB::query("SELECT button_title FROM app_change_log WHERE is_visible = 1 ORDER BY priority_order DESC, created_date DESC LIMIT 1");
        if ($stmt && $res = $stmt->get_result()) {
            $row = $res->fetch_assoc();
            if ($row) {
                echo json_encode(['status' => 'success', 'visible' => true, 'button_title' => $row['button_title']]);
            } else {
                echo json_encode(['status' => 'success', 'visible' => false]);
            }
        } else {
            echo json_encode(['status' => 'success', 'visible' => false]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
