<?php
// frontend/PyViz/auth/CreditSystem.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Auth.php';

class CreditSystem {
    
    // Defaults (if DB empty)
    const DEFAULT_LIMIT_DAILY_STUDENT = 20;
    const DEFAULT_LIMIT_DAILY_TEACHER = 100;
    const DEFAULT_LIMIT_MONTHLY_STUDENT = 200;
    const DEFAULT_LIMIT_MONTHLY_TEACHER = 2000;
    
    // Default Costs
    const DEFAULT_COST_AUDIO_MIN = 0.06; // $0.06 per minute (~$0.01 per 10s)
    const DEFAULT_COST_TEXT_IN_1M = 0.50;
    const DEFAULT_COST_TEXT_OUT_1M = 1.50;

    // Helper to get settings
    private static function getSettings() {
        $data = [];
        $res = DB::query("SELECT setting_key, setting_value FROM app_settings")->get_result();
        while($row = $res->fetch_assoc()) {
            $data[$row['setting_key']] = $row['setting_value'];
        }
        return $data;
    }

    public static function checkAndReserve($userId, $role, $verifiedTeacher) {
        $settings = self::getSettings();
        
        // Fetch User Custom Quota
        $uRes = DB::query("SELECT quota_daily, quota_monthly FROM users WHERE id = ?", [$userId], "i");
        $uRow = $uRes->get_result()->fetch_assoc();
        $uQuotaD = $uRow['quota_daily'] ?? null;
        $uQuotaM = $uRow['quota_monthly'] ?? null;
        
        $limitDaily = (!is_null($uQuotaD)) ? $uQuotaD : (
            $verifiedTeacher 
            ? ($settings['limit_daily_teacher'] ?? self::DEFAULT_LIMIT_DAILY_TEACHER) 
            : ($settings['limit_daily_student'] ?? self::DEFAULT_LIMIT_DAILY_STUDENT)
        );
            
        $limitMonthly = (!is_null($uQuotaM)) ? $uQuotaM : (
            $verifiedTeacher 
            ? ($settings['limit_monthly_teacher'] ?? self::DEFAULT_LIMIT_MONTHLY_TEACHER) 
            : ($settings['limit_monthly_student'] ?? self::DEFAULT_LIMIT_MONTHLY_STUDENT)
        );

        if ($role === 'admin') {
            $limitDaily = 999999999;
            $limitMonthly = 999999999;
        }
        
        $today = date('Ymd');
        $month = date('Ym');
        
        // ... (rest of check logic remains same, just variable usage) ...
        
        // Check Daily
        $stmt = DB::query("SELECT credits_used FROM user_credit_daily WHERE user_id = ? AND yyyymmdd = ?", [$userId, $today], "is");
        $res = $stmt->get_result();
        $dailyUsed = 0;
        if ($row = $res->fetch_assoc()) {
            $dailyUsed = $row['credits_used'];
        }
        
        // Check Monthly
        $stmt = DB::query("SELECT credits_used FROM user_credit_monthly WHERE user_id = ? AND yyyymm = ?", [$userId, $month], "is");
        $res = $stmt->get_result();
        $monthlyUsed = 0;
        if ($row = $res->fetch_assoc()) {
            $monthlyUsed = $row['credits_used'];
        }

        $overDaily = ($dailyUsed >= $limitDaily);
        $overMonthly = ($monthlyUsed >= $limitMonthly);
        
        if ($overDaily || $overMonthly) {
             // Try to use Reward Credit (Atomic Decrement)
             $upd = DB::query("UPDATE users SET reward_credits = reward_credits - 1 WHERE id = ? AND reward_credits > 0", [$userId], "i");
             if ($upd && $upd->affected_rows > 0) {
                 // Reward used, allow request (bypass limit)
             } else {
                 if ($overDaily) {
                     $dShow = min($dailyUsed, $limitDaily);
                     return ['status' => 'error', 'message' => "Daily limit exceeded ($dShow/$limitDaily)"];
                 }
                 if ($overMonthly) {
                     $mShow = min($monthlyUsed, $limitMonthly);
                     return ['status' => 'error', 'message' => "Monthly limit exceeded ($mShow/$limitMonthly)"];
                 }
             }
        }

        // Start Reserving
        DB::query("INSERT INTO user_credit_daily (user_id, yyyymmdd, credits_used, updated_at) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE credits_used = credits_used + 1, updated_at = NOW()", [$userId, $today], "is");
        DB::query("INSERT INTO user_credit_monthly (user_id, yyyymm, credits_used, updated_at) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE credits_used = credits_used + 1, updated_at = NOW()", [$userId, $month], "is");
        DB::query("INSERT INTO speech_requests (user_id, created_at, status, credit_charged, user_ip) VALUES (?, NOW(), 'started', 1, ?)", [$userId, $_SERVER['REMOTE_ADDR']??''], "is");
        $requestId = DB::connect()->insert_id;
        
        return ['status' => 'success', 'request_id' => $requestId];
    }

