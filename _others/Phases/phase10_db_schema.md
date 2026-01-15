# Phase 10 – Database Schema (MySQL)

> This document defines the **complete MySQL database schema** for Phase 10 (User Authentication, Credits & Cost Control) of **PyViz**.
> It is designed for **PHP + MySQL shared/VPS hosting**, Antigravity-safe, and avoids unsupported MySQL features such as `CHECK` constraints.

---

## 1. General Conventions

- Storage Engine: **InnoDB**
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- All timestamps stored in **UTC**
- Foreign keys enforced
- Validation logic handled at application layer

---

## 2. Core User Tables

### 2.1 `users`
Stores user accounts and profile information.

Fields:
- `id` BIGINT PK AI
- `full_name` VARCHAR(120) NOT NULL
- `email` VARCHAR(190) NOT NULL
- `password_hash` VARCHAR(255) NOT NULL
- `phone_e164` VARCHAR(20) NOT NULL
- `country_code` CHAR(2) NULL
- `role` ENUM('student','teacher') NOT NULL
- `email_verified_at` DATETIME NULL
- `teacher_verified` TINYINT(1) NOT NULL DEFAULT 0
- `status` ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active'
- `suspended_reason` VARCHAR(255) NULL
- `consent_at` DATETIME NULL
- `created_at` DATETIME NOT NULL
- `updated_at` DATETIME NOT NULL

Indexes:
- UNIQUE `uq_users_email` (`email`)
- INDEX `ix_users_status` (`status`)
- INDEX `ix_users_role` (`role`)
- INDEX `ix_users_teacher_verified` (`teacher_verified`)

---

### 2.2 `user_email_verifications`
Stores email verification OTP codes.

Fields:
- `id` BIGINT PK AI
- `user_id` BIGINT NOT NULL
- `code6` CHAR(6) NOT NULL
- `expires_at` DATETIME NOT NULL
- `used_at` DATETIME NULL
- `attempts` INT NOT NULL DEFAULT 0
- `created_ip` VARCHAR(45) NULL
- `created_at` DATETIME NOT NULL

Indexes:
- INDEX `ix_uev_user` (`user_id`)
- INDEX `ix_uev_code` (`code6`)
- INDEX `ix_uev_expires` (`expires_at`)

FK:
- `user_id` → `users(id)` ON DELETE CASCADE

---

### 2.3 `user_password_resets`
Password reset OTP handling.

Fields:
- `id` BIGINT PK AI
- `user_id` BIGINT NOT NULL
- `code6` CHAR(6) NOT NULL
- `expires_at` DATETIME NOT NULL
- `used_at` DATETIME NULL
- `created_ip` VARCHAR(45) NULL
- `created_at` DATETIME NOT NULL

Indexes:
- INDEX `ix_upr_user` (`user_id`)
- INDEX `ix_upr_code` (`code6`)

FK:
- `user_id` → `users(id)` ON DELETE CASCADE

---

## 3. Teacher Verification

### 3.1 `teacher_verification_requests`
Stores teacher ID verification submissions.

Fields:
- `id` BIGINT PK AI
- `user_id` BIGINT NOT NULL
- `status` ENUM('pending','approved','rejected') NOT NULL
- `id_card_file_path` VARCHAR(255) NOT NULL
- `id_card_file_mime` VARCHAR(80) NULL
- `id_card_file_size_bytes` INT NULL
- `submitted_at` DATETIME NOT NULL
- `reviewed_at` DATETIME NULL
- `reviewed_by_admin_id` BIGINT NULL
- `admin_note` VARCHAR(255) NULL
- `rejected_reason` VARCHAR(255) NULL

Indexes:
- INDEX `ix_tvr_user` (`user_id`)
- INDEX `ix_tvr_status` (`status`)

FK:
- `user_id` → `users(id)` ON DELETE CASCADE

Note: Re-upload after rejection should create a **new row** for audit history.

---

## 4. Credits & Quota Tables

### 4.1 `user_credit_daily`
Daily credit usage per user.

Fields:
- `id` BIGINT PK AI
- `user_id` BIGINT NOT NULL
- `yyyymmdd` CHAR(8) NOT NULL
- `credits_used` INT NOT NULL DEFAULT 0
- `credits_refunded` INT NOT NULL DEFAULT 0
- `updated_at` DATETIME NOT NULL

Indexes:
- UNIQUE `uq_ucd_user_day` (`user_id`,`yyyymmdd`)

FK:
- `user_id` → `users(id)` ON DELETE CASCADE

---

### 4.2 `user_credit_monthly`
Monthly credit usage per user.

Fields:
- `id` BIGINT PK AI
- `user_id` BIGINT NOT NULL
- `yyyymm` CHAR(6) NOT NULL
- `credits_used` INT NOT NULL DEFAULT 0
- `credits_refunded` INT NOT NULL DEFAULT 0
- `updated_at` DATETIME NOT NULL

Indexes:
- UNIQUE `uq_ucm_user_month` (`user_id`,`yyyymm`)

FK:
- `user_id` → `users(id)` ON DELETE CASCADE

---

## 5. Speech Requests & Token Accounting

### 5.1 `speech_requests`
Represents one Speech-to-Code action.

Fields:
- `id` BIGINT PK AI
- `user_id` BIGINT NOT NULL
- `created_at` DATETIME NOT NULL
- `status` ENUM('started','stt_ok','llm_ok','failed','refunded') NOT NULL
- `failure_stage` ENUM('stt','llm','system') NULL
- `failure_message` VARCHAR(255) NULL
- `user_ip` VARCHAR(45) NULL
- `country_code` CHAR(2) NULL
- `audio_seconds` DECIMAL(6,2) NULL
- `credit_charged` TINYINT(1) NOT NULL DEFAULT 0
- `credit_refunded` TINYINT(1) NOT NULL DEFAULT 0
- `stt_provider` VARCHAR(40) NULL
- `stt_model` VARCHAR(60) NULL
- `llm_provider` VARCHAR(40) NULL
- `llm_model` VARCHAR(60) NULL

