<?php
// fix_tracking_db.php
require_once __DIR__ . '/auth/db.php';

echo "Checking/Creating Tracking Tables...\n";

// 1. tracking_live_sessions
$sql1 = "CREATE TABLE IF NOT EXISTS tracking_live_sessions (
    session_id VARCHAR(255) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    user_type VARCHAR(50),
    app_name VARCHAR(100),
    country_code VARCHAR(10),
    last_heartbeat DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if(DB::query($sql1)) {
    echo "Table 'tracking_live_sessions' checked/created.\n";
} else {
    echo "Error creating 'tracking_live_sessions': " . DB::error() . "\n";
}

// 2. tracking_documents
$sql2 = "CREATE TABLE IF NOT EXISTS tracking_documents (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    user_type VARCHAR(50),
    app_name VARCHAR(100),
    level_name VARCHAR(255),
    chapter_name VARCHAR(255),
    document_name VARCHAR(255),
    country_code VARCHAR(10),
    ip_hash VARCHAR(64),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (document_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if(DB::query($sql2)) {
    echo "Table 'tracking_documents' checked/created.\n";
} else {
    echo "Error creating 'tracking_documents': " . DB::error() . "\n";
}

// 3. tracking_events
$sql3 = "CREATE TABLE IF NOT EXISTS tracking_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    user_type VARCHAR(50),
    app_name VARCHAR(100),
    event_type VARCHAR(50), 
    event_name VARCHAR(255),
    country_code VARCHAR(10),
    ip_hash VARCHAR(64),
    os VARCHAR(50),
    device_type VARCHAR(50),
    session_id VARCHAR(255),
    meta TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (event_type),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if(DB::query($sql3)) {
    echo "Table 'tracking_events' checked/created.\n";
} else {
    echo "Error creating 'tracking_events': " . DB::error() . "\n";
}

echo "Done.\n";
?>
