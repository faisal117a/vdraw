<?php
// frontend/PyViz/admin/dashboard/Settings.php
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/Auth.php';

// Ensure Admin
// Ensure Admin
if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    die("Access Denied");
}

class AdminSettings {
    
    // Load current app_settings from DB
    public static function loadSettings() {
        $data = [];
        $stmt = DB::query("SELECT setting_key, setting_value FROM app_settings");
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) {
            $data[$row['setting_key']] = $row['setting_value'];
        }
        return $data;
    }

    // Save app_setting
    public static function saveSetting($key, $value) {
        // Upsert
        DB::query("INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()", [$key, $value], "ss");
    }

    // Load .env file content
    public static function loadEnv() {
        $envPath = __DIR__ . '/../../.env';
        if (file_exists($envPath)) {
            return file_get_contents($envPath);
        }
        return "";
    }

    // Save .env file content (Append to env_versions table first for rollback)
    public static function saveEnv($content) {
        $envPath = __DIR__ . '/../../.env';
        error_log("Settings::saveEnv Path: " . $envPath);

        // Backup current env to history (Best Effort)
        try {
            $current = file_exists($envPath) ? file_get_contents($envPath) : "";
            $adminId = Auth::user()['id'];
            
            DB::query("CREATE TABLE IF NOT EXISTS env_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT,
                env_content TEXT,
                version_note VARCHAR(255),
                created_at DATETIME
            )");
    
            DB::query("INSERT INTO env_versions (admin_id, env_content, version_note, created_at) VALUES (?, ?, 'Manual Update via Dashboard', NOW())", [$adminId, $current], "is");
        } catch (Throwable $e) {
            error_log("Settings::saveEnv DB Error: " . $e->getMessage());
        }

        // Write new content
        $result = file_put_contents($envPath, $content);
        if($result === false) {
            error_log("Settings::saveEnv: Failed to write to file.");
            throw new Exception("Failed to write .env file");
        }
        return true;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // DEBUG: Remove after fix
    // error_log("Settings POST Action: $action");


    if ($action === 'save_settings') {
        // Feature Toggles (Destructive if missing)
        $toggles = ['stt_enabled', 'editor_enabled', 'import_enabled', 'new_registrations', 'tracking_enabled'];
        foreach ($toggles as $t) {
            $val = isset($_POST[$t]) ? '1' : '0';
            AdminSettings::saveSetting($t, $val);
        }

        // Limits & Costs (Only save if present, but this is the general form)
        $paramMap = [
            'limit_daily_student' => 'int',
            'limit_daily_teacher' => 'int',
            'limit_daily_teacher_verified' => 'int',
            'limit_monthly_student' => 'int',
            'limit_monthly_teacher' => 'int',
            'max_ip_daily' => 'int',
            'max_ip_monthly' => 'int',
            'audio_tokens_per_sec' => 'int',
            'cost_audio_min' => 'float',
            'cost_text_in_1m' => 'float',
            'cost_text_out_1m' => 'float'
        ];

        foreach ($paramMap as $key => $type) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                AdminSettings::saveSetting($key, $val);
            }
        }
        
        header("Location: index.php?tab=settings&msg=General Settings Saved");
        exit;
    }

    if ($action === 'save_security_settings') {
        $secMap = [
            'captcha_enabled_signup', 'captcha_enabled_login',
            'captcha_site_key', 'captcha_secret_key'
        ];
        
        foreach ($secMap as $key) {
            if (strpos($key, 'enabled') !== false) {
                // Checkbox logic
                $val = isset($_POST[$key]) ? '1' : '0';
                AdminSettings::saveSetting($key, $val);
            } else {
                // Text logic
                $val = $_POST[$key] ?? '';
                AdminSettings::saveSetting($key, $val);
            }
        }
        header("Location: index.php?tab=security&msg=Security Settings Saved");
        exit;
    }

    if ($action === 'save_smtp') {
        $smtpMap = [
            'smtp_host' => 'string',
            'smtp_port' => 'int',
            'smtp_user' => 'string',
            'smtp_pass' => 'string'
        ];
        
        foreach ($smtpMap as $key => $type) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                AdminSettings::saveSetting($key, $val);
            }
        }
        header("Location: index.php?tab=settings&msg=SMTP Configuration Saved");
        exit;
    }

    if ($action === 'save_env') {
        $content = $_POST['env_content'] ?? '';
        AdminSettings::saveEnv($content);
        header("Location: index.php?tab=settings&msg=Env Saved");
        exit;
    }

    if ($action === 'change_password') {
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        
        $uid = Auth::user()['id'];
        $stmt = DB::query("SELECT password_hash FROM users WHERE id = ?", [$uid], "i");
        $u = $stmt ? $stmt->get_result()->fetch_assoc() : null;
        
        if (!$u || !password_verify($cur, $u['password_hash'])) {
             header("Location: index.php?tab=settings&msg=Error: Invalid Current Password");
             exit;
        }
        
        $hash = password_hash($new, PASSWORD_DEFAULT);
        DB::query("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $uid], "si");
        header("Location: index.php?tab=settings&msg=Password Updated Successfully");
        exit;
    }
}

?>
