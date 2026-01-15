<?php
// frontend/PyViz/admin/dashboard/user_actions.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/Auth.php';

// Ensure Admin
if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$email = $_POST['email'] ?? '';

if (!$email && !in_array($action, ['get_logs', 'edit_user', 'add_reward', 'get_report_data', 'get_app_metrics'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

$conn = DB::connect();

try {
    // error_log("UserAction Request: Action=$action, Email=$email");

    switch ($action) {
        case 'block':
            DB::query("UPDATE users SET status = 'blocked' WHERE email = ?", [$email], "s");
            echo json_encode(['status' => 'success', 'message' => "User $email blocked."]);
            break;

        case 'unblock':
            DB::query("UPDATE users SET status = 'active' WHERE email = ?", [$email], "s");
            echo json_encode(['status' => 'success', 'message' => "User $email unblocked."]);
            break;

        case 'manual_verify_email':
            DB::query("UPDATE users SET email_verified_at = NOW() WHERE email = ?", [$email], "s");
            echo json_encode(['status' => 'success', 'message' => "Email for $email manually verified."]);
            break;

        case 'verify_teacher':
            // Check if email verified first
            $check = DB::query("SELECT id, email_verified_at FROM users WHERE email = ?", [$email], "s")->get_result()->fetch_assoc();
            if (!$check || empty($check['email_verified_at'])) {
                echo json_encode(['status' => 'error', 'message' => "Cannot verify teacher. User's email is not verified."]);
                exit;
            }

            // Verify and promote
            DB::query("UPDATE users SET teacher_verified = 1, role = 'teacher' WHERE email = ?", [$email], "s");
            
            // Update request
            DB::query("UPDATE teacher_verification_requests SET status = 'approved', updated_at = NOW() WHERE user_id = ? AND status = 'pending'", [$check['id']], "i");
            
            echo json_encode(['status' => 'success', 'message' => "Teacher $email verified manually."]);
            break;

        case 'unverify_teacher':
            // Delete request so user sees "Unverified" and can apply again
            $res = DB::query("SELECT id FROM users WHERE email = ?", [$email], "s")->get_result();
            if($r = $res->fetch_assoc()) {
                 DB::query("DELETE FROM teacher_verification_requests WHERE user_id = ?", [$r['id']], "i");
            }
            DB::query("UPDATE users SET teacher_verified = 0 WHERE email = ?", [$email], "s");
            echo json_encode(['status' => 'success', 'message' => "Teacher $email status set to Unverified (Request Cleared)."]);
            break;

        case 'get_logs':
            $uid = $_POST['user_id'] ?? 0;
            if(!$uid) throw new Exception("User ID required");
            

            // Select actual ip_address, country
            $stmt = DB::query("SELECT title as activity_type, details as description, created_at, ip_address, country FROM user_activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 50", [$uid], "i");
            if($stmt && $res=$stmt->get_result()) {
                while($r=$res->fetch_assoc()) {
                    if($r['ip_address'] === '::1' || $r['ip_address'] === '127.0.0.1') $r['ip_address'] = 'Localhost';
                    $logs[] = $r;
                }
            }

            // 2. Token Usage Logs
            $usage = [];
            $usageQ = "SELECT 
                        audio_tokens_in, 
                        text_tokens_in, 
                        text_tokens_out, 
                        estimated_cost_audio,
                        estimated_cost_text_in,
                        estimated_cost_text_out,
                        estimated_cost_total,
                        created_at 
                       FROM speech_token_cost_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
            $stmt2 = DB::query($usageQ, [$uid], "i");
            if($stmt2 && $res2=$stmt2->get_result()) {
                while($r=$res2->fetch_assoc()) $usage[] = $r;
            }

            echo json_encode(['status'=>'success', 'logs'=>$logs, 'usage'=>$usage]);
            break;

        case 'add_reward':
            $uid = $_POST['user_id'] ?? 0;
            $amount = (int)($_POST['amount'] ?? 0);
            if(!$uid) { echo json_encode(['status'=>'error','message'=>'User ID missing']); exit; }
            if($amount <= 0) { echo json_encode(['status'=>'error','message'=>'Amount must be positive']); exit; }
            
            DB::query("UPDATE users SET reward_credits = COALESCE(reward_credits,0) + ? WHERE id = ?", [$amount, $uid], "ii");
            echo json_encode(['status'=>'success', 'message'=>"Added $amount credits successfully."]);
            break;

        case 'edit_user':
            $uid = $_POST['user_id'] ?? 0;
            $role = $_POST['role'] ?? '';
            $pass = $_POST['password'] ?? '';
            
            if(!$uid) throw new Exception("User ID required");
            if(!$role) throw new Exception("Role required");

            if($pass) {
                 $hash = password_hash($pass, PASSWORD_DEFAULT);
                 DB::query("UPDATE users SET role = ?, password = ? WHERE id = ?", [$role, $hash, $uid], "ssi");
            } else {
                 DB::query("UPDATE users SET role = ? WHERE id = ?", [$role, $uid], "si");
            }
            echo json_encode(['status'=>'success','message'=>'User updated successfully']);
            break;

        case 'get_report_data':
             $page = (int)($_POST['page'] ?? 1);
             $limit = 20; $offset = ($page-1)*$limit;
             $search = $_POST['search'] ?? '';
             $dStart = $_POST['date_start'] ?? null;
             $dEnd = $_POST['date_end'] ?? null;

             // Stats
             $stats = [];
             $stats['total_users'] = DB::query("SELECT COUNT(*) c FROM users")->get_result()->fetch_assoc()['c'];
             $stats['active_users'] = DB::query("SELECT COUNT(*) c FROM users WHERE status='active'")->get_result()->fetch_assoc()['c'];
             $stats['blocked_users'] = DB::query("SELECT COUNT(*) c FROM users WHERE status='blocked'")->get_result()->fetch_assoc()['c'];
             $stats['teachers'] = DB::query("SELECT COUNT(*) c FROM users WHERE role='teacher'")->get_result()->fetch_assoc()['c'];
             $stats['verified_teachers'] = DB::query("SELECT COUNT(*) c FROM users WHERE role='teacher' AND teacher_verified=1")->get_result()->fetch_assoc()['c'];
             
             $tok = DB::query("SELECT 
                SUM(audio_tokens_in) as aud, 
                SUM(text_tokens_in + text_tokens_out) as txt,
                SUM(estimated_cost_audio) as cost_aud,
                SUM(estimated_cost_text_in + estimated_cost_text_out) as cost_txt,
                SUM(estimated_cost_total) as cost_total
                FROM speech_token_cost_log")->get_result()->fetch_assoc();
             $stats['tokens'] = $tok;

             // Geo Stats
             $geo = [];
             $resG = DB::query("SELECT IFNULL(u.country_code, 'Unknown') as cc, SUM(l.audio_tokens_in + l.text_tokens_in + l.text_tokens_out) as toks, SUM(l.estimated_cost_total) as cost FROM speech_token_cost_log l JOIN users u ON l.user_id = u.id GROUP BY u.country_code ORDER BY cost DESC LIMIT 20")->get_result();
             while($r=$resG->fetch_assoc()) $geo[] = $r;
             $stats['geo'] = $geo;

             // IP Stats
             $ips = [];
             $resI = DB::query("SELECT user_ip, SUM(speech_count) as reqs, SUM(tokens_total_est) as toks, SUM(cost_total_est) as cost FROM ip_usage_monthly GROUP BY user_ip ORDER BY cost DESC LIMIT 20")->get_result();
             while($r=$resI->fetch_assoc()) $ips[] = $r;
             $stats['ips'] = $ips;

             // --- PHASE 15 COMPREHENSIVE REPORTING ---

             // Global Filter Construction
             $p15_where = ["1=1"];
             $p15_params = []; $p15_types = "";
             
             if($dStart) { $p15_where[] = "DATE(created_at) >= ?"; $p15_params[] = $dStart; $p15_types.="s"; }
             if($dEnd)   { $p15_where[] = "DATE(created_at) <= ?"; $p15_params[] = $dEnd; $p15_types.="s"; }
             
             // Search Filter (User, Country, Type)
             if($search) {
                 $searchTerm = "%$search%";
                 $p15_where[] = "(country_code LIKE ? OR user_type LIKE ? OR user_id IN (SELECT id FROM users WHERE email LIKE ? OR full_name LIKE ?))";
                 $p15_params[] = $searchTerm; 
                 $p15_params[] = $searchTerm; 
                 $p15_params[] = $searchTerm; 
                 $p15_params[] = $searchTerm; 
                 $p15_types .= "ssss";
             }
             
             $wVisits = implode(" AND ", array_merge($p15_where, ["event_type = 'visit'"]));
             $wButtons = implode(" AND ", array_merge($p15_where, ["event_type = 'button'"]));
             $wDocs    = implode(" AND ", array_merge($p15_where, ["event_type = 'document'"]));
             
             // 1. APPS RANKING (Daily / Weekly / Monthly) - Independent of Date Filter (Snapshot)
             $rankings = ['daily'=>[], 'weekly'=>[], 'monthly'=>[]];
             // Daily (Last 24h)
             $stmtD = DB::query("SELECT app_name, COUNT(*) as c FROM tracking_events WHERE event_type='visit' AND created_at >= NOW() - INTERVAL 1 DAY GROUP BY app_name ORDER BY c DESC LIMIT 5");
             if($stmtD && $rD=$stmtD->get_result()) while($r=$rD->fetch_assoc()) $rankings['daily'][] = $r;
             
             // Weekly (Last 7d)
             $stmtW = DB::query("SELECT app_name, COUNT(*) as c FROM tracking_events WHERE event_type='visit' AND created_at >= NOW() - INTERVAL 7 DAY GROUP BY app_name ORDER BY c DESC LIMIT 5");
             if($stmtW && $rW=$stmtW->get_result()) while($r=$rW->fetch_assoc()) $rankings['weekly'][] = $r;
             
             // Monthly (Last 30d)
             $stmtM = DB::query("SELECT app_name, COUNT(*) as c FROM tracking_events WHERE event_type='visit' AND created_at >= NOW() - INTERVAL 30 DAY GROUP BY app_name ORDER BY c DESC LIMIT 5");
             if($stmtM && $rM=$stmtM->get_result()) while($r=$rM->fetch_assoc()) $rankings['monthly'][] = $r;
             
             $stats['rankings'] = $rankings;

             // 2. DAILY TRENDS (Date-wise Metrics)
             $dailyStats = [];
             $qTrend = "SELECT 
                            DATE(created_at) as log_date,
                            app_name,
                            COUNT(*) as total_visits,
                            SUM(CASE WHEN user_type = 'student' THEN 1 ELSE 0 END) as student_visits,
                            SUM(CASE WHEN user_type = 'teacher' THEN 1 ELSE 0 END) as teacher_visits,
                            SUM(CASE WHEN app_name = 'Home' THEN 1 ELSE 0 END) as home_visits,
                            SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile_visits,
                            SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop_visits
                        FROM tracking_events 
                        WHERE $wVisits
                        GROUP BY log_date, app_name
                        ORDER BY log_date DESC, total_visits DESC";
            
             $stmtTrend = DB::query($qTrend, $p15_params, $p15_types);
             if ($stmtTrend && $resTrend = $stmtTrend->get_result()) {
                 while($r=$resTrend->fetch_assoc()) $dailyStats[] = $r;
             }
             $stats['daily_trends'] = $dailyStats;

             // 3. BUTTON HIT TRACKING
             $btnStats = [];
             $qBtn = "SELECT app_name, event_name as button_name, COUNT(*) as hits 
                      FROM tracking_events 
                      WHERE $wButtons 
                      GROUP BY app_name, event_name 
                      ORDER BY hits DESC";
             $stmtBtn = DB::query($qBtn, $p15_params, $p15_types);
             if ($stmtBtn && $resBtn = $stmtBtn->get_result()) {
                 while($r=$resBtn->fetch_assoc()) $btnStats[] = $r;
             }
             $stats['button_hits'] = $btnStats;

             // 7. ENVIRONMENT STATS (Country & OS)
             
             // Country
             $cStats = [];
             $qCountry = "SELECT app_name, country_code, COUNT(*) as c FROM tracking_events WHERE $wVisits GROUP BY app_name, country_code ORDER BY c DESC";
             $stmtCountry = DB::query($qCountry, $p15_params, $p15_types);
             if ($stmtCountry && $resC = $stmtCountry->get_result()) {
                 while($r=$resC->fetch_assoc()) $cStats[] = $r;
             }
             $stats['country_visits'] = $cStats;

             // OS
             $oStats = [];
             $qOS = "SELECT app_name, os, COUNT(*) as c FROM tracking_events WHERE $wVisits GROUP BY app_name, os ORDER BY c DESC";
             $stmtOS = DB::query($qOS, $p15_params, $p15_types);
             if ($stmtOS && $resO = $stmtOS->get_result()) {
                 while($r=$resO->fetch_assoc()) $oStats[] = $r;
             }
             $stats['os_stats'] = $oStats;

             // 4. DOCUMENT ANALYTICS (Enhanced)
             
             // A. Total Opens
             $totalDocOpens = 0;
             $stmtTO = DB::query("SELECT COUNT(*) as c FROM tracking_events WHERE $wDocs", $p15_params, $p15_types);
             if ($stmtTO && $resTO=$stmtTO->get_result()) {
                 if($r=$resTO->fetch_assoc()) $totalDocOpens = $r['c'];
             }
             
             // B. Grouping by Level & Chapter
             $levels = [];
             $stmtLev = DB::query("SELECT level_name, COUNT(*) as c FROM tracking_documents GROUP BY level_name ORDER BY c DESC");
             if ($stmtLev && $resLev=$stmtLev->get_result()) {
                 while($r=$resLev->fetch_assoc()) $levels[] = $r;
             }

             $chapters = [];
             $stmtChap = DB::query("SELECT chapter_name, COUNT(*) as c FROM tracking_documents GROUP BY chapter_name ORDER BY c DESC");
             if ($stmtChap && $resChap=$stmtChap->get_result()) {
                 while($r=$resChap->fetch_assoc()) $chapters[] = $r;
             }

             // C. Top 10 Documents
             $topDocs = [];
             $stmtTD = DB::query("SELECT document_name as title, COUNT(*) as views FROM tracking_documents GROUP BY document_name ORDER BY views DESC LIMIT 10");
             if ($stmtTD && $resTD=$stmtTD->get_result()) {
                 while($r=$resTD->fetch_assoc()) $topDocs[] = $r;
             }

             $stats['doc_analytics'] = [
                 'total_opens' => $totalDocOpens,
                 'levels' => $levels,
                 'chapters' => $chapters,
                 'top_docs' => $topDocs
             ];

             // 5. LIVE USERS (Detailed)
             $liveStats = [];
             $trackingServicePath = __DIR__ . '/../../auth/TrackingService.php';
             
             if (file_exists($trackingServicePath)) {
                 require_once $trackingServicePath;
                 // Total Live
                 $liveStats['total'] = count(TrackingService::getLiveUsers());
                 
                 // By Country
                 $resLC = DB::query("SELECT country_code, COUNT(*) as c FROM tracking_live_sessions WHERE last_heartbeat >= NOW() - INTERVAL 5 MINUTE GROUP BY country_code");
                 if($resLC && $r=$resLC->get_result()) {
                     while($row=$r->fetch_assoc()) $liveStats['by_country'][] = $row;
                 }
    
                 // By App + Country
                 $resLCA = DB::query("SELECT app_name, country_code, COUNT(*) as c FROM tracking_live_sessions WHERE last_heartbeat >= NOW() - INTERVAL 5 MINUTE GROUP BY app_name, country_code");
                 if($resLCA && $r=$resLCA->get_result()) {
                     while($row=$r->fetch_assoc()) $liveStats['by_app_country'][] = $row;
                 }
             } else {
                 $liveStats['total'] = 0;
                 $liveStats['error'] = 'TrackingService not found';
             }
             
             $stats['live_users'] = $liveStats;


             // 6. REWARDS (Points)
             $stats['rewards'] = ['total'=>0, 'top_earners'=>[]];
             try {
                 // Check if table exists (or just try query and catch)
                 // Attempt Query
                 $resTP = DB::query("SELECT SUM(points) as t FROM user_points");
                 if($resTP && $r=$resTP->get_result()->fetch_assoc()) $stats['rewards']['total'] = $r['t'] ?? 0;
                 
                 $resE = DB::query("SELECT u.email, p.points FROM user_points p JOIN users u ON p.user_id = u.id ORDER BY p.points DESC LIMIT 5");
                 if($resE && $reResult=$resE->get_result()) {
                     while($r=$reResult->fetch_assoc()) $stats['rewards']['top_earners'][] = $r;
                 }
             } catch (Exception $e) {
                 // Table likely missing, or other DB error. Return partial/empty to prevent dashboard crash.
                 $stats['rewards']['error'] = 'Rewards data unavailable';
             }



             // Table (Users with Usage)
             // Table (Users with Usage)

             $where = ['1=1'];
             $params = []; $types = "";
             
             if($search) { $where[] = "(u.email LIKE ? OR u.full_name LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; $types.="ss"; }
             if($dStart) { $where[] = "DATE(l.created_at) >= ?"; $params[] = $dStart; $types.="s"; }
             if($dEnd)   { $where[] = "DATE(l.created_at) <= ?"; $params[] = $dEnd; $types.="s"; }
             
             $wStr = implode(" AND ", $where);
             
             // Totals (Filtered)
             $tQ = "SELECT 
                    SUM(l.audio_tokens_in) as aud_tok,
                    SUM(l.text_tokens_in + l.text_tokens_out) as txt_tok,
                    SUM(l.estimated_cost_total) as total_cost
                    FROM users u 
                    INNER JOIN speech_token_cost_log l ON u.id = l.user_id
                    WHERE $wStr";
             // Reuse params/types (without limit) logic? 
             // DB::query supports re-using params array if passed? Yes.
             $totals = ['aud_tok'=>0, 'txt_tok'=>0, 'total_cost'=>0];
             $stmtT = DB::query($tQ, $params, $types);
             if($stmtT && $resT=$stmtT->get_result()) {
                 $totals = $resT->fetch_assoc();
             }

             // Data Query
             $q = "SELECT u.email, u.full_name, u.last_login_at,
                   SUM(l.audio_tokens_in) as aud_tok,
                   SUM(l.text_tokens_in + l.text_tokens_out) as txt_tok,
                   SUM(l.estimated_cost_audio) as aud_cost,
                   SUM(l.estimated_cost_text_in + l.estimated_cost_text_out) as txt_cost,
                   SUM(l.estimated_cost_total) as total_cost,
                   SUM(l.audio_tokens_in + l.text_tokens_in + l.text_tokens_out) as total_tok_sum
                   FROM users u
                   INNER JOIN speech_token_cost_log l ON u.id = l.user_id
                   WHERE $wStr
                   GROUP BY u.id
                   ORDER BY total_cost DESC
                   LIMIT ? OFFSET ?";
             
             $params[] = $limit; $params[] = $offset; $types.="ii";
             $rows = [];
             $stmt = DB::query($q, $params, $types);
             if($stmt && $res=$stmt->get_result()) {
                 while($r=$res->fetch_assoc()) $rows[] = $r;
             }
             
             echo json_encode(['status'=>'success', 'stats'=>$stats, 'table'=>$rows, 'totals'=>$totals, 'page'=>$page]);
             break;

        case 'get_app_metrics':
             $appName = $_POST['app_name'] ?? '';
             $dStart = $_POST['date_start'] ?? null;
             $dEnd = $_POST['date_end'] ?? null;
             
             if (!$appName) {
                 echo json_encode(['status' => 'error', 'message' => 'App name required']);
                 break;
             }
             
             // Build WHERE clause - use 'te.' alias for queries that join tables
             $where = ["app_name = ?"];
             $whereWithAlias = ["te.app_name = ?"];
             $params = [$appName];
             $types = "s";
             
             if ($dStart) { 
                 $where[] = "DATE(created_at) >= ?"; 
                 $whereWithAlias[] = "DATE(te.created_at) >= ?"; 
                 $params[] = $dStart; 
                 $types .= "s"; 
             }
             if ($dEnd) { 
                 $where[] = "DATE(created_at) <= ?"; 
                 $whereWithAlias[] = "DATE(te.created_at) <= ?"; 
                 $params[] = $dEnd; 
                 $types .= "s"; 
             }
             
             $wVisits = implode(" AND ", array_merge($where, ["event_type = 'visit'"]));
             $wButtons = implode(" AND ", array_merge($where, ["event_type = 'button'"]));
             $wDocs = implode(" AND ", array_merge($where, ["event_type = 'document'"]));
             // For queries with JOINs, use the aliased version
             $wJoinBase = implode(" AND ", $whereWithAlias);

             
             $metrics = [];
             
             // Total Visits
             $res = DB::query("SELECT COUNT(*) as c FROM tracking_events WHERE $wVisits", $params, $types);
             $metrics['total_visits'] = $res ? $res->get_result()->fetch_assoc()['c'] : 0;
             
             // Student Visits
             $res = DB::query("SELECT COUNT(*) as c FROM tracking_events WHERE $wVisits AND user_type = 'student'", $params, $types);
             $metrics['student_visits'] = $res ? $res->get_result()->fetch_assoc()['c'] : 0;
             
             // Teacher Visits
             $res = DB::query("SELECT COUNT(*) as c FROM tracking_events WHERE $wVisits AND user_type = 'teacher'", $params, $types);
             $metrics['teacher_visits'] = $res ? $res->get_result()->fetch_assoc()['c'] : 0;
             
             // Button Clicks
             $res = DB::query("SELECT COUNT(*) as c FROM tracking_events WHERE $wButtons", $params, $types);
             $metrics['button_clicks'] = $res ? $res->get_result()->fetch_assoc()['c'] : 0;
             
             // Desktop vs Mobile
             $res = DB::query("SELECT COUNT(*) as c FROM tracking_events WHERE $wVisits AND device_type = 'desktop'", $params, $types);
             $metrics['desktop_visits'] = $res ? $res->get_result()->fetch_assoc()['c'] : 0;
             
             $res = DB::query("SELECT COUNT(*) as c FROM tracking_events WHERE $wVisits AND device_type = 'mobile'", $params, $types);
             $metrics['mobile_visits'] = $res ? $res->get_result()->fetch_assoc()['c'] : 0;
             
             // Countries
             $countries = [];
             $res = DB::query("SELECT country_code, COUNT(*) as count FROM tracking_events WHERE $wVisits GROUP BY country_code ORDER BY count DESC LIMIT 10", $params, $types);
             if ($res && $r = $res->get_result()) {
                 while ($row = $r->fetch_assoc()) $countries[] = $row;
             }
             $metrics['countries'] = $countries;
             
             // Button Names
             $buttons = [];
             $res = DB::query("SELECT event_name, COUNT(*) as count FROM tracking_events WHERE $wButtons GROUP BY event_name ORDER BY count DESC LIMIT 10", $params, $types);
             if ($res && $r = $res->get_result()) {
                 while ($row = $r->fetch_assoc()) $buttons[] = $row;
             }
             $metrics['buttons'] = $buttons;
             
             // Top 10 Users by Activity
             $topUsers = [];
             $res = DB::query("SELECT u.email, u.full_name, COUNT(*) as count FROM tracking_events te LEFT JOIN users u ON te.user_id = u.id WHERE $wJoinBase AND te.user_id IS NOT NULL GROUP BY te.user_id ORDER BY count DESC LIMIT 10", $params, $types);
             if ($res && $r = $res->get_result()) {
                 while ($row = $r->fetch_assoc()) $topUsers[] = $row;
             }
             $metrics['top_users'] = $topUsers;
             
             // Documents (for DViz)
             if (strtolower($appName) === 'dviz') {
                 $docMetrics = [];
                 
                 // Total opens
                 $res = DB::query("SELECT COUNT(*) as c FROM tracking_events WHERE $wDocs", $params, $types);
                 $docMetrics['total_opens'] = $res ? $res->get_result()->fetch_assoc()['c'] : 0;
                 
                 // Top documents
                 $topDocs = [];
                 $res = DB::query("SELECT document_name as title, COUNT(*) as views FROM tracking_documents WHERE app_name = ? GROUP BY document_name ORDER BY views DESC LIMIT 10", [$appName], "s");
                 if ($res && $r = $res->get_result()) {
                     while ($row = $r->fetch_assoc()) $topDocs[] = $row;
                 }
                 $docMetrics['top'] = $topDocs;
                 
                 $metrics['documents'] = $docMetrics;
             }
             
             echo json_encode(['status' => 'success', 'metrics' => $metrics]);
             break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (Throwable $e) {
    error_log("UserAction Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}


