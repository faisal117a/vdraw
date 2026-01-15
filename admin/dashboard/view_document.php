<?php
// frontend/PyViz/admin/dashboard/view_document.php
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/Auth.php';

// Ensure Admin
if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    die("Access Denied");
}

$id = $_GET['id'] ?? 0;
if (!$id) die("Invalid ID");

// Fetch filename from DB
$stmt = DB::query("SELECT document_path FROM teacher_verification_requests WHERE id = ?", [$id], "i");
$row = $stmt->get_result()->fetch_assoc();

if (!$row) die("Document not found in DB");

$filename = $row['document_path'];

// Path to uploads (outside web root logic from upload script)
// We went up 3 levels from user/dashboard/upload_verification.php
// upload_verification is in frontend/PyViz/user/dashboard/
// so uploads is in d:\xampp\htdocs\uploads\verification_docs\
// current file is frontend/PyViz/admin/dashboard/
// so we need strictly similar logic to locate the dir.

// Let's rely on relative path:
// Current: frontend/PyViz/admin/dashboard/
// Target: frontend/PyViz/user/docs/
$path = __DIR__ . '/../../user/docs/' . $filename;

if (!file_exists($path)) {
    die("File not found on server: " . htmlspecialchars($path));
}

// mime type detection
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if ($ext === 'pdf') $mime = 'application/pdf';
if ($ext === 'jpg' || $ext === 'jpeg') $mime = 'image/jpeg';
if ($ext === 'png') $mime = 'image/png';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));

readfile($path);
?>