Indexes:
- INDEX `ix_sr_user_time` (`user_id`,`created_at`)
- INDEX `ix_sr_ip_time` (`user_ip`,`created_at`)

FK:
- `user_id` → `users(id)` ON DELETE CASCADE

---

### 5.2 `speech_token_cost_log`
Token and cost breakdown per speech request.

Fields:
- `id` BIGINT PK AI
- `speech_request_id` BIGINT NOT NULL
- `user_id` BIGINT NOT NULL
- `created_at` DATETIME NOT NULL
- `audio_tokens_in` INT NOT NULL
- `text_tokens_in` INT NOT NULL
- `text_tokens_out` INT NOT NULL
- `audio_cost_per_1m` DECIMAL(12,4) NOT NULL
- `text_cost_per_1m_in` DECIMAL(12,4) NOT NULL
- `text_cost_per_1m_out` DECIMAL(12,4) NOT NULL
- `estimated_cost_audio` DECIMAL(12,6) NOT NULL
- `estimated_cost_text_in` DECIMAL(12,6) NOT NULL
- `estimated_cost_text_out` DECIMAL(12,6) NOT NULL
- `estimated_cost_total` DECIMAL(12,6) NOT NULL

Indexes:
- UNIQUE `uq_stcl_request` (`speech_request_id`)
- INDEX `ix_stcl_user_time` (`user_id`,`created_at`)

FK:
- `speech_request_id` → `speech_requests(id)` ON DELETE CASCADE
- `user_id` → `users(id)` ON DELETE CASCADE

---

## 6. IP & Country Control

### 6.1 `ip_usage_monthly`
Tracks monthly usage per IP.

Fields:
- `id` BIGINT PK AI
- `user_ip` VARCHAR(45) NOT NULL
- `yyyymm` CHAR(6) NOT NULL
- `speech_count` INT NOT NULL DEFAULT 0
- `tokens_total_est` BIGINT NOT NULL DEFAULT 0
- `cost_total_est` DECIMAL(12,6) NOT NULL DEFAULT 0
- `updated_at` DATETIME NOT NULL

Indexes:
- UNIQUE `uq_ipm_ip_month` (`user_ip`,`yyyymm`)

---

### 6.2 `blocked_ips`

Fields:
- `id` BIGINT PK AI
- `user_ip` VARCHAR(45) NOT NULL
- `reason` VARCHAR(255) NULL
- `blocked_at` DATETIME NOT NULL
- `is_active` TINYINT(1) NOT NULL DEFAULT 1

Indexes:
- UNIQUE `uq_bip_ip` (`user_ip`)

---

### 6.3 `blocked_countries`

Fields:
- `id` BIGINT PK AI
- `country_code` CHAR(2) NOT NULL
- `reason` VARCHAR(255) NULL
- `blocked_at` DATETIME NOT NULL
- `is_active` TINYINT(1) NOT NULL DEFAULT 1

Indexes:
- UNIQUE `uq_bc_country` (`country_code`)

---

## 7. Settings & Configuration

### 7.1 `app_settings`
Key-value store for admin configuration.

Fields:
- `id` BIGINT PK AI
- `setting_key` VARCHAR(80) NOT NULL
- `setting_value` TEXT NULL
- `updated_at` DATETIME NOT NULL

Indexes:
- UNIQUE `uq_app_settings_key` (`setting_key`)

---

### 7.2 `env_versions`
Stores `.env` history for rollback.

Fields:
- `id` BIGINT PK AI
- `created_at` DATETIME NOT NULL
- `created_by_admin` VARCHAR(80) NULL
- `env_snapshot` MEDIUMTEXT NOT NULL
- `note` VARCHAR(255) NULL

Indexes:
- INDEX `ix_env_created_at` (`created_at`)

---

## 8. User Activity Logs

### 8.1 `user_activity_log`
Human-readable user timeline.

Fields:
- `id` BIGINT PK AI
- `user_id` BIGINT NOT NULL
- `event_type` VARCHAR(40) NOT NULL
- `title` VARCHAR(120) NOT NULL
- `details` VARCHAR(255) NULL
- `user_ip` VARCHAR(45) NULL
- `created_at` DATETIME NOT NULL

Indexes:
- INDEX `ix_ual_user_time` (`user_id`,`created_at`)

FK:
- `user_id` → `users(id)` ON DELETE CASCADE

---

## 9. Optional Session Persistence

### 9.1 `user_sessions`
For database-backed sessions (optional).

Fields:
- `id` BIGINT PK AI
- `user_id` BIGINT NOT NULL
- `session_token_hash` VARCHAR(255) NOT NULL
- `created_at` DATETIME NOT NULL
- `expires_at` DATETIME NOT NULL
- `revoked_at` DATETIME NULL
- `last_ip` VARCHAR(45) NULL

Indexes:
- UNIQUE `uq_usess_token` (`session_token_hash`)
- INDEX `ix_usess_user_exp` (`user_id`,`expires_at`)

FK:
- `user_id` → `users(id)` ON DELETE CASCADE

---

## 10. Indexing Strategy Summary

- Always index:
  - `users.email`
  - Foreign keys (`*_id`)
  - Date buckets (`yyyymm`, `yyyymmdd`)
  - Log tables by (`user_id`, `created_at`)

---

**End of Database Schema Specification (Phase 10)**

