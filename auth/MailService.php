<?php
// frontend/PyViz/auth/MailService.php
require_once __DIR__ . '/SimpleSMTP.php';
require_once __DIR__ . '/db.php';

class MailService {
    
    private static function getSMTPSettings() {
        // Load from app_settings or .env
        // For now, let's assume valid settings are needed.
        // We can create a dummy setup or try to read from .env if mapped.
        // For Phase 10 spec: "Configured from admin panel".
        
        $settings = [];
        $res = DB::query("SELECT setting_key, setting_value FROM app_settings")->get_result();
        while($row = $res->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return [
            'host' => $settings['smtp_host'] ?? '',
            'port' => $settings['smtp_port'] ?? 587,
            'user' => $settings['smtp_user'] ?? '',
            'pass' => $settings['smtp_pass'] ?? ''
        ];
    }
    
    public static function send($to, $subject, $htmlBody) {
        $conf = self::getSMTPSettings();
        if (empty($conf['host']) || empty($conf['user'])) {
            // Log that email was attempted but not sent due to missing config
            error_log("MailService: Missing SMTP config. Would send to $to: $subject");
            return false;
        }

        $smtp = new SimpleSMTP($conf['host'], $conf['port'], $conf['user'], $conf['pass']);
        return $smtp->send($to, $subject, $htmlBody);
    }
    
    // Templates
    public static function sendVerificationEmail($to, $code) {
        $subject = "Verify your Vdraw Account";
        $host = $_SERVER['HTTP_HOST'];
        $body = "
        <div style='font-family: sans-serif; padding: 20px; background: #f8fafc;'>
            <div style='background: white; padding: 20px; border-radius: 8px; max-width: 500px; margin: 0 auto; border: 1px solid #e2e8f0;'>
                <h2 style='color: #2563eb;'>Vdraw Verification</h2>
                <p>Welcome to Vdraw! Please verify your email address to access all features.</p>
                <div style='background: #eff6ff; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; font-weight: bold; color: #1e3a8a; margin: 20px 0;'>
                    $code
                </div>
                <p style='text-align: center; margin-bottom: 20px;'>
                    <a href='http://$host/verify_email.php?email=$to' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Verify Email</a>
                </p>
                <p>Or click here: <a href='http://$host/verify_email.php?email=$to'>http://$host/verify_email.php?email=$to</a></p>
                <p>This code expires in 30 minutes.</p>
            </div>
        </div>
        ";
        return self::send($to, $subject, $body);
    }

    public static function sendPasswordReset($to, $code) {
        $subject = "Reset your Password";
        $body = "
        <div style='font-family: sans-serif; padding: 20px; background: #f8fafc;'>
            <div style='background: white; padding: 20px; border-radius: 8px; max-width: 500px; margin: 0 auto; border: 1px solid #e2e8f0;'>
                <h2 style='color: #dc2626;'>Reset Password</h2>
                <p>Use the code below to reset your Vdraw account password.</p>
                <div style='background: #fef2f2; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; font-weight: bold; color: #991b1b; margin: 20px 0;'>
                    $code
                </div>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
        </div>
        ";
        return self::send($to, $subject, $body);
    }
}
?>
