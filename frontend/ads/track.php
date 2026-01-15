<?php
// frontend/ads/track.php
require_once '../../auth/db.php';
require_once '../../auth/Auth.php';

// Lightweight privacy-aware tracking
if (session_status() === PHP_SESSION_NONE) session_start();
// No JSON response needed, just 1x1 pixel or 204 No Content
// Or if called via fetch, return json.

$adId = $_GET['id'] ?? 0;
if (!$adId) exit;

$user = Auth::user();
$userId = $user ? $user['id'] : null;

// Privacy: Hash IP
$ip = $_SERVER['REMOTE_ADDR'];
$ipHash = hash('sha256', $ip . 'SALT_V1');

// Get User Agent info
$ua = $_SERVER['HTTP_USER_AGENT'];
$device = 'desktop';
if (preg_match('/mobile|android|iphone/i', $ua)) $device = 'mobile';
elseif (preg_match('/tablet|ipad/i', $ua)) $device = 'tablet';

// Get Geo if possible (Placeholder, usually needs GeoIP DB)
$country = 'XX'; 
// Example: $country = geoip_country_code_by_name($ip);

$browser = 'Unknown';
if (strpos($ua, 'Chrome') !== false) $browser = 'Chrome';
elseif (strpos($ua, 'Firefox') !== false) $browser = 'Firefox';
elseif (strpos($ua, 'Safari') !== false) $browser = 'Safari';
elseif (strpos($ua, 'Edge') !== false) $browser = 'Edge';

// Record Impression
// Deduplication logic? "Unique Viewers" in reporting implied.
// We record every impression, report aggregates unique/total.

DB::query("INSERT INTO ad_impressions (ad_id, user_id, ip_hash, country, device, os, browser) VALUES (?, ?, ?, ?, ?, 'Unknown', ?)", 
    [$adId, $userId, $ipHash, $country, $device, $browser], "iissss");

header("Content-Type: image/gif");
echo base64_decode("R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"); // 1x1 transparent gif
?>
