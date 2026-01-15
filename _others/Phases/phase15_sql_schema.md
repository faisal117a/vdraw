# Phase 15 – SQL Schema (.md)

> **Scope:** Internal-only Admin Analytics & Rewards System
>
> Designed for **high-write tracking**, **safe analytics**, and **real-time sessions** without impacting app performance.

---

## 1. users (existing / reference)
Assumed existing table.

```sql
-- reference only
users(
  id BIGINT PK,
  name VARCHAR(150),
  email VARCHAR(150),
  user_type ENUM('student','teacher','admin'),
  country_code CHAR(2)
)
```

---

## 2. tracking_events (CORE ANALYTICS TABLE)

Stores **all historical analytics events** (cold data).

```sql
CREATE TABLE tracking_events (
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
);
```

---

## 3. tracking_live_sessions (HOT TABLE – REAL TIME)

Used ONLY for **Report 3 (Live Users)**.

```sql
CREATE TABLE tracking_live_sessions (
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
);
```

> A user is **LIVE** if `last_heartbeat >= NOW() - INTERVAL 5 MINUTE`

---

## 4. tracking_documents (DViz DOCUMENT OPENS)

Dedicated table for **Report 2**.

```sql
CREATE TABLE tracking_documents (
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
);
```

---

## 5. user_points (CURRENT BALANCE)

Stores **current usable points**.

```sql
CREATE TABLE user_points (
  user_id BIGINT PRIMARY KEY,

  points INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 6. user_point_logs (AUDIT & HISTORY)

Every increment & redemption is logged.

```sql
CREATE TABLE user_point_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,

  user_id BIGINT NOT NULL,
  action ENUM('earn','redeem') NOT NULL,

  points INT NOT NULL,
  related_event VARCHAR(100) NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_user (user_id),
  INDEX idx_action (action)
);
```

---

## 7. user_credits (SPEECH‑TO‑CODE CREDITS)

```sql
CREATE TABLE user_credits (
  user_id BIGINT PRIMARY KEY,

  credits INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 8. user_credit_logs (REDEMPTION HISTORY)

```sql
CREATE TABLE user_credit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,

  user_id BIGINT NOT NULL,
  credits_added INT NOT NULL,
  points_consumed INT NOT NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_user (user_id)
);
```

---

## 9. admin_settings (KILL‑SWITCH & CONTROLS)

```sql
CREATE TABLE admin_settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value VARCHAR(100) NOT NULL
);
```

### Required Seed Values
```sql
INSERT INTO admin_settings VALUES
('tracking_enabled','1'),
('points_enabled','1'),
('live_session_timeout_minutes','5');
```

---

## Referential & Operational Rules

- `tracking_events` → cold analytics (archive after 90/180 days)
- `tracking_live_sessions` → truncate stale rows via cron
- IPs MUST be hashed before insert
- All inserts must be **non-blocking / async**

---

## Antigravity SQL Checklist

- [ ] All tables created successfully
- [ ] Indexes verified
- [ ] JSON meta support enabled
- [ ] Cron job defined for live session cleanup
- [ ] Points & credits atomic updates tested

---

**End of Phase 15 – SQL Schema**

