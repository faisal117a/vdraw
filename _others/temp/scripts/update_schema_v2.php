<?php
require_once __DIR__ . '/frontend/PyViz/auth/db.php';
$conn = DB::connect();

try {
    echo "Updating schema...\n";

    // 1. user_activity_log: Add country
    $conn->query("ALTER TABLE user_activity_log ADD COLUMN IF NOT EXISTS country VARCHAR(100) DEFAULT 'Unknown'");
    echo "- user_activity_log updated\n";
    
    // 2. blocked_ips: Add block_type and expires_at
    $conn->query("ALTER TABLE blocked_ips ADD COLUMN IF NOT EXISTS block_type ENUM('manual','auto') DEFAULT 'manual'");
    $conn->query("ALTER TABLE blocked_ips ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL");
    echo "- blocked_ips updated\n";

    $conn->query("INSERT IGNORE INTO app_settings (setting_key, setting_value, updated_at) VALUES ('max_ip_daily', '100', NOW())");
    $conn->query("INSERT IGNORE INTO app_settings (setting_key, setting_value, updated_at) VALUES ('max_ip_monthly', '2000', NOW())");
    echo "- app_settings updated using setting_key\n";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
