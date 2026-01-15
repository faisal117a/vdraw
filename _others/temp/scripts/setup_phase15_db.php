<?php
// setup_phase15_db.php

$host = '127.0.0.1';
$user = 'root';
$pass = ''; 
$dbname = 'pyviz_db7';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database...\n";

$queries = [
    "CREATE TABLE IF NOT EXISTS tracking_events (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT NULL,
      user_type ENUM('student','teacher','guest') NOT NULL,
      app_name VARCHAR(50) NOT NULL,
      event_type ENUM('visit','button','document','session') NOT NULL,
      event_name VARCHAR(100) NOT NULL,
      country_code CHAR(2) NULL,
      ip_hash CHAR(64) NULL,
      os VARCHAR(50) NULL,
      device_type ENUM('desktop','mobile','tablet') NULL,
      session_id VARCHAR(100) NULL,
      meta JSON NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_date (created_at),
      INDEX idx_app_event (app_name, event_type),
      INDEX idx_user (user_id),
      INDEX idx_country (country_code)
    )",

    "CREATE TABLE IF NOT EXISTS tracking_live_sessions (
      session_id VARCHAR(100) PRIMARY KEY,
      user_id BIGINT NOT NULL,
      user_type ENUM('student','teacher') NOT NULL,
      app_name VARCHAR(50) NOT NULL,
      country_code CHAR(2) NULL,
      last_heartbeat DATETIME NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_app (app_name),
      INDEX idx_country (country_code),
      INDEX idx_heartbeat (last_heartbeat)
    )",

    "CREATE TABLE IF NOT EXISTS tracking_documents (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT NULL,
      user_type ENUM('student','teacher','guest') NOT NULL,
      app_name VARCHAR(50) NOT NULL DEFAULT 'DViz',
      level_name VARCHAR(100) NOT NULL,
      chapter_name VARCHAR(150) NOT NULL,
      document_name VARCHAR(200) NOT NULL,
      country_code CHAR(2) NULL,
      ip_hash CHAR(64) NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_doc (level_name, chapter_name),
      INDEX idx_date (created_at)
    )",

    "CREATE TABLE IF NOT EXISTS user_points (
      user_id BIGINT PRIMARY KEY,
      points INT NOT NULL DEFAULT 0,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS user_point_logs (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT NOT NULL,
      action ENUM('earn','redeem') NOT NULL,
      points INT NOT NULL,
      related_event VARCHAR(100) NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_user (user_id),
      INDEX idx_action (action)
    )",

    "CREATE TABLE IF NOT EXISTS user_credits (
      user_id BIGINT PRIMARY KEY,
      credits INT NOT NULL DEFAULT 0,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS user_credit_logs (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT NOT NULL,
      credits_added INT NOT NULL,
      points_consumed INT NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_user (user_id)
    )",

    "CREATE TABLE IF NOT EXISTS admin_settings (
      setting_key VARCHAR(50) PRIMARY KEY,
      setting_value VARCHAR(100) NOT NULL
    )"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created/checked successfully.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

// Seed admin_settings
$seeds = [
    "INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('tracking_enabled', '1')",
    "INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('points_enabled', '1')",
    "INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('live_session_timeout_minutes', '5')"
];

foreach ($seeds as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Seed data inserted/checked successfully.\n";
    } else {
        echo "Error seeding data: " . $conn->error . "\n";
    }
}

$conn->close();

?>
