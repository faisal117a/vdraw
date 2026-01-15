<?php
// auth/TrackingService.php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/PointsSystem.php';

class TrackingService {

    public static function isTrackingEnabled() {
        $res = DB::query("SELECT setting_value FROM app_settings WHERE setting_key = 'tracking_enabled'");
        if ($res && $res->get_result()->num_rows > 0) {
            $row = $res->get_result()->fetch_assoc();
            return $row['setting_value'] === '1';
        }
        return true; 
    }

    public static function getLiveUsers($minutes = 5) {
        $threshold = date('Y-m-d H:i:s', strtotime("-$minutes minutes"));
        $sql = "SELECT u.email, u.full_name, s.app_name, s.country_code, s.last_heartbeat 
                FROM tracking_live_sessions s
                JOIN users u ON s.user_id = u.id
                WHERE s.last_heartbeat >= ?
                ORDER BY s.last_heartbeat DESC";
        
        $stmt = DB::query($sql, [$threshold], 's');
        if(!$stmt) return [];
        
        $res = $stmt->get_result();
        if(!$res) return [];

        $users = [];
        while($r = $res->fetch_assoc()) {
            $users[] = $r;
        }
        return $users;
    }

    // $data keys: user_id, user_type, app_name, event_type, event_name, country_code, ip, os, device_type, session_id, meta
    public static function logEvent($data) {
        if (!self::isTrackingEnabled()) return;

        $user_id = $data['user_id'] ?? null;
        $user_type = $data['user_type'] ?? 'guest';
        $app_name = $data['app_name'] ?? 'Unknown';
        $event_type = $data['event_type'] ?? 'visit';
        $event_name = $data['event_name'] ?? 'Generic';
        $country = $data['country_code'] ?? null;
        $ip = $data['ip'] ?? $_SERVER['REMOTE_ADDR'];
        $os = $data['os'] ?? 'Unknown';
        $device = $data['device_type'] ?? 'desktop';
        $session_id = $data['session_id'] ?? session_id();
        $meta = isset($data['meta']) ? json_encode($data['meta']) : null;

        $ip_hash = hash('sha256', $ip);

        // 1. Insert into tracking_events (Cold Storage)
        $sql = "INSERT INTO tracking_events 
                (user_id, user_type, app_name, event_type, event_name, country_code, ip_hash, os, device_type, session_id, meta)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        DB::query($sql, 
            [$user_id, $user_type, $app_name, $event_type, $event_name, $country, $ip_hash, $os, $device, $session_id, $meta], 
            'issssssssss'
        );

        // 2. Handle Document Tracking (Report 2)
        if ($event_type === 'document' && !empty($data['meta'])) {
            self::logDocumentEvent($data, $ip_hash);
        }

        // 3. Handle Live Session (Hot Storage)
        // Update presence for ALL events to keep them 'live'
        self::updateLiveSession($session_id, $user_id, $user_type, $app_name, $country);

        // 4. Handle Points (Rewards)
        if ($event_type === 'button') {
            PointsSystem::addPoints($user_id, 1, "Button Click: $event_name ($app_name)");
        }
    }

    private static function logDocumentEvent($data, $ip_hash) {
        $meta = $data['meta'];
        $level = $meta['level_name'] ?? 'Unknown';
        $chapter = $meta['chapter_name'] ?? 'Unknown';
        $doc = $meta['document_name'] ?? 'Unknown';

        $sql = "INSERT INTO tracking_documents
                (user_id, user_type, app_name, level_name, chapter_name, document_name, country_code, ip_hash)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        DB::query($sql, 
            [$data['user_id'], $data['user_type'], $data['app_name'], $level, $chapter, $doc, $data['country_code'], $ip_hash],
            'isssssss'
        );
    }

    private static function updateLiveSession($session_id, $user_id, $user_type, $app_name, $country) {
        if (!$session_id || !$user_id) return; // Only track logged-in users in live table? Spec says "user_id nullable for guests" but live table has user_id NOT NULL usually?
        // Checking schema: tracking_live_sessions.user_id is BIGINT NOT NULL. 
        // So we only track logged in users in live sessions table as per schema.
        
        $sql = "INSERT INTO tracking_live_sessions 
                (session_id, user_id, user_type, app_name, country_code, last_heartbeat)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                app_name = VALUES(app_name), 
                last_heartbeat = NOW()"; // Keep country constant or update? Maybe they moved?

        DB::query($sql, [$session_id, $user_id, $user_type, $app_name, $country], 'sisss');
    }
}


