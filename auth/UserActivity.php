<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Security.php';

class UserActivity {
    public static function log($uid, $type, $desc) {
         if(!$uid) return;
         $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
         if($ip === '::1') $ip = '127.0.0.1';
         
         Security::checkIPUsage($ip);

         $country = 'Unknown';

         try {
             DB::query("INSERT INTO user_activity_log (user_id, title, details, ip_address, country, created_at) VALUES (?, ?, ?, ?, ?, NOW())", [$uid, $type, $desc, $ip, $country], "issss");
         } catch(Exception $e) {
             error_log("UserActivity Log Error: " . $e->getMessage());
         }
    }
}
?>
