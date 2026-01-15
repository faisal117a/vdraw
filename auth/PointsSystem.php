<?php
// auth/PointsSystem.php

require_once __DIR__ . '/db.php';

class PointsSystem {

    public static function isEnabled() {
        $stmt = DB::query("SELECT setting_value FROM admin_settings WHERE setting_key = 'points_enabled'");
        if ($stmt) {
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                return $row['setting_value'] === '1';
            }
        }
        return true; 
    }

    public static function addPoints($userId, $amount, $reason) {
        if (!self::isEnabled()) return false;
        if (!$userId) return false;

        $conn = DB::connect();
        
        // 1. Update/Insert user_points
        $sql = "INSERT INTO user_points (user_id, points) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE points = points + VALUES(points)";
        if (!DB::query($sql, [$userId, $amount], 'ii')) {
            return false;
        }

        // 2. Log transaction
        $logSql = "INSERT INTO user_point_logs (user_id, action, points, related_event) VALUES (?, 'earn', ?, ?)";
        DB::query($logSql, [$userId, $amount, $reason], 'iis');

        return true;
    }

    public static function getBalance($userId) {
        $stmt = DB::query("SELECT points FROM user_points WHERE user_id = ?", [$userId], 'i');
        if ($stmt) {
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                return (int)$row['points'];
            }
        }
        return 0;
    }

    public static function getCredits($userId) {
        $stmt = DB::query("SELECT credits FROM user_credits WHERE user_id = ?", [$userId], 'i');
        if ($stmt) {
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                return (int)$row['credits'];
            }
        }
        return 0;
    }

    public static function redeemCredits($userId) {
        $cost_per_credit = 20;

        $balance = self::getBalance($userId);
        if ($balance < $cost_per_credit) {
            return ['status' => 'error', 'message' => 'Insufficient points'];
        }

        $conn = DB::connect();
        $conn->begin_transaction();

        try {
            // Deduct points
            $deductSql = "UPDATE user_points SET points = points - ? WHERE user_id = ?";
            DB::query($deductSql, [$cost_per_credit, $userId], 'ii');

            // Log point deduction
            $logSql = "INSERT INTO user_point_logs (user_id, action, points, related_event) VALUES (?, 'redeem', ?, 'Credit Redemption')";
            DB::query($logSql, [$userId, -$cost_per_credit], 'ii');

            // Add Credit
            $creditSql = "INSERT INTO user_credits (user_id, credits) VALUES (?, 1) 
                          ON DUPLICATE KEY UPDATE credits = credits + 1";
            DB::query($creditSql, [$userId], 'i');

            // Log credit addition
            $creditLogSql = "INSERT INTO user_credit_logs (user_id, credits_added, points_consumed) VALUES (?, 1, ?)";
            DB::query($creditLogSql, [$userId, $cost_per_credit], 'ii');

            $conn->commit();
            return ['status' => 'success', 'new_balance' => $balance - $cost_per_credit];

        } catch (Exception $e) {
            $conn->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
?>
