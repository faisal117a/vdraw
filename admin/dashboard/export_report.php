<?php
// frontend/PyViz/admin/dashboard/export_report.php
ob_start(); // Start buffering to catch any whitespace includes
ini_set('display_errors', 0); // Disable error printing to file

require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/Auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!Auth::isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    ob_end_clean();
    die("Access Denied");
}

$type = $_GET['type'] ?? 'users';
$filename = "pyviz_report_" . $type . "_" . date('Ymd') . ".csv";

// Clear buffer before sending headers
ob_end_clean();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

try {
    if ($type === 'users') {
        fputcsv($output, ['ID', 'Full Name', 'Email', 'Role', 'Status', 'Verified', 'Phone', 'Created At']);
        $stmt = DB::query("SELECT id, full_name, email, role, status, is_email_verified, phone, created_at FROM users");
        if ($stmt) {
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()) {
                fputcsv($output, $row);
            }
        }
    } elseif ($type === 'usage') {
        fputcsv($output, ['Request ID', 'User ID', 'Tokens Audio', 'Tokens Text In', 'Tokens Text Out', 'Cost Total ($)', 'Date']);
        $stmt = DB::query("SELECT speech_request_id, user_id, audio_tokens_in, text_tokens_in, text_tokens_out, estimated_cost_total, created_at FROM speech_token_cost_log ORDER BY created_at DESC");
        if ($stmt) {
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()) {
                fputcsv($output, $row);
            }
        }
    }
} catch (Throwable $e) {
    // If error occurs during CSV generation, we can't easily show alert, but we can write error to csv
    fputcsv($output, ['ERROR', $e->getMessage()]);
}

fclose($output);
exit;
?>
