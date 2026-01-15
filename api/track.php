<?php
// api/track.php

require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../auth/TrackingService.php';

$conn = DB::connect(); // Ensure DB connection
// Check Global Kill Switch
$chk = DB::query("SELECT setting_value FROM app_settings WHERE setting_key = 'tracking_enabled'");
if ($chk && $r = $chk->get_result()->fetch_assoc()) {
    if ($r['setting_value'] === '0') {
         echo json_encode(['status' => 'disabled']);
         exit;
    }
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
    exit;
}

// User Context
$user = Auth::user();

// Build tracking payload
$data = [
    'user_id' => $user['id'] ?? null,
    'user_type' => $user['role'] ?? 'guest',
    'country_code' => $user['country_code'] ?? ($input['meta']['country'] ?? null),
    
    'app_name' => $input['app_name'] ?? 'Unknown',
    'event_type' => $input['event_type'] ?? 'visit',
    'event_name' => $input['event_name'] ?? 'Unknown',
    'meta' => $input['meta'] ?? [],
    'session_id' => session_id(), 
    
    'ip' => $_SERVER['REMOTE_ADDR'],
    // Use client provided OS/Device if available, otherwise User-Agent sniffing
    'os' => $input['meta']['os'] ?? 'Unknown',
    'device_type' => 'desktop' 
];

// Simple UA parse fallback for OS/Device
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    if (stripos($ua, 'mobile') !== false) $data['device_type'] = 'mobile';
    if (stripos($ua, 'tablet') !== false) $data['device_type'] = 'tablet';
    
    if ($data['os'] === 'Unknown') {
        if (stripos($ua, 'windows') !== false) $data['os'] = 'Windows';
        elseif (stripos($ua, 'macintosh') !== false || stripos($ua, 'mac os') !== false) $data['os'] = 'MacOS';
        elseif (stripos($ua, 'inux') !== false) $data['os'] = 'Linux';
        elseif (stripos($ua, 'android') !== false) $data['os'] = 'Android';
        elseif (stripos($ua, 'iphone') !== false || stripos($ua, 'ipad') !== false) $data['os'] = 'iOS';
    }
}

// Dispatch to Service
TrackingService::logEvent($data);

echo json_encode(['status' => 'ok']);
?>
