<?php
// admin/ads/ads_manager.php
require_once __DIR__ . '/../../auth/Auth.php';
require_once __DIR__ . '/../../auth/db.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'list_ads') {
        $stmt = DB::query("SELECT * FROM ads WHERE status != 'completed' ORDER BY priority DESC, created_at DESC");
        $ads = [];
        if ($stmt && $res = $stmt->get_result()) {
            while ($row = $res->fetch_assoc()) {
                // Get placements
                $pStmt = DB::query("SELECT p.placement_key FROM ad_placement_map m JOIN ad_placements p ON m.placement_id = p.placement_id WHERE m.ad_id = ?", [$row['ad_id']], "i");
                $placements = [];
                if ($pStmt && $pRes = $pStmt->get_result()) {
                    while ($pRow = $pRes->fetch_assoc()) $placements[] = $pRow['placement_key'];
                }
                $row['placements'] = $placements;
                
                // Get Stats (Impressions/Clicks) - Simplified for list
                $sStmt = DB::query("SELECT COUNT(*) as imp FROM ad_impressions WHERE ad_id = ?", [$row['ad_id']], "i");
                $row['stats_impressions'] = ($sStmt && $sRes = $sStmt->get_result()) ? $sRes->fetch_assoc()['imp'] : 0;
                
                $cStmt = DB::query("SELECT COUNT(*) as clk FROM ad_clicks WHERE ad_id = ?", [$row['ad_id']], "i");
                $row['stats_clicks'] = ($cStmt && $cRes = $cStmt->get_result()) ? $cRes->fetch_assoc()['clk'] : 0;

                $ads[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'ads' => $ads]);
    }
    elseif ($action === 'save_ad') {
        $id = $_POST['ad_id'] ?? '';
        $title = $_POST['ad_title'];
        $type = $_POST['ad_type'];
        $network = $_POST['network_name'] ?? null;
        
        // Dynamic ad_code retrieval
        $code = null;
        if ($type === 'sponsor') $code = $_POST['ad_code_sponsor'] ?? null;
        elseif ($type === 'google') $code = $_POST['ad_code_google'] ?? null;
        elseif ($type === 'push') $code = $_POST['ad_code_push'] ?? null;
        else $code = $_POST['ad_code'] ?? null; // Fallback

        $audience = $_POST['audience'];
        $priority = (int)$_POST['priority'];
        $status = $_POST['status'];
        $sDate = !empty($_POST['start_datetime']) ? $_POST['start_datetime'] : null;
        $eDate = !empty($_POST['end_datetime']) ? $_POST['end_datetime'] : null;
        
        $conn = DB::connect();

        if ($id) {
            // Update
            $sql = "UPDATE ads SET ad_title=?, ad_type=?, network_name=?, ad_code=?, audience=?, priority=?, status=?, start_datetime=?, end_datetime=? WHERE ad_id=?";
            DB::query($sql, [$title, $type, $network, $code, $audience, $priority, $status, $sDate, $eDate, $id], "sssssisssi");
            $adId = $id;
        } else {
            // Insert
            $sql = "INSERT INTO ads (ad_title, ad_type, network_name, ad_code, audience, priority, status, start_datetime, end_datetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            DB::query($sql, [$title, $type, $network, $code, $audience, $priority, $status, $sDate, $eDate], "sssssisss");
            $adId = $conn->insert_id;
        }

        // Handle Placements
        DB::query("DELETE FROM ad_placement_map WHERE ad_id = ?", [$adId], "i");
        if (!empty($_POST['placements'])) {
            $places = is_array($_POST['placements']) ? $_POST['placements'] : explode(',', $_POST['placements']);
            foreach ($places as $pkey) {
                // Get ID
                $r = DB::query("SELECT placement_id FROM ad_placements WHERE placement_key = ?", [$pkey], "s");
                if ($r && $row = $r->get_result()->fetch_assoc()) {
                    DB::query("INSERT INTO ad_placement_map (ad_id, placement_id) VALUES (?, ?)", [$adId, $row['placement_id']], "ii");
                }
            }
        }
        
        // Handle Apps
        DB::query("DELETE FROM ad_apps WHERE ad_id = ?", [$adId], "i");
         if (!empty($_POST['apps'])) {
            $apps = is_array($_POST['apps']) ? $_POST['apps'] : explode(',', $_POST['apps']);
            foreach ($apps as $app) {
                DB::query("INSERT INTO ad_apps (ad_id, app_key) VALUES (?, ?)", [$adId, $app], "is");
            }
        }

        // Handle Image Upload if Sponsor
        if ($type === 'sponsor' && !empty($_FILES['ad_image']['name'])) {
            $targetDir = "../../uploads/ads/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time() . '_' . basename($_FILES["ad_image"]["name"]);
            $targetFile = $targetDir . $fileName;
            if (move_uploaded_file($_FILES["ad_image"]["tmp_name"], $targetFile)) {
                // Remove old? For now just add (support slider later, but phase 11 says Multi is okay but 1 is simpler)
                // For simplicity, we wipe old for this ad if simple mode
                DB::query("DELETE FROM ad_images WHERE ad_id = ?", [$adId], "i");
                DB::query("INSERT INTO ad_images (ad_id, image_path) VALUES (?, ?)", [$adId, 'uploads/ads/' . $fileName], "is");
            }
        }
        // If image URL provided manually (or editing/keeping existing), handle that logic if needed. 
        // For now, assume upload is required for new image.

        // Handle Push Settings if Push
        if ($type === 'push') {
            $disp = $_POST['push_display_type'] ?? 'toast';
            $delay = $_POST['push_close_delay'] ?? 3;
            $imm = isset($_POST['push_allow_close']) ? 1 : 0;
            $freq = $_POST['push_freq'] ?? 1;
            
            DB::query("REPLACE INTO push_ads_settings (ad_id, display_type, close_delay_seconds, allow_immediate_close, frequency_limit) VALUES (?, ?, ?, ?, ?)", 
                [$adId, $disp, $delay, $imm, $freq], "isiii");
        }

        echo json_encode(['status' => 'success', 'message' => 'Ad saved successfully']);
    }
    elseif ($action === 'delete_ad') {
        $id = $_POST['ad_id'];
        DB::query("DELETE FROM ads WHERE ad_id = ?", [$id], "i");
        echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'get_ad') {
        $id = $_POST['ad_id'];
        $stmt = DB::query("SELECT * FROM ads WHERE ad_id = ?", [$id], "i");
        $ad = $stmt->get_result()->fetch_assoc();
        
        // Get Placements
        $pRes = DB::query("SELECT p.placement_key FROM ad_placement_map m JOIN ad_placements p ON m.placement_id = p.placement_id WHERE m.ad_id = ?", [$id], "i")->get_result();
        $ad['placements'] = [];
        while($r = $pRes->fetch_assoc()) $ad['placements'][] = $r['placement_key'];

        // Get Apps
        $aRes = DB::query("SELECT app_key FROM ad_apps WHERE ad_id = ?", [$id], "i")->get_result();
        $ad['apps'] = [];
        while($r = $aRes->fetch_assoc()) $ad['apps'][] = $r['app_key'];

        // Get Images
        $iRes = DB::query("SELECT image_path FROM ad_images WHERE ad_id = ?", [$id], "i")->get_result();
        $ad['images'] = [];
        while($r = $iRes->fetch_assoc()) $ad['images'][] = $r['image_path'];
        
        // Get Push Settings
        if($ad['ad_type'] === 'push') {
            $push = DB::query("SELECT * FROM push_ads_settings WHERE ad_id = ?", [$id], "i")->get_result()->fetch_assoc();
            if($push) $ad = array_merge($ad, $push);
        }

        echo json_encode(['status' => 'success', 'ad' => $ad]);
    }
    elseif ($action === 'toggle_status') {
         $id = $_POST['ad_id'];
         $status = $_POST['status']; // active/paused/completed
         
         // Check current status
         $curr = DB::query("SELECT status FROM ads WHERE ad_id = ?", [$id], "i")->get_result()->fetch_assoc();
         if ($curr && $curr['status'] === 'completed') {
             echo json_encode(['status' => 'error', 'message' => 'Completed campaigns cannot be modified.']);
             exit;
         }

         DB::query("UPDATE ads SET status = ? WHERE ad_id = ?", [$status, $id], "si");
         echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'save_global_settings') {
        $enabled = isset($_POST['ads_enabled']) ? 1 : 0;
        $days = $_POST['ip_retention'] ?? 14;
        $freq = $_POST['default_freq'] ?? 3;
        
        DB::query("UPDATE ad_global_settings SET ads_enabled=?, ip_retention_days=?, default_frequency_limit=? WHERE id=1", [$enabled, $days, $freq], "iii");
        echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'get_report') {
        $start = $_POST['start_date'] . ' 00:00:00';
        $end = $_POST['end_date'] . ' 23:59:59';
        
        // Helper to get count safely
        function getCount($sql, $params=[], $types="") {
            $stmt = DB::query($sql, $params, $types);
            if ($stmt && $res = $stmt->get_result()) {
                $r = $res->fetch_assoc();
                return $r ? $r['c'] : 0;
            }
            return 0;
        }

        // KPI
        $imp = getCount("SELECT COUNT(*) as c FROM ad_impressions WHERE created_at BETWEEN ? AND ?", [$start, $end], "ss");
        $clk = getCount("SELECT COUNT(*) as c FROM ad_clicks WHERE created_at BETWEEN ? AND ?", [$start, $end], "ss");
        $active = getCount("SELECT COUNT(*) as c FROM ads WHERE status = 'active'");
        $total = getCount("SELECT COUNT(*) as c FROM ads");
        $ctr = ($imp > 0) ? ($clk / $imp) * 100 : 0;

        // Charts
        $dates = []; $impData = []; $clkData = []; $impMap = []; $clkMap = [];
        
        $stmtD = DB::query("SELECT DATE(created_at) as d, COUNT(*) as c FROM ad_impressions WHERE created_at BETWEEN ? AND ? GROUP BY d ORDER BY d", [$start, $end], "ss");
        if ($stmtD && $resD = $stmtD->get_result()) {
            while($r = $resD->fetch_assoc()) $impMap[$r['d']] = $r['c'];
        }
        
        $stmtC = DB::query("SELECT DATE(created_at) as d, COUNT(*) as c FROM ad_clicks WHERE created_at BETWEEN ? AND ? GROUP BY d ORDER BY d", [$start, $end], "ss");
        if ($stmtC && $resC = $stmtC->get_result()) {
            while($r = $resC->fetch_assoc()) $clkMap[$r['d']] = $r['c'];
        }
        
        // Fill dates
        $curr = strtotime($_POST['start_date']);
        $last = strtotime($_POST['end_date']);
        while($curr <= $last) {
            $d = date('Y-m-d', $curr);
            $dates[] = $d;
            $impData[] = $impMap[$d] ?? 0;
            $clkData[] = $clkMap[$d] ?? 0;
            $curr = strtotime('+1 day', $curr);
        }

        // Apps
        $apps = [];
        $stmtA = DB::query("SELECT aa.app_key, COUNT(*) as c FROM ad_impressions i JOIN ad_apps aa ON i.ad_id = aa.ad_id WHERE i.created_at BETWEEN ? AND ? GROUP BY aa.app_key", [$start, $end], "ss");
        if ($stmtA && $resA = $stmtA->get_result()) {
            while($r = $resA->fetch_assoc()) $apps[$r['app_key'] ?? 'Unknown'] = $r['c'];
        }

        // Table
        $table = [];
        $adsList = [];
        $stmtT = DB::query("SELECT a.ad_id, a.ad_title, a.ad_type, a.status FROM ads a");
        if ($stmtT && $resT = $stmtT->get_result()) {
            while($row = $resT->fetch_assoc()) $adsList[] = $row;
        }
        
        foreach($adsList as $currAd) {
             $i = getCount("SELECT COUNT(*) as c FROM ad_impressions WHERE ad_id = ? AND created_at BETWEEN ? AND ?", [$currAd['ad_id'], $start, $end], "iss");
             
             if ($i == 0 && $currAd['status'] !== 'active') continue; 
             
             $c = getCount("SELECT COUNT(*) as c FROM ad_clicks WHERE ad_id = ? AND created_at BETWEEN ? AND ?", [$currAd['ad_id'], $start, $end], "iss");
             
             $currAd['impressions'] = $i;
             $currAd['clicks'] = $c;
             $currAd['ctr'] = ($i > 0) ? ($c / $i) * 100 : 0;
             $table[] = $currAd;
        }
        
        usort($table, function($a, $b) { return $b['impressions'] - $a['impressions']; });

        echo json_encode([
            'status' => 'success',
            'kpi' => ['impressions' => $imp, 'clicks' => $clk, 'active_ads' => $active, 'total_ads' => $total, 'ctr' => $ctr],
            'charts' => ['dates' => $dates, 'impressions' => $impData, 'clicks' => $clkData, 'apps' => $apps],
            'table' => $table
        ]);
    }
    elseif ($action === 'export_report') {
        $start = $_GET['start_date'] . ' 00:00:00';
        $end = $_GET['end_date'] . ' 23:59:59';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="ads_report_' . $_GET['start_date'] . '.csv"');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Ad ID', 'Title', 'Type', 'Status', 'Impressions', 'Clicks', 'CTR (%)']);
        
        $resT = DB::query("SELECT a.ad_id, a.ad_title, a.ad_type, a.status FROM ads a");
        if($resT) {
            while($currAd = $resT->get_result()->fetch_assoc()) {
                $i = DB::query("SELECT COUNT(*) as c FROM ad_impressions WHERE ad_id = ? AND timestamp BETWEEN ? AND ?", [$currAd['ad_id'], $start, $end], "iss")->get_result()->fetch_assoc()['c'];
                $c = DB::query("SELECT COUNT(*) as c FROM ad_clicks WHERE ad_id = ? AND timestamp BETWEEN ? AND ?", [$currAd['ad_id'], $start, $end], "iss")->get_result()->fetch_assoc()['c'];
                $ctr = ($i > 0) ? ($c / $i) * 100 : 0;
                
                fputcsv($out, [
                    $currAd['ad_id'],
                    $currAd['ad_title'],
                    $currAd['ad_type'],
                    $currAd['status'],
                    $i,
                    $c,
                    number_format($ctr, 2)
                ]);
            }
        }
        fclose($out);
        exit;
    }
    elseif ($action === 'get_global_settings') {
        $res = DB::query("SELECT * FROM ad_global_settings WHERE id=1");
        $row = $res->get_result()->fetch_assoc();
        echo json_encode(['status' => 'success', 'settings' => $row]);
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