    public static function refund($requestId, $userId, $stage, $message) {
        // Mark request failed
        DB::query("UPDATE speech_requests SET status = 'failed', failure_stage = ?, failure_message = ?, credit_charged = 0, credit_refunded = 1 WHERE id = ?", [$stage, $message, $requestId], "ssi");
        
        $today = date('Ymd');
        $month = date('Ym');
        
        // Refund Daily
        DB::query("UPDATE user_credit_daily SET credits_used = GREATEST(0, credits_used - 1), credits_refunded = credits_refunded + 1 WHERE user_id = ? AND yyyymmdd = ?", [$userId, $today], "is");
        // Refund Monthly
        DB::query("UPDATE user_credit_monthly SET credits_used = GREATEST(0, credits_used - 1), credits_refunded = credits_refunded + 1 WHERE user_id = ? AND yyyymm = ?", [$userId, $month], "is");
    }

    public static function complete($requestId, $audioSec, $audioTokens, $textIn, $textOut) {
        $settings = self::getSettings();
        
        $costAudioMin = $settings['cost_audio_min'] ?? self::DEFAULT_COST_AUDIO_MIN;
        $costTextIn = $settings['cost_text_in_1m'] ?? self::DEFAULT_COST_TEXT_IN_1M;
        $costTextOut = $settings['cost_text_out_1m'] ?? self::DEFAULT_COST_TEXT_OUT_1M;

        // Calc Costs
        // Audio: Price per Minute
        $audioMinutes = $audioSec / 60;
        $audioCost = $audioMinutes * $costAudioMin;
        
        // Text: Price per 1M
        $textInCost = ($textIn / 1000000) * $costTextIn;
        $textOutCost = ($textOut / 1000000) * $costTextOut;
        
        $totalCost = $audioCost + $textInCost + $textOutCost;
        
        // Update Request
        DB::query("UPDATE speech_requests SET status = 'llm_ok', audio_seconds = ? WHERE id = ?", [$audioSec, $requestId], "di");
        
        // Log Cost
        $res = DB::query("SELECT user_id FROM speech_requests WHERE id = ?", [$requestId], "i");
        $userId = $res->get_result()->fetch_assoc()['user_id'];

        $sql = "INSERT INTO speech_token_cost_log 
        (speech_request_id, user_id, created_at, audio_tokens_in, text_tokens_in, text_tokens_out, 
        audio_cost_per_1m, text_cost_per_1m_in, text_cost_per_1m_out, 
        estimated_cost_audio, estimated_cost_text_in, estimated_cost_text_out, estimated_cost_total)
        VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Note: For audio_cost_per_1m column, we are storing the per-minute rate now to avoid schema change
        DB::query($sql, 
            [$requestId, $userId, $audioTokens, $textIn, $textOut, 
             $costAudioMin, $costTextIn, $costTextOut,
             $audioCost, $textInCost, $textOutCost, $totalCost], 
            "iiiiiddddddd"
        );
        
        // Update IP tracking
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $m = date('Ym');
        DB::query("INSERT INTO ip_usage_monthly (user_ip, yyyymm, speech_count, tokens_total_est, cost_total_est, updated_at)
        VALUES (?, ?, 1, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE speech_count = speech_count + 1, tokens_total_est = tokens_total_est + VALUES(tokens_total_est), cost_total_est = cost_total_est + VALUES(cost_total_est), updated_at = NOW()",
        [$ip, $m, $audioTokens+$textIn+$textOut, $totalCost],
        "sidd");
    }
}
?>
