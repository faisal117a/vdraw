<?php
// frontend/ads/serve_ads.php
require_once '../../auth/db.php';
require_once '../../auth/Auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Check Global Kill Switch
$gStmt = DB::query("SELECT ads_enabled FROM ad_global_settings WHERE id=1");
if ($gStmt && $gRes = $gStmt->get_result()->fetch_assoc()) {
    if (!$gRes['ads_enabled']) {
        echo json_encode(['status' => 'disabled', 'reason' => 'Global kill switch']);
        exit;
    }
}

// 1. Identify User Context
$user = Auth::user();
$userId = $user ? $user['id'] : null;
$userRole = $user ? $user['role'] : 'guest';
$audience = $user ? 'logged_in' : 'guest';

// 2. Capture Request Data
$appKey = $_POST['app_key'] ?? '';
$placements = $_POST['placements'] ?? []; // Array of placement_keys expected
if (!is_array($placements)) $placements = explode(',', $placements);

if (empty($appKey) || empty($placements)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing params']);
    exit;
}

// 3. Hash IP for privacy
$ip = $_SERVER['REMOTE_ADDR'];
$ipHash = hash('sha256', $ip . 'SALT_V1'); // Simple salt, ideally from env

// 4. Fetch Eligible Ads
// Criteria: 
// - Active
// - Matches App
// - Matches Placement (We fetch all matching any placement, then sort)
// - Matches Audience (or 'both')
// - Date valid

$now = date('Y-m-d H:i:s');
$placeholders = implode("','", array_map('addslashes', $placements));

// Complex query to join maps
$sql = "
SELECT 
    a.ad_id, a.ad_title, a.ad_type, a.network_name, a.ad_code, a.priority, 
    p.placement_key,
    img.image_path
FROM ads a
JOIN ad_placement_map apm ON a.ad_id = apm.ad_id
JOIN ad_placements p ON apm.placement_id = p.placement_id
LEFT JOIN ad_images img ON a.ad_id = img.ad_id
WHERE 
    a.status = 'active'
    AND p.placement_key IN ('$placeholders')
    AND (a.audience = 'both' OR a.audience = ?)
    AND (a.start_datetime IS NULL OR a.start_datetime <= ?)
    AND (a.end_datetime IS NULL OR a.end_datetime >= ?)
    AND (
        (SELECT COUNT(*) FROM ad_apps aa WHERE aa.ad_id = a.ad_id) = 0
        OR EXISTS (SELECT 1 FROM ad_apps aa WHERE aa.ad_id = a.ad_id AND aa.app_key = ?)
    )
ORDER BY a.priority DESC, RAND() 
";

// Logic: We want ONE ad per placement requested? 
// Or return all valid ads for the requested placements to let JS decide?
// Best practice: Server decides winning ad per placement to strictly control logic.

$adsByPlacement = [];

$stmt = DB::query($sql, [$audience, $now, $now, $appKey], "ssss");
if ($stmt && $res = $stmt->get_result()) {
    while ($row = $res->fetch_assoc()) {
        $pk = $row['placement_key'];
        
        // If we already have an ad for this placement, skip (higher priority/random wins first due to Order By)
        if (isset($adsByPlacement[$pk])) continue;
        
        // Format Ad Data
        $adData = [
            'id' => $row['ad_id'],
            'type' => $row['ad_type'],
            'content' => '',
            'track_url' => '',
            'w' => 0, 'h' => 0 // Dynamic
        ];

        // Process Content
        if ($row['ad_type'] === 'sponsor') {
            $adData['image'] = $row['image_path'];
            // Pass raw target URL (ad_code), JS will wrap it in click.php tracking
            $adData['link'] = $row['ad_code']; 
            $adData['track_imp'] = 'ads/track.php?id=' . $row['ad_id'];
        } 
        elseif ($row['ad_type'] === 'google') {
            $adData['html'] = $row['ad_code']; // JS snippet
            // No strict tracking for impression/click on backend for Google usually, or pixel?
            // Phase 11 says "Google Ads ... Untouched".
        }
        
        $adsByPlacement[$pk] = $adData;
        
        // Record Impression asynchronously? 
        // Better: Return pixel URL to JS to fire when rendered.
    }
}

// 5. Check for Push Ads (Popups)
// Separate logic: One push ad per session/page load?
// Check frequency limits.
// For now, return one push ad if available and eligible.

$pushAd = null;
$sqlPush = "
SELECT a.*, ps.*
FROM ads a
JOIN ad_apps aa ON a.ad_id = aa.ad_id
JOIN push_ads_settings ps ON a.ad_id = ps.ad_id
WHERE 
    a.ad_type = 'push' 
    AND a.status = 'active'
    AND aa.app_key = ?
    AND (a.audience = 'both' OR a.audience = ?)
    AND (a.start_datetime IS NULL OR a.start_datetime <= ?)
    AND (a.end_datetime IS NULL OR a.end_datetime >= ?)
ORDER BY a.priority DESC, RAND()
LIMIT 1
";
$stmtP = DB::query($sqlPush, [$appKey, $audience, $now, $now], "ssss");
if ($stmtP && $resP = $stmtP->get_result()) {
    if ($pRow = $resP->fetch_assoc()) {
        // frequency check logic (session based or cookie based)
        // Client side will handle frequency cookie for simplicity? 
        // Or check DB? "Strict frequency control".
        // DB check is expensive on every load. 
        // Lets send ad, JS checks localStorage/cookie? 
        // Phase 11 says "Frequency Cap (per user)"
        
        $pushAd = [
            'id' => $pRow['ad_id'],
            'type' => 'push',
            'display' => $pRow['display_type'], // modal, toast
            'content' => $pRow['ad_code'],
            'delay' => $pRow['close_delay_seconds'],
            'allow_close' => $pRow['allow_immediate_close'],
            'freq' => $pRow['frequency_limit'],
            'track_imp' => 'ads/track.php?id=' . $pRow['ad_id']
        ];
    }
}


echo json_encode([
    'status' => 'success',
    'placements' => $adsByPlacement,
    'push' => $pushAd
]);
?>
