<?php
require_once __DIR__ . '/../auth/db.php';

$conn = DB::connect();

$queries = [
    "CREATE TABLE IF NOT EXISTS email_templates (
        tid INT AUTO_INCREMENT PRIMARY KEY,
        template_title VARCHAR(255) NOT NULL,
        text_body LONGTEXT NOT NULL,
        html_body LONGTEXT NOT NULL,
        template_version INT NOT NULL DEFAULT 1,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS email_batches (
        bid INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        email_list_source LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS email_batch_items (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
        sent_at DATETIME NULL,
        error_reason VARCHAR(500) NULL,
        CONSTRAINT fk_batch_items_batch
            FOREIGN KEY (batch_id) REFERENCES email_batches(bid)
            ON DELETE CASCADE,
        INDEX idx_batch_email (batch_id, email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS smtp_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        profile_name VARCHAR(150) NOT NULL,
        smtp_host VARCHAR(255) NOT NULL,
        smtp_port INT NOT NULL,
        smtp_username VARCHAR(255) NOT NULL,
        smtp_password VARCHAR(255) NOT NULL,
        sender_email VARCHAR(255) NOT NULL,
        sender_name VARCHAR(255) NOT NULL,
        encryption ENUM('none','tls','ssl') NOT NULL DEFAULT 'tls',
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS email_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        template_id INT NOT NULL,
        smtp_profile_id INT NOT NULL,
        log_text LONGTEXT NOT NULL,
        started_at DATETIME NOT NULL,
        ended_at DATETIME NOT NULL,
        total_emails INT NOT NULL,
        sent_count INT NOT NULL,
        failed_count INT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_logs_batch
            FOREIGN KEY (batch_id) REFERENCES email_batches(bid),
        CONSTRAINT fk_logs_template
            FOREIGN KEY (template_id) REFERENCES email_templates(tid),
        CONSTRAINT fk_logs_smtp
            FOREIGN KEY (smtp_profile_id) REFERENCES smtp_profiles(id),
        INDEX idx_log_batch (batch_id),
        INDEX idx_log_date (created_at)
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS email_attachments (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        template_id INT NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_attach_template
            FOREIGN KEY (template_id) REFERENCES email_templates(tid)
            ON DELETE CASCADE
    ) ENGINE=InnoDB;"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Query executed successfully: " . substr($sql, 0, 50) . "...\n";
    } else {
        echo "Error executing query: " . $conn->error . "\nSQL: " . $sql . "\n";
    }
}

echo "Migration completed.\n";
