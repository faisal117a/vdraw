<?php
// admin/email/api/smtp.php
require_once __DIR__ . '/../../../auth/Auth.php';
require_once __DIR__ . '/../../../auth/db.php';
require_once __DIR__ . '/../models/SMTPProfile.php';
require_once __DIR__ . '/../models/VDSMTP.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            echo json_encode(['status' => 'success', 'data' => SMTPProfile::getAll()]);
            break;

        case 'get':
            $id = $_POST['id'] ?? 0;
            $data = SMTPProfile::get($id);
            if ($data) {
                echo json_encode(['status' => 'success', 'data' => $data]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Not found']);
            }
            break;

        case 'save':
            $id = $_POST['id'] ?? 0;
            $name = $_POST['profile_name'] ?? '';
            $host = $_POST['smtp_host'] ?? '';
            $port = (int)$_POST['smtp_port'];
            $user = $_POST['smtp_username'] ?? '';
            $pass = $_POST['smtp_password'] ?? ''; // Might be empty if updating without change
            $senderEmail = $_POST['sender_email'] ?? '';
            $senderName = $_POST['sender_name'] ?? '';
            $enc = $_POST['encryption'] ?? 'tls';
            $isDefault = (int)($_POST['is_default'] ?? 0);

            if ($id > 0) {
                SMTPProfile::update($id, $name, $host, $port, $user, $pass, $senderEmail, $senderName, $enc, $isDefault);
                echo json_encode(['status' => 'success', 'message' => 'Profile updated']);
            } else {
                if (empty($pass)) throw new Exception("Password required for new profile");
                SMTPProfile::create($name, $host, $port, $user, $pass, $senderEmail, $senderName, $enc, $isDefault);
                echo json_encode(['status' => 'success', 'message' => 'Profile created']);
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            SMTPProfile::delete($id);
            echo json_encode(['status' => 'success', 'message' => 'Profile deleted']);
            break;

        case 'test':
            $host = $_POST['smtp_host'] ?? '';
            $port = (int)$_POST['smtp_port'];
            $user = $_POST['smtp_username'] ?? '';
            $pass = $_POST['smtp_password'] ?? '';
            $senderEmail = $_POST['sender_email'] ?? '';
            $senderName = $_POST['sender_name'] ?? '';
            $enc = $_POST['encryption'] ?? 'tls';

            // If ID provided and pass empty, fetch from DB
            $id = $_POST['id'] ?? 0;
            if ($id > 0 && empty($pass)) {
                $p = SMTPProfile::get($id);
                if ($p) $pass = $p['smtp_password'];
            }

            $testTo = Auth::user()['email'];

            $smtp = new VDSMTP($host, $port, $user, $pass, $enc, $senderEmail, $senderName);
            // $smtp->setDebug(true); // Can capture debug log to return

            $logBuffer = [];
            $smtp->setDebug(true, function ($msg) use (&$logBuffer) {
                $logBuffer[] = $msg;
            });

            if ($smtp->send($testTo, "SMTP Test from VDraw", "This is a test email to verify SMTP configuration.")) {
                echo json_encode(['status' => 'success', 'message' => "Test email sent to $testTo", 'logs' => $logBuffer]);
            } else {
                echo json_encode(['status' => 'error', 'message' => "Failed: " . $smtp->getLastError(), 'logs' => $logBuffer]);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
