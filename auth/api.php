<?php
// PyViz/auth/api.php
require_once 'Auth.php';
require_once 'Security.php';
Security::checkAccess();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        // Captcha Check
        if(Auth::isCaptchaEnabled('login')) {
             if(!Auth::verifyCaptcha($_POST['g-recaptcha-response'] ?? '')) {
                 echo json_encode(['status'=>'error', 'message'=>'Captcha verification failed.']);
                 exit;
             }
        }
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        echo json_encode(Auth::login($email, $password));
        break;

    case 'register':
        // Terms Check
        if(empty($_POST['terms_agreed'])) {
             echo json_encode(['status'=>'error', 'message'=>'You must agree to the Terms of Use.']);
             exit;
        }

        // Captcha Check
        if(Auth::isCaptchaEnabled('signup')) {
             if(!Auth::verifyCaptcha($_POST['g-recaptcha-response'] ?? '')) {
                 echo json_encode(['status'=>'error', 'message'=>'Captcha verification failed.']);
                 exit;
             }
        }

        $data = [
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'role' => $_POST['role'] ?? 'student',
            'country' => 'US' // Auto-detect later
        ];
        echo json_encode(Auth::register($data));
        break;

    case 'verify_email':
        $email = $_POST['email'] ?? '';
        $code = $_POST['code'] ?? '';
        echo json_encode(Auth::verifyEmail($email, $code));
        break;

    case 'logout':
        echo json_encode(Auth::logout());
        break;
    
    case 'resend_verification':
        $email = $_POST['email'] ?? '';
        if (empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Email is required']);
            exit;
        }
        
        // Find user by email
        $userQ = DB::query("SELECT id, email_verified_at FROM users WHERE email = ?", [$email], "s");
        $userRes = $userQ->get_result();
        
        if ($userRes->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }
        
        $user = $userRes->fetch_assoc();
        
        if (!is_null($user['email_verified_at'])) {
            echo json_encode(['status' => 'error', 'message' => 'Email is already verified']);
            exit;
        }
        
        // Generate new verification code
        $code = rand(100000, 999999);
        DB::query("INSERT INTO user_email_verifications (user_id, code6, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())", [$user['id'], $code], "is");
        
        // Send Email
        try {
            require_once __DIR__ . '/MailService.php';
            MailService::sendVerificationEmail($email, $code);
            error_log("Resend verification: Email sent to $email");
            echo json_encode(['status' => 'success', 'message' => 'Verification code sent']);
        } catch (Exception $e) {
            error_log("Resend verification mail error for $email: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again later.']);
        }
        break;
        
    case 'check_status':
        $user = Auth::user();
        if ($user) {
            echo json_encode(['status' => 'authenticated', 'user' => $user]);
        } else {
            echo json_encode(['status' => 'guest']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
