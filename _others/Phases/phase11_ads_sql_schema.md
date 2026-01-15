# Phase 11 – Ads System SQL Schema

This document defines the **database schema** required for the Phase‑11 Ads System in VDraw. The schema is modular, privacy‑aware, and scalable.

---

## 1. ads (Master Table)

Stores all ads (Sponsor, Google/Third‑Party, Push).

```sql
CREATE TABLE ads (
  ad_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ad_title VARCHAR(255) NOT NULL,
  ad_type ENUM('sponsor','google','push') NOT NULL,
  network_name VARCHAR(100) NULL,
  ad_code MEDIUMTEXT NULL,
  audience ENUM('guest','logged_in','both') DEFAULT 'both',
  priority INT DEFAULT 0,
  status ENUM('active','paused','suspended','expired') DEFAULT 'active',
  start_datetime DATETIME NULL,
  end_datetime DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 2. ad_images (Sponsor Ads Media)

Supports **single image or slider** ads.

```sql
CREATE TABLE ad_images (
  image_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ad_id BIGINT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 1,
  FOREIGN KEY (ad_id) REFERENCES ads(ad_id)
);
```

---

## 3. ad_sizes

Predefined safe sizes.

```sql
CREATE TABLE ad_sizes (
  size_id INT PRIMARY KEY AUTO_INCREMENT,
  label VARCHAR(50),
  width INT,
  height INT
);
```

---

## 4. ad_apps

Maps ads to apps.

```sql
CREATE TABLE ad_apps (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ad_id BIGINT NOT NULL,
  app_key VARCHAR(50) NOT NULL,
  FOREIGN KEY (ad_id) REFERENCES ads(ad_id)
);
```

---

## 5. ad_placements

Defines where ads appear.

```sql
CREATE TABLE ad_placements (
  placement_id INT PRIMARY KEY AUTO_INCREMENT,
  placement_key VARCHAR(100) UNIQUE NOT NULL,
  description VARCHAR(255)
);
```

---

## 6. ad_placement_map

Maps ads to placements.

```sql
CREATE TABLE ad_placement_map (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ad_id BIGINT NOT NULL,
  placement_id INT NOT NULL,
  FOREIGN KEY (ad_id) REFERENCES ads(ad_id),
  FOREIGN KEY (placement_id) REFERENCES ad_placements(placement_id)
);
```

---

## 7. ad_impressions

Tracks ad views (privacy‑aware).

```sql
CREATE TABLE ad_impressions (
  impression_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ad_id BIGINT NOT NULL,
  user_id BIGINT NULL,
  ip_hash CHAR(64) NOT NULL,
  country VARCHAR(50),
  device ENUM('desktop','mobile','tablet'),
  os VARCHAR(50),
  browser VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ad_id) REFERENCES ads(ad_id)
);
```

---

## 8. ad_clicks

Tracks sponsor & push ad clicks only.

```sql
CREATE TABLE ad_clicks (
  click_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ad_id BIGINT NOT NULL,
  user_id BIGINT NULL,
  ip_hash CHAR(64) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ad_id) REFERENCES ads(ad_id)
);
```

---

## 9. push_ads_settings

Push‑specific configuration.

```sql
CREATE TABLE push_ads_settings (
  ad_id BIGINT PRIMARY KEY,
  display_type ENUM('modal','fullscreen','toast') DEFAULT 'toast',
  close_delay_seconds INT DEFAULT 3,
  allow_immediate_close BOOLEAN DEFAULT TRUE,
  min_active_users INT DEFAULT 0,
  frequency_limit INT DEFAULT 1,
  FOREIGN KEY (ad_id) REFERENCES ads(ad_id)
);
```

---

## 10. push_ads_schedule

Auto scheduling for push ads.

```sql
CREATE TABLE push_ads_schedule (
  schedule_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ad_id BIGINT NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  is_manual BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (ad_id) REFERENCES ads(ad_id)
);
```

---

## 11. ad_global_settings

System‑wide controls.

```sql
CREATE TABLE ad_global_settings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ads_enabled BOOLEAN DEFAULT TRUE,
  default_frequency_limit INT DEFAULT 3,
  ip_retention_days INT DEFAULT 14,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## Notes

- Google Ads do **not** use clicks table
- IPs are hashed before insert
- Old impression records may be anonymized via scheduled job
- Indexes should be added on `ad_id`, `created_at` for performance

**End of Phase‑11 SQL Schema**

