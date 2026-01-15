<?php
// PyViz/auth/Auth.php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'lifetime' => 86400 * 7, // 7 days
        'samesite' => 'Lax'
    ]);
    session_start();
}

class Auth {
    // Check if logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Register User
    public static function register($data) {
        $conn = DB::connect();
        
        $fullName = $data['full_name'];
        $email = $data['email'];
        $password = $data['password'];
        $phone = $data['phone'] ?? '';
        $role = $data['role'] ?? 'student';
        $country = $data['country'] ?? 'XX';
        
        // Check if email exists
        $check = DB::query("SELECT id FROM users WHERE email = ?", [$email], "s");
        if ($check->get_result()->num_rows > 0) {
            return ['status' => 'error', 'message' => 'Email already registered'];
        }

        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Fetch Global Limits for Snapshot
        $st = DB::query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'limit_%'");
        $limits = [];
        $res = $st->get_result();
        while($r = $res->fetch_assoc()) $limits[$r['setting_key']] = (int)$r['setting_value'];
        $st->close();
        
        $limitD = 20; $limitM = 200;
        if ($role === 'teacher') {
            $limitD = $limits['limit_daily_teacher'] ?? 100;
            $limitM = $limits['limit_monthly_teacher'] ?? 500;
        } else {
            $limitD = $limits['limit_daily_student'] ?? 20;
            $limitM = $limits['limit_monthly_student'] ?? 200;
        }
        
        // Insert user with Quotas
        $sql = "INSERT INTO users (full_name, email, password_hash, phone_e164, country_code, role, quota_daily, quota_monthly, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = DB::query($sql, [$fullName, $email, $hash, $phone, $country, $role, $limitD, $limitM], "ssssssii");
        
        if (!$stmt) {
             return ['status' => 'error', 'message' => 'System Error: Could not create user.'];
        }

        if ($stmt->affected_rows > 0) {
            $userId = $stmt->insert_id;
            
            // Generate Verification Code
            $code = rand(100000, 999999);
            DB::query("INSERT INTO user_email_verifications (user_id, code6, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())", [$userId, $code], "is");
            
            // Auto login after register? Or require verification?
            // Phase 10 says "User cannot access restricted features until email is verified".
            // So we can log them in but checks will fail.
            
            // Phase 14: Login MUST be blocked if email_verified != 1
            // Do NOT log them in automatically.
            
            // Send Verification Email
            $emailSent = false;
            $emailError = '';
            try {
                require_once __DIR__ . '/MailService.php';
                MailService::sendVerificationEmail($email, $code);
                $emailSent = true;
                error_log("Registration: Verification email sent successfully to $email");
            } catch (Exception $e) {
                $emailError = $e->getMessage();
                error_log("Registration Mail Error for $email: " . $emailError);
            }
            
            $message = 'Registration successful. Please verify your email.';
            if (!$emailSent) {
                // On production, warn user if email failed
                $isLocalhost = ($_SERVER['SERVER_NAME'] ?? '') === 'localhost' || ($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1';
                if (!$isLocalhost) {
                    $message .= ' (Warning: Email delivery may be delayed. Please try again in a few minutes if you do not receive the code.)';
                }
            }
            
            return ['status' => 'success', 'message' => $message, 'verification_code' => $code, 'email_sent' => $emailSent]; 
        } else {
            return ['status' => 'error', 'message' => 'Registration failed.'];
        }
    }

    // Login User
    public static function login($email, $password) {
        $stmt = DB::query("SELECT id, full_name, password_hash, role, teacher_verified, email_verified_at, status FROM users WHERE email = ?", [$email], "s");
        $res = $stmt->get_result();
        
        if ($res->num_rows === 0) {
            return ['status' => 'error', 'message' => 'Invalid credentials'];
        }
        
        $user = $res->fetch_assoc();
        
        if ($user['status'] !== 'active') {
             $s = $user['status'] ? $user['status'] : 'blocked';
             return ['status' => 'error', 'message' => "Account is $s"];
        }

        // Phase 14: Login MUST be blocked if email_verified != 1
        if (is_null($user['email_verified_at'])) {
            return ['status' => 'error', 'message' => 'Please verify your email before logging in.'];
        }

        if (password_verify($password, $user['password_hash'])) {
            try {
                // Update Last Login
                DB::query("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']], "i");
                
                // Log Activity
                require_once __DIR__ . '/UserActivity.php';
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                if($ip === '::1') $ip = 'Localhost';
                UserActivity::log($user['id'], 'Login', 'User logged in from ' . $ip);
            } catch (Exception $e) {
                error_log("Login Stats Error: " . $e->getMessage());
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['teacher_verified'] = $user['teacher_verified'];
                $_SESSION['is_email_verified'] = !is_null($user['email_verified_at']);
                
                // Fetch country code from this user row? The SELECT above didn't fetch it.
                // Let's fetch it now or include in SELECT.
                $cQ = DB::query("SELECT country_code FROM users WHERE id = ?", [$user['id']], "i");
                if($cR = $cQ->get_result()->fetch_assoc()) {
                     $_SESSION['country_code'] = $cR['country_code'];
                }

                // Log IP logic can go here (Activity Log)
                
                return ['status' => 'success', 'message' => 'Login successful', 'redirect' => self::getRedirectPath($user['role'])];
        } else {
            return ['status' => 'error', 'message' => 'Invalid credentials'];
        }
    }

    // Verify Email
    public static function verifyEmail($email, $code) {
        // Find user by email
        $userQ = DB::query("SELECT id, email_verified_at FROM users WHERE email = ?", [$email], "s");
        $userRes = $userQ->get_result();
        
        if ($userRes->num_rows === 0) {
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        $user = $userRes->fetch_assoc();
        
        if (!is_null($user['email_verified_at'])) {
            return ['status' => 'success', 'message' => 'Email already verified'];
        }
        
        // Check code
        // We check the latest valid code code
        $res = DB::query("SELECT id FROM user_email_verifications WHERE user_id = ? AND code6 = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1", [$user['id'], $code], "ii");
        
        if ($res->get_result()->num_rows > 0) {
            // Valid
            DB::query("UPDATE users SET email_verified_at = NOW(), status = 'active' WHERE id = ?", [$user['id']], "i");
            return ['status' => 'success', 'message' => 'Email verified successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Invalid or expired verification code'];
        }
    }

    public static function logout() {
        session_destroy();
        return ['status' => 'success', 'message' => 'Logged out'];
    }

    public static function user() {
        if (!isset($_SESSION['user_id'])) return null;
        return [
            'id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'verified' => $_SESSION['is_email_verified'] ?? false,
            'country_code' => $_SESSION['country_code'] ?? null
        ];
    }
    
    // Check if feature access is allowed
    public static function canAccess($feature) {
        if (!isset($_SESSION['user_id'])) return false;
        
        // Phase 10: "User cannot access restricted features until email is verified"
        if (empty($_SESSION['is_email_verified'])) {
            // Allow dashboard access maybe? But restricted features are NO.
             return false;
        }
        
        return true;
    }

    private static function getRedirectPath($role) {
        if ($role === 'admin') return 'admin/dashboard/';
        return 'user/dashboard/';
    }

    public static function isCaptchaEnabled($type) {
        // type: login, signup
        $key = 'captcha_enabled_' . $type;
        $res = DB::query("SELECT setting_value FROM app_settings WHERE setting_key = ?", [$key], "s")->get_result();
        if($res->num_rows > 0) {
            return $res->fetch_assoc()['setting_value'] === '1';
        }
        return false;
    }

    public static function getCaptchaSiteKey() {
        $res = DB::query("SELECT setting_value FROM app_settings WHERE setting_key = 'captcha_site_key'")->get_result();
        return $res->num_rows > 0 ? $res->fetch_assoc()['setting_value'] : '';
    }

    public static function verifyCaptcha($token) {
        if (empty($token)) return false;
        
        $res = DB::query("SELECT setting_value FROM app_settings WHERE setting_key = 'captcha_secret_key'")->get_result();
        $secret = $res->num_rows > 0 ? $res->fetch_assoc()['setting_value'] : '';
        
        if (empty($secret)) return true; // Fail safe? Or fail strict? If configured, strict. If not, open? Assume fail if logic called.
        // Actually if logic called, it means enabled. So fail if secret missing.
        if (empty($secret)) return false;

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = ['secret' => $secret, 'response' => $token];
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $json = json_decode($result, true);
        
        return $json['success'] ?? false;
    }
}


