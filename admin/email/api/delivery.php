<?php
// admin/email/api/delivery.php
require_once __DIR__ . '/../../../auth/Auth.php';
require_once __DIR__ . '/../../../auth/db.php';
require_once __DIR__ . '/../models/EmailBatch.php';
require_once __DIR__ . '/../models/EmailTemplate.php';
require_once __DIR__ . '/../models/SMTPProfile.php';
require_once __DIR__ . '/../models/VDSMTP.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'process_batch':
            $batchId = (int)($_POST['batch_id'] ?? 0);
            $templateId = (int)($_POST['template_id'] ?? 0);
            $smtpId = (int)($_POST['smtp_id'] ?? 0);
            $limit = (int)($_POST['limit'] ?? 5);

            // 1. Get Resources
            $template = EmailTemplate::get($templateId);
            $smtpProfile = SMTPProfile::get($smtpId); // Decrypts password automatically

            if (!$template || !$smtpProfile) {
                throw new Exception("Invalid Template or SMTP Profile");
            }

            // 2. Setup SMTP
            $smtp = new VDSMTP(
                $smtpProfile['smtp_host'],
                $smtpProfile['smtp_port'],
                $smtpProfile['smtp_username'],
                $smtpProfile['smtp_password'],
                $smtpProfile['encryption'],
                $smtpProfile['sender_email'],
                $smtpProfile['sender_name']
            );

            // 3. Status Check (if resuming, fine. snapshot must exist)
            // Query pending
            $sql = "SELECT i.id, i.email, u.full_name, u.role FROM email_batch_items i LEFT JOIN users u ON i.email = u.email WHERE i.batch_id = ? AND i.status='pending' LIMIT ?";
            $stmt = DB::query($sql, [$batchId, $limit], "ii");
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $logs = [];
            $processed = 0;

            foreach ($items as $item) {
                // Personalize
                $name = $item['full_name'] ?: 'User';
                $email = $item['email'];

                $subject = str_replace(['{name}', '{email}'], [$name, $email], $template['template_title']);
                $html = str_replace(['{name}', '{email}'], [$name, $email], $template['html_body']);
                $text = str_replace(['{name}', '{email}'], [$name, $email], $template['text_body']);

                // If HTML is empty, send Text? VDSMTP supports HTML. SimpleSMTP was HTML only basically.
                // VDSMTP sends multipart/alternative if we improved it, but currently it sends Text OR HTML based on flag.
                // Current VDSMTP sends HTML header.
                // If template has HTML, use it.
                $body = !empty($html) ? $html : nl2br($text);

                $sent = $smtp->send($email, $subject, $body, true);

                if ($sent) {
                    DB::query("UPDATE email_batch_items SET status='sent', sent_at=NOW() WHERE id=?", [$item['id']], "i");
                    $logs[] = [
                        'email' => $email,
                        'status' => 'sent',
                        'msg' => 'Sent ✓ ' . date('d-M-Y h:i A')
                    ];
                } else {
                    $error = $smtp->getLastError();
                    DB::query("UPDATE email_batch_items SET status='failed', error_reason=? WHERE id=?", [$error, $item['id']], "si");
                    $logs[] = [
                        'email' => $email,
                        'status' => 'failed',
                        'msg' => 'Failed ✗ ' . $error
                    ];
                }
                $processed++;
            }

            // Check remaining
            $remaining = 0;
            $cnt = DB::query("SELECT COUNT(*) c FROM email_batch_items WHERE batch_id = ? AND status='pending'", [$batchId], "i")->get_result()->fetch_assoc();
            $remaining = $cnt['c'];

            echo json_encode([
                'status' => 'success',
                'logs' => $logs,
                'processed_count' => $processed,
                'remaining' => $remaining
            ]);
            break;

        case 'save_log':
            $batchId = (int)$_POST['batch_id'];
            $templateId = (int)$_POST['template_id'];
            $smtpId = (int)$_POST['smtp_profile_id'];
            $logText = $_POST['log_text'];
            $startedAt = $_POST['started_at']; // Format must be datetime compatible
            $endedAt = date('Y-m-d H:i:s');
            $total = (int)$_POST['total_emails'];
            $sent = (int)$_POST['sent_count'];
            $failed = (int)$_POST['failed_count'];

            $sql = "INSERT INTO email_logs (batch_id, template_id, smtp_profile_id, log_text, started_at, ended_at, total_emails, sent_count, failed_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            DB::query($sql, [$batchId, $templateId, $smtpId, $logText, $startedAt, $endedAt, $total, $sent, $failed], "iiisssiii");

            echo json_encode(['status' => 'success', 'message' => 'Log saved successfully']);
            break;

        case 'reset_failed':
            // "Resend Failed Emails... uses same batch snapshot"
            // Strategy: Update status 'failed' -> 'pending' for this batch
            $batchId = (int)$_POST['batch_id'];
            DB::query("UPDATE email_batch_items SET status='pending', error_reason=NULL WHERE batch_id=? AND status='failed'", [$batchId], "i");
            $c = DB::query("SELECT ROW_COUNT() as c")->get_result()->fetch_assoc()['c']; // or use affected_rows logic
            echo json_encode(['status' => 'success', 'message' => "Reset $c failed items to pending."]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
