<?php
require_once __DIR__ . '/../../auth/Auth.php';
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../../auth/PointsSystem.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user = Auth::user();
$userId = $user['id'];
$creditsToBuy = filter_input(INPUT_POST, 'credits', FILTER_VALIDATE_INT);

if (!$creditsToBuy || $creditsToBuy <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid credit amount.']);
    exit;
}

try {
    $costPerCredit = 20;
    $totalCost = $creditsToBuy * $costPerCredit;
    $currentBalance = PointsSystem::getBalance($userId);
    
    if ($currentBalance < $totalCost) {
        echo json_encode(['success' => false, 'message' => 'Insufficient points. You need ' . $totalCost . ' points but only have ' . $currentBalance . '.']);
        exit;
    }
    
    // Perform redemption
    $conn = DB::connect();
    $conn->begin_transaction();
    
    try {
        // Deduct points
        DB::query("UPDATE user_points SET points = points - ? WHERE user_id = ?", [$totalCost, $userId], 'ii');
        
        // Log point deduction
        DB::query("INSERT INTO user_point_logs (user_id, action, points, related_event) VALUES (?, 'redeem', ?, ?)", 
            [$userId, -$totalCost, "Redeemed $creditsToBuy credits"], 'iis');
        
        // Add reward credits to user
        DB::query("UPDATE users SET reward_credits = COALESCE(reward_credits, 0) + ? WHERE id = ?", [$creditsToBuy, $userId], 'ii');
        
        $conn->commit();
        
        $newBalance = PointsSystem::getBalance($userId);
        echo json_encode(['success' => true, 'message' => "Successfully redeemed $creditsToBuy credits!", 'new_balance' => $newBalance]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
