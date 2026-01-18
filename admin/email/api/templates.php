<?php
// admin/email/api/templates.php
require_once __DIR__ . '/../../../auth/Auth.php';
require_once __DIR__ . '/../../../auth/db.php';
require_once __DIR__ . '/../models/EmailTemplate.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            echo json_encode(['status' => 'success', 'data' => EmailTemplate::getAll()]);
            break;

        case 'get':
            $id = $_POST['id'] ?? 0;
            $data = EmailTemplate::get($id);
            if ($data) {
                echo json_encode(['status' => 'success', 'data' => $data]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Not found']);
            }
            break;

        case 'save':
            $id = $_POST['id'] ?? 0;
            $title = $_POST['title'] ?? '';
            $text = $_POST['text_body'] ?? '';
            $html = $_POST['html_body'] ?? '';
            $isActive = (int)($_POST['is_active'] ?? 1);

            if (empty($title)) throw new Exception("Title is required");

            if ($id > 0) {
                // Update
                // Check if we need to increment version (if bodies changed)
                $current = EmailTemplate::get($id);
                $increment = ($current['text_body'] !== $text || $current['html_body'] !== $html);

                EmailTemplate::update($id, $title, $text, $html, $isActive, $increment);
                echo json_encode(['status' => 'success', 'message' => 'Template updated']);
            } else {
                // Create
                EmailTemplate::create($title, $text, $html, $isActive);
                echo json_encode(['status' => 'success', 'message' => 'Template created']);
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            EmailTemplate::delete($id);
            echo json_encode(['status' => 'success', 'message' => 'Template deleted']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
