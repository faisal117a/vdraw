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
