<?php
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Security.php';

class Gatekeeper {
    public static function protect() {
        Security::checkAccess();
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'path' => '/',
                'lifetime' => 86400 * 7,
                'samesite' => 'Lax'
            ]);
            session_start();
        }

        if (!Auth::isLoggedIn()) {
            // Redirect to root login page
            // We assume app is running at /vdraw-anti-auto or similar
            // We can detect base path or use relative
            $redirect = urlencode($_SERVER['REQUEST_URI']);
            header("Location: ../../login.php?redirect=$redirect");
            exit;
        }

        // Check Verification for Apps
        // If user is accessing an App (not dashboard), require verification
        $script = $_SERVER['SCRIPT_NAME'];
        
        // Allowed paths for unverified users (Dashboard, Verify Actions, Logout)
        $allowed = [
            '/user/dashboard/index.php',
            '/user/dashboard/',
            '/auth/api.php',
            '/logout.php'
        ];

        // Normalizing allowed logic
        $isAllowed = false;
        foreach ($allowed as $path) {
            if (strpos($script, $path) !== false) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
             // Use generic check
             // Note: Auth::canAccess checks is_email_verified
             // We pass a dummy feature name
             if (!Auth::canAccess('any_app')) {
                 // Not verified? Redirect to dashboard to verify
                 header("Location: ../../user/dashboard/?msg=verify_required");
                 exit;
             }
        }
    }
}
