# 🔴 Critical Prompt for Antigravity  
## Audio Token Calculation, Costing & Abuse-Safe Business Logic

This document defines **mandatory, non-negotiable rules** for implementing audio token calculation, cost accounting, and quota enforcement in the application.

The system now stores **Audio Tokens / Second** as a configurable value in the **database** (currently `100`).  
All logic MUST dynamically rely on this DB value.

---

## 1. Single Source of Truth (MANDATORY)

- **Audio Tokens / Second MUST be read from the database**
- Hardcoding values like `100`, `700`, or `1000` is **strictly forbidden**
- Any future DB change must automatically affect:
  - Token calculation
  - Cost estimation
  - Quota enforcement

➡️ Any hardcoded value is considered a **critical bug**

---

## 2. Backend Is the Authority (NON-NEGOTIABLE)

- Frontend-sent duration is **informational only**
- Backend MUST:
  - Parse the uploaded audio file
  - Extract **actual duration from audio metadata**
  - Ignore frontend duration if mismatched

❌ Never trust JS timers, UI counters, or request payload values

---

## 3. Duration Rules (LOCKED ORDER)

Apply these rules **strictly in order**:

1. Read real audio duration from the file
2. Cap duration to **maximum 7 seconds**
3. Enforce **minimum billable duration = 1 second**

### Examples
| Actual Duration | Billable Duration |
|----------------|------------------|
| 0.3 sec | 1 sec |
| 2.8 sec | 2.8 sec |
| 9.4 sec | 7 sec |

---

## 4. Token Calculation Formula (REQUIRED)

```
tokens = ceil(
  billable_duration_seconds
  × audio_tokens_per_second_from_db
)
```

### Example (DB value = 100)
| Duration | Tokens |
|--------|--------|
| 1 sec | 100 |
| 3.2 sec | 320 |
| 7 sec | 700 |

⚠️ This calculation MUST exist **server-side only**

---

## 5. Hard Limits Enforcement

Before calling **any** audio / STT model:

- Reject request if:
  - Billable duration exceeds max
  - Tokens exceed per-request limit
  - User quota < required tokens

❌ Do NOT call the audio model if validation fails

---

## 6. Cost & Quota Accounting Rules

- Deduct tokens **after validation**
- Deduct tokens **even if transcription fails**
- Tokens represent **compute cost**, not successful output

No free retries  
No silent probing  
No “failed request = free request”

---

## 7. System-Wide Consistency Audit (MANDATORY)

Antigravity MUST audit and align:

- Audio upload handlers
- Token calculation logic
- Cost estimation logic
- User quota enforcement
- Admin dashboard displays
- Logs and usage records

➡️ Every component must use:
- The **same DB-driven tokens/sec value**
- The **same duration rules**
- The **same formula**

---

## 8. Logging & Traceability (REQUIRED)

For **every audio request**, log:

- User ID
- Real audio duration
- Tokens/sec value read from DB
- Final tokens charged
- Outcome (success / rejected / failed)

These logs are required for:
- Billing audits
- Abuse detection
- Support & dispute resolution

---

## 9. No-Assumptions Policy

If any value or rule is unclear:

- ❌ Do NOT guess
- ❌ Do NOT invent defaults
- ✅ Ask explicitly

Silent assumptions are unacceptable in billing logic.

---

## 10. Expected Outcome (Acceptance Criteria)

After implementation:

- Changing **Audio Tokens / Sec** in DB instantly affects:
  - Cost calculation
  - User quota usage
  - Request limits
- No hardcoded values remain
- Backend fully controls billing accuracy
- System is resistant to manipulation and abuse

---

## 🔐 Final Note

This logic is **core SaaS infrastructure**, not a feature.

Any deviation from this document is considered:
- A billing bug
- A security risk
- A production-blocking issue

