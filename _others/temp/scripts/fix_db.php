<?php
// fix_db.php - One-off script to create missing tables which cause 500 errors
require_once __DIR__ . '/frontend/PyViz/auth/db.php';

echo "Checking tables...<br>";
$conn = DB::connect();

// Blocked IPs
$sql = "CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    reason VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) echo "Table blocked_ips OK.<br>";
else echo "Error blocked_ips: " . $conn->error . "<br>";

// Blocked Countries
$sql = "CREATE TABLE IF NOT EXISTS blocked_countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_code VARCHAR(2) NOT NULL UNIQUE,
    reason VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) echo "Table blocked_countries OK.<br>";
else echo "Error blocked_countries: " . $conn->error . "<br>";

// Teacher Verification Requests
$sql = "CREATE TABLE IF NOT EXISTS teacher_verification_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if ($conn->query($sql)) echo "Table teacher_verification_requests OK.<br>";
else echo "Error teacher_verification_requests: " . $conn->error . "<br>";

// App Settings Table
$sql = "CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) echo "Table app_settings OK.<br>";
else echo "Error app_settings: " . $conn->error . "<br>";

// Speech Requests Table
$sql = "CREATE TABLE IF NOT EXISTS speech_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'started',
    credit_charged INT DEFAULT 0,
    credit_refunded INT DEFAULT 0,
    user_ip VARCHAR(45),
    audio_seconds DECIMAL(10,2) DEFAULT 0,
    failure_stage VARCHAR(100),
    failure_message TEXT
)";
if ($conn->query($sql)) echo "Table speech_requests OK.<br>";
else echo "Error speech_requests: " . $conn->error . "<br>";

// Speech Token Cost Log
$sql = "CREATE TABLE IF NOT EXISTS speech_token_cost_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    speech_request_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    audio_tokens_in INT DEFAULT 0,
    text_tokens_in INT DEFAULT 0,
    text_tokens_out INT DEFAULT 0,
    audio_cost_per_1m DECIMAL(10,4),
    text_cost_per_1m_in DECIMAL(10,4),
    text_cost_per_1m_out DECIMAL(10,4),
    estimated_cost_audio DECIMAL(10,6),
    estimated_cost_text_in DECIMAL(10,6),
    estimated_cost_text_out DECIMAL(10,6),
    estimated_cost_total DECIMAL(10,6)
)";
if ($conn->query($sql)) echo "Table speech_token_cost_log OK.<br>";
else echo "Error speech_token_cost_log: " . $conn->error . "<br>";

// User Credit Daily
$sql = "CREATE TABLE IF NOT EXISTS user_credit_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    yyyymmdd VARCHAR(8) NOT NULL,
    credits_used INT DEFAULT 0,
    credits_refunded INT DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_day (user_id, yyyymmdd)
)";
if ($conn->query($sql)) echo "Table user_credit_daily OK.<br>";
else echo "Error user_credit_daily: " . $conn->error . "<br>";

// User Credit Monthly
$sql = "CREATE TABLE IF NOT EXISTS user_credit_monthly (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    yyyymm VARCHAR(6) NOT NULL,
    credits_used INT DEFAULT 0,
    credits_refunded INT DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_month (user_id, yyyymm)
)";
if ($conn->query($sql)) echo "Table user_credit_monthly OK.<br>";
else echo "Error user_credit_monthly: " . $conn->error . "<br>";

// IP Usage Monthly
$sql = "CREATE TABLE IF NOT EXISTS ip_usage_monthly (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_ip VARCHAR(45) NOT NULL,
    yyyymm VARCHAR(6) NOT NULL,
    speech_count INT DEFAULT 0,
    tokens_total_est INT DEFAULT 0,
    cost_total_est DECIMAL(10,4) DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ip_month (user_ip, yyyymm)
)";
if ($conn->query($sql)) echo "Table ip_usage_monthly OK.<br>";
else echo "Error ip_usage_monthly: " . $conn->error . "<br>";

// User Activity Log
$sql = "CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100),
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) echo "Table user_activity_log OK.<br>";
else echo "Error user_activity_log: " . $conn->error . "<br>";

echo "Done.";
