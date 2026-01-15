# Phase 11 – Ads System (VDraw)

## Overview
Phase 11 introduces a complete, admin-controlled advertising system for VDraw. The system supports **Sponsor Ads**, **Google/Third‑Party Ads**, and **Push Ads**, with strict discipline, privacy awareness, performance safety, and detailed reporting. Ads can be shown to **guests**, **logged‑in users**, or **both**, depending on configuration.

This phase is designed to be **SaaS‑grade**, extensible, and policy‑safe.

---

## Core Principles

- Ads are **fully controlled from Admin Dashboard**
- Ads load **asynchronously** to avoid performance impact
- Global **Kill Switch** to disable all ads instantly
- Strict **frequency control** for Push Ads
- Privacy‑aware analytics (IP anonymization)
- Google Ads are treated as **external & untouched**

---

## Ad Types

### 1. Sponsor Ads
Manually created ads for educational institutes or commercial sponsors.

**Features**
- Image‑based ads (Single Image or Slider)
- Clickable target URL
- Fixed predefined sizes
- Full reporting

**Required Fields**
- Ad Title (internal)
- Ad Type: Sponsor
- Image(s): single or multiple
- Size (predefined)
- Target URL
- Audience: Guest / Logged‑in / Both
- Allowed Apps (multi‑select)
- Allowed Placements (multi‑select)
- Priority (integer)
- Status: Active / Paused / Suspended
- Start Date / End Date (optional)

**Predefined Sizes**
- 300×300 (Square)
- 336×280 (Rectangle)
- 728×90 (Horizontal)
- 970×90 (Wide Bar)
- 300×600 (Sidebar)

---

### 2. Google Ads / Other Networks
Used for Google AdSense or any third‑party ad network.

**Key Rule**: VDraw does NOT manage size, clicks, or targeting.

**Fields**
- Ad Title
- Ad Type: Google / Third‑Party
- Network Name
- Code Snippet (JS/HTML)
- Audience
- Allowed Apps
- Allowed Placements
- Status

**Rules**
- No click tracking
- No code modification
- No aggressive placement
- No popup usage

---

### 3. Push Ads (Popup Ads)
Popup‑style ads displayed as modal, overlay, or notification.

**Types**
- Modal Popup
- Full Screen Overlay
- Toast Notification (recommended)

**Modes**
- Auto Scheduled
- Manual Push

**Common Fields**
- Ad Title
- Ad Type: Push
- Content (image + text)
- Display Type
- Close Button Delay (seconds)
- Allow Immediate Close (Yes/No)
- Audience
- Target Apps
- Priority
- Status

**Auto Schedule Fields**
- Start Date & Time
- End Date & Time
- Min Active Users Condition
- Frequency Cap (per user)

**Manual Push Fields**
- Push Now Button
- App Selection
- Audience Selection

---

## Ad Placement System

Ads are rendered dynamically based on **placement keys**.

### Home Page
- Between PyViz Explainer and DViz Library Icon Boxes

### VDraw Landing Page (After Login)
- Ad 1: Under paragraph “Enter data manually or upload a file…”
- Ad 2: Between “Visualization” and “Detailed Statistics” panels

### PDraw Studio
- Ad 1: Right sidebar under “Diagram” (top)
- Ad 2: Under “Execution Trace” after horizontal line

### TGDraw Lab
- Ad 1: Above content
- Ad 2: Below content

### PyViz
- Ad 1: Inside Feedback Panel at bottom
- Ad 2: Above “Action Log” in right sidebar

### DViz
- Ad 1: Under “Levels” title (after line)
- Ad 2: Main area, right side of heading/subheading

---

## Ad Rotation Logic

1. Filter ads by:
   - App
   - Placement
   - Audience
   - Status
   - Date validity
2. Sort by Priority (higher first)
3. Rotate ads with same priority
4. Ignore expired or suspended ads

---

## Admin Panel Structure

**Sidebar → Settings → Ads**

### Sections
- Ads List
- Create / Edit Ad
- Push Ads Scheduler
- Reports
- Global Controls

### Global Controls
- 🔴 Disable All Ads (Kill Switch)
- Default Frequency Limits
- Default Privacy Retention Days

---

## Reporting System

### Sponsor Ads Report

- Total Impressions
- Unique Viewers
- Total Clicks
- CTR (%)
- Date‑wise Graph
- App‑wise Distribution
- Device Split (Mobile/Desktop)
- OS Breakdown
- Browser Breakdown
- Top Countries

**Privacy Rules**
- Raw IP stored temporarily
- IP anonymized after defined days
- Reports never expose raw IPs

---

### Push Ads Report

- Total Push Events
- Total Views
- Total Clicks
- CTR
- Date‑wise Performance
- App‑wise Performance

---

## Performance & UX Rules

- Ads load asynchronously
- Ads must not block learning flow
- Push Ads must respect frequency caps
- No Google Ads in critical workflows

---

## Future‑Ready Notes

- Multi‑currency sponsorship
- Geo‑targeted sponsor ads
- Sponsor self‑service portal (optional)
- Ad A/B testing (optional)

---

## Final Note

This Ads system is designed to be **ethical, disciplined, and scalable**, suitable for an educational SaaS platform with commercial sustainability.

**End of Phase 11 Specification**

