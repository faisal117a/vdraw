<?php
// frontend/ads/click.php
require_once '../../auth/db.php';
require_once '../../auth/Auth.php';

$adId = $_GET['id'] ?? 0;
$url = $_GET['url'] ?? '/';

if ($adId) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $user = Auth::user();
    $userId = $user ? $user['id'] : null;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $ipHash = hash('sha256', $ip . 'SALT_V1');
    
    DB::query("INSERT INTO ad_clicks (ad_id, user_id, ip_hash) VALUES (?, ?, ?)", [$adId, $userId, $ipHash], "iis");
}

header("Location: " . $url);
exit;
?>
