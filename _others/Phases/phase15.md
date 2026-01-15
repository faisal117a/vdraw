# Phase 15 – Comprehensive App Usage Tracking, Analytics & Rewards System

## Objective
Build a **robust, API‑safe, non‑blocking tracking system** for all VDraw apps that:
- Tracks detailed usage (daily/weekly/monthly)
- Never breaks the app if tracking API fails
- Produces **critical admin reports**
- Enables **real‑time live users monitoring**
- Introduces a **gamified points → credits reward system**

The entire system must remain **clean, modular, debuggable, and future‑proof**.

---

## Core Design Principles

1. **Fail‑Safe Tracking (Zero UX Impact)**
   - Tracking runs asynchronously (fire‑and‑forget)
   - Any API failure must be silently ignored
   - App logic must NEVER depend on tracking response

2. **Single Universal Tracking API**
   - Avoid bulky and repetitive code
   - All tracking done via ONE function

```js
track(appName, type, meta = {})
```

3. **Explicit Code Markers (Mandatory)**
Every tracking block MUST be wrapped as:

```js
// start tracking {Name} api code
track(...)
// end tracking {Name} api code
```

This allows:
- Easy removal
- Debugging
- Hot‑patch updates

---

## Universal Tracking Function (Concept)

### Parameters
| Parameter | Description |
|--------|------------|
| appName | App identifier (Stats, Linear, Graph, PyViz, DViz, etc.) |
| type | visit, button, document, session |
| meta | Object containing contextual data |

### Meta Examples
```json
{
  "user_type": "student",
  "country": "PK",
  "os": "Windows",
  "ip": "auto",
  "button": "Calculate",
  "document": "Chapter 3 – Trees"
}
```

---

## Data That MUST Be Tracked & Stored

### Common Fields (All Events)
- user_id (nullable for guests)
- user_type (student / teacher / guest)
- app_name
- event_type
- event_name
- country
- ip_address
- os
- device_type (mobile / desktop)
- session_id
- created_at (timestamp)

---

## Report 1 – App Usage Analytics
**(With Refresh Button)**

### Filters
- Date range (date‑wise)
- User search (name/email LIKE)

### Metrics
- Daily app visits (after login)
- Total student daily visits per app
- Total teacher daily visits per app
- Total home page visits
- Country‑wise visits per app
- IP‑wise visits per app
- Mobile visits per app
- OS‑wise visits per app

### Button Hit Tracking
| URL | Button |
|---|---|
| /Stats | Calculate |
| /Linear | Run Simulation |
| /Graph | Create Root |
| /Graph | Initialize Graph |
| /PyViz | Run Code |

### Derived Insights
- Apps ranking by usage (daily / weekly / monthly)

---

## Report 2 – Document Analytics (DViz)
**(With Refresh Button)**

### Tracking Rule
- Count ONLY when a document is actually opened

### Metrics
- Total document opens
- Grouped by Level Name
- Grouped by Chapter Name
- Top 10 most visited documents

---

## Report 3 – Real‑Time Live Users (CRITICAL)
**(With Refresh Button)**

### Definition
A user is considered **live** if:
- Logged in
- Session active within last X minutes (e.g. 5 min)

### Metrics
- Total live users per app
- Total live users per country
- Total live users per app + country

### Technical Note
- Session heartbeat ping every 60–90 seconds
- Auto‑expire inactive sessions

---

## New Feature – Points & Rewards System

### Rule
Each button action gives **1 point**:
- Calculate
- Run Simulation
- Create Root
- Initialize Graph
- Run Code

### Conversion Logic
- **20 points = 1 Speech‑to‑Code Credit**

### Redeem Logic (IMPORTANT)
Example:
- User has **19 points**
- User clicks Redeem
- System grants **1 credit**
- Remaining points = **9**

### User Dashboard
- Current Points
- Total Credits Earned
- Redeem Button
- Redemption History

### Purpose
- Encourage higher app engagement
- Increase feature usage organically

---

## Admin Safeguards
- No duplicate tracking per request
- Rate-limit tracking per session
- IP hashing for privacy (SHA-256, irreversible)
- **Global Tracking Kill-Switch (Admin Setting)**
  - Instantly disable all tracking without redeploy
  - Useful during incidents or performance issues
- Event-type ENUM enforcement (`visit`, `button`, `document`, `session`)

---

## Performance Strategy
- Use background queue / async request (fire-and-forget)
- Batch insert when possible
- Indexed tables on (date, app_name, event_type)
- Separate hot tables (live sessions) from cold analytics tables
- Auto-archive raw events older than defined retention period

---

## Antigravity – Mandatory Final Checklist

Antigravity MUST recursively verify and confirm:

### Tracking Implementation
- [ ] Universal `track()` function implemented
- [ ] Fail‑safe (no exception leaks)
- [ ] All tracking blocks wrapped with start/end comments
- [ ] Tracking added to ALL listed apps
- [ ] Button hit tracking verified

### Reports
- [ ] Report 1 metrics complete
- [ ] Report 1 filters working
- [ ] Report 2 document tracking correct
- [ ] Top 10 documents logic verified
- [ ] Report 3 live session logic accurate

### Rewards System
- [ ] Points increment on every button hit
- [ ] Points visible on user dashboard
- [ ] Redeem logic correct (20 → 1 credit)
- [ ] Partial points handled correctly

### Stability & QA
- [ ] Tracking API failure does NOT affect app
- [ ] Refresh buttons re‑query data correctly
- [ ] Performance tested under load

### Completion Rule
If **ANY checkbox remains unchecked**, Antigravity MUST:
1. Clearly list pending items
2. Ask permission to proceed
3. Resume until ALL are confirmed complete

---

## Decisions Locked (Confirmed)

- Tracking Scope: **Internal only (Admin analytics)**
- No external API exposure
- No advertiser or partner data sharing

---

## Open Suggestions / Questions (For Later Phases)

1. Guest users: full tracking or anonymized summary only?
2. Country detection source: IP-based vs user profile override?
3. Report exports (CSV / Excel) needed for management?
4. Retention policy: keep raw events for 90 days or 180 days?
5. Rewards system: admin override / manual credit grant needed?

---

**End of Phase 15 Specification**
