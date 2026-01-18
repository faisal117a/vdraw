<?php
// admin/dashboard/changelog_actions.php
require_once __DIR__ . '/../../auth/Auth.php';
require_once __DIR__ . '/../../auth/db.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $sql = "SELECT * FROM app_change_log ORDER BY priority_order DESC, created_date DESC";
            $stmt = DB::query($sql);
            $logs = [];
            if ($stmt && $res = $stmt->get_result()) {
                while ($row = $res->fetch_assoc()) {
                    $row['app_names'] = json_decode($row['app_names'], true) ?: [];
                    $logs[] = $row;
                }
            }
            echo json_encode(['status' => 'success', 'logs' => $logs]);
            break;

        case 'get':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = DB::query("SELECT * FROM app_change_log WHERE id = ?", [$id], "i");
            if ($stmt && $res = $stmt->get_result()) {
                $log = $res->fetch_assoc();
                if ($log) {
                    $log['app_names'] = json_decode($log['app_names'], true) ?: [];
                    echo json_encode(['status' => 'success', 'log' => $log]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Not found']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Query failed']);
            }
            break;

        case 'save':
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = $_POST['description'] ?? '';
            $changeType = $_POST['change_type'] ?? 'improvement';
            $appNames = $_POST['app_names'] ?? [];
            if (!is_array($appNames)) $appNames = explode(',', $appNames);
            $appNamesJson = json_encode(array_values(array_filter($appNames)));
            $buttonTitle = trim($_POST['button_title'] ?? "What's New");
            $isVisible = isset($_POST['is_visible']) ? 1 : 0;
            $versionTag = trim($_POST['version_tag'] ?? '') ?: null;
            $priorityOrder = (int)($_POST['priority_order'] ?? 0);
            $createdDate = $_POST['created_date'] ?? date('Y-m-d');
            $createdBy = $_SESSION['user_id'] ?? null;

            if (empty($title)) {
                echo json_encode(['status' => 'error', 'message' => 'Title is required']);
                exit;
            }

            if ($id > 0) {
                // Update
                $sql = "UPDATE app_change_log SET 
                    title = ?, description = ?, change_type = ?, app_names = ?, 
                    button_title = ?, is_visible = ?, version_tag = ?, priority_order = ?, 
                    created_date = ?, updated_at = NOW()
                    WHERE id = ?";
                DB::query($sql, [
                    $title,
                    $description,
                    $changeType,
                    $appNamesJson,
                    $buttonTitle,
                    $isVisible,
                    $versionTag,
                    $priorityOrder,
                    $createdDate,
                    $id
                ], "sssssisisi");
                echo json_encode(['status' => 'success', 'message' => 'Change log updated']);
            } else {
                // Insert
                $sql = "INSERT INTO app_change_log 
                    (title, description, change_type, app_names, button_title, is_visible, version_tag, priority_order, created_date, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                DB::query($sql, [
                    $title,
                    $description,
                    $changeType,
                    $appNamesJson,
                    $buttonTitle,
                    $isVisible,
                    $versionTag,
                    $priorityOrder,
                    $createdDate,
                    $createdBy
                ], "sssssisisi");
                echo json_encode(['status' => 'success', 'message' => 'Change log created']);
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
                exit;
            }
            DB::query("DELETE FROM app_change_log WHERE id = ?", [$id], "i");
            echo json_encode(['status' => 'success', 'message' => 'Change log deleted']);
            break;

        case 'toggle_visibility':
            $id = (int)($_POST['id'] ?? 0);
            $visible = (int)($_POST['is_visible'] ?? 0);
            DB::query("UPDATE app_change_log SET is_visible = ?, updated_at = NOW() WHERE id = ?", [$visible, $id], "ii");
            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
