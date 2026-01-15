<?php
// frontend/PyViz/auth/Security.php
require_once __DIR__ . '/db.php';

class Security {
    
    public static function checkAccess() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if ($ip === '::1') $ip = '127.0.0.1';
        
        // 0. Cleanup Expired Auto-Blocks
        DB::query("DELETE FROM blocked_ips WHERE block_type='auto' AND expires_at < NOW()");

        // 1. Check IP Block
        $stmt = DB::query("SELECT reason, expires_at FROM blocked_ips WHERE ip_address = ? LIMIT 1", [$ip], "s");
        if ($stmt && $res=$stmt->get_result()) {
            if ($row = $res->fetch_assoc()) {
                self::deny("Access Denied. Your IP ($ip) is blocked. Reason: " . htmlspecialchars($row['reason']));
            }
        }
        
        // 2. Check Country Block
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['country_code'])) {
            $cc = $_SESSION['country_code'];
            $stmt = DB::query("SELECT reason FROM blocked_countries WHERE country_code = ?", [$cc], "s");
            if ($stmt && $res=$stmt->get_result()) {
                if ($row = $res->fetch_assoc()) {
                    self::deny("Access Denied. Your country ($cc) is blocked.");
                }
            }
        }
    }
    
    private static function deny($msg) {
        http_response_code(403);
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['status' => 'error', 'message' => $msg]);
        } else {
            echo "<!DOCTYPE html><html><body style='background:#0f172a;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;'><div style='text-align:center;padding:40px;border:1px solid #334155;border-radius:10px;background:#1e293b;'><h1 style='color:#ef4444;'>🚫 Access Blocked</h1><p>$msg</p></div></body></html>";
        }
        exit;
    }
    
    // Admin Functions
    public static function blockIP($ip, $reason = 'Manual Block', $type='manual') {
        $expires = null;
        if($type === 'auto') {
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        }
        DB::query("INSERT INTO blocked_ips (ip_address, reason, block_type, expires_at, created_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE reason=VALUES(reason), block_type=VALUES(block_type), expires_at=VALUES(expires_at)", [$ip, $reason, $type, $expires], "ssss");
    }
    
    public static function unblockIP($ip) {
        DB::query("DELETE FROM blocked_ips WHERE ip_address = ?", [$ip], "s");
    }

    public static function checkIPUsage($ip) {
        if ($ip === '::1') $ip = '127.0.0.1';
        
        // Get Limits
        $maxDaily = 100; $maxMonthly = 2000;
        $res = DB::query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('max_ip_daily', 'max_ip_monthly')");
        if($res && $r=$res->get_result()) {
            while($row=$r->fetch_assoc()) {
                if($row['setting_key']=='max_ip_daily') $maxDaily = (int)$row['setting_value'];
                if($row['setting_key']=='max_ip_monthly') $maxMonthly = (int)$row['setting_value'];
            }
        }
        
        // Check Daily
        $daily = DB::query("SELECT COUNT(*) c FROM user_activity_log WHERE ip_address = ? AND created_at >= CURDATE()", [$ip], "s")->get_result()->fetch_assoc()['c'];
        if($daily > $maxDaily) {
            self::blockIP($ip, "Auto Block: Exceeded Daily Limit ($daily/$maxDaily)", 'auto');
            return false;
        }

        // Check Monthly
        $monthly = DB::query("SELECT COUNT(*) c FROM user_activity_log WHERE ip_address = ? AND created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')", [$ip], "s")->get_result()->fetch_assoc()['c'];
        if($monthly > $maxMonthly) {
            self::blockIP($ip, "Auto Block: Exceeded Monthly Limit ($monthly/$maxMonthly)", 'auto');
            return false;
        }
        return true;
    }
    
    public static function getBlockedIPs() {
        $data = [];
        $stmt = DB::query("SELECT * FROM blocked_ips ORDER BY created_at DESC");
        if ($stmt && $res=$stmt->get_result()) {
            while($row = $res->fetch_assoc()) $data[] = $row;
        }
        return $data;
    }
}
?>
