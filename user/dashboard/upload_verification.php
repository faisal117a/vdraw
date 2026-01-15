<?php
// frontend/PyViz/user/dashboard/upload_verification.php
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/Auth.php';

// Ensure Logged In
if (!Auth::isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

$user = Auth::user();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['id_document'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['id_document'];
$allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
$maxSize = 3 * 1024 * 1024; // 3MB

// Validation
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PDF, JPG, PNG allowed.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['status' => 'error', 'message' => 'File too large. Max 3MB.']);
    exit;
}

// Upload Logic
// Storing in 'user/docs/' relative to 'user' folder
// Current file: frontend/PyViz/user/dashboard/
// Target: frontend/PyViz/user/docs/
$uploadDir = __DIR__ . '/../docs/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory.']);
        exit;
    }
}

// Check for upload error code
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Upload Error Code: ' . $file['error']]);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'teacher_ver_' . $user['id'] . '_' . time() . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Record in DB
    try {
        $stmt = DB::query("INSERT INTO teacher_verification_requests (user_id, document_path, status, submitted_at, updated_at) VALUES (?, ?, 'pending', NOW(), NOW())", [$user['id'], $filename], "is");
        if (!$stmt) throw new Exception("DB Insert Failed");
        echo json_encode(['status' => 'success', 'message' => 'Verification request submitted successfully.']);
    } catch (Throwable $e) {
        // Cleanup file if DB fails
        @unlink($targetPath);
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file. Check folder permissions.']);
}
?>
