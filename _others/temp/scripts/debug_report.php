<?php
// Debug script to test report generation logic (Without Auth/HTTP)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/auth/db.php';
require_once __DIR__ . '/auth/TrackingService.php';

echo "--- START DEBUG REPORT ---\n";

// Mock params
$p15_params = [];
$p15_types = "";
$wVisits = "1=1";
$wButtons = "1=1";
$wDocs = "1=1";
$wStr = "1=1"; // For users table

$stats = [];

echo "1. Rankings... ";
$rankings = ['daily'=>[], 'weekly'=>[], 'monthly'=>[]];
try {
    $stmtD = DB::query("SELECT app_name, COUNT(*) as c FROM tracking_events WHERE event_type='visit' AND created_at >= NOW() - INTERVAL 1 DAY GROUP BY app_name ORDER BY c DESC LIMIT 5");
    if($stmtD && $rD=$stmtD->get_result()) while($r=$rD->fetch_assoc()) $rankings['daily'][] = $r;
    echo "OK (Daily: ".count($rankings['daily']).")\n";
} catch(Exception $e) { echo "FAIL: ".$e->getMessage()."\n"; }
$stats['rankings'] = $rankings;

echo "2. Daily Trends... ";
try {
    $dailyStats = [];
    $qTrend = "SELECT DATE(created_at) as log_date, app_name, COUNT(*) as total_visits FROM tracking_events WHERE $wVisits GROUP BY log_date, app_name ORDER BY log_date DESC LIMIT 5";
    $stmtTrend = DB::query($qTrend, $p15_params, $p15_types);
    if ($stmtTrend && $resTrend = $stmtTrend->get_result()) {
        while($r=$resTrend->fetch_assoc()) $dailyStats[] = $r;
    }
    echo "OK (Rows: ".count($dailyStats).")\n";
} catch(Exception $e) { echo "FAIL: ".$e->getMessage()."\n"; }
$stats['daily_trends'] = $dailyStats;

echo "3. Rewards... ";
try {
    $rewards = ['total'=>0, 'top_earners'=>[]];
    $stmtR = DB::query("SELECT SUM(points) as t FROM user_points");
    if($stmtR && $r=$stmtR->get_result()->fetch_assoc()) $rewards['total'] = $r['t'] ?? 0;

    $stmtRE = DB::query("SELECT u.email, p.points FROM user_points p JOIN users u ON p.user_id = u.id ORDER BY p.points DESC LIMIT 5");
    if($stmtRE && $rE=$stmtRE->get_result()) while($r=$rE->fetch_assoc()) $rewards['top_earners'][] = $r;
    echo "OK (Total: ".$rewards['total'].")\n";
} catch(Exception $e) { echo "FAIL: ".$e->getMessage()."\n"; }
$stats['rewards'] = $rewards;

echo "4. Live Users... ";
try {
    $live = count(TrackingService::getLiveUsers());
    echo "OK ($live)\n";
} catch(Exception $e) { echo "FAIL: ".$e->getMessage()."\n"; }

echo "--- JSON OUTPUT ---\n";
echo json_encode($stats, JSON_PRETTY_PRINT);
?>
