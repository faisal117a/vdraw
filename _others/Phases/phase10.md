# Phase 10 – User Authentication, Credits & Cost Control System (PyViz)

## 1. Phase Overview
Phase 10 introduces a **full authentication, authorization, and cost‑control layer** for PyViz. This phase protects expensive features (Speech‑to‑Code, Editor Mode, Import File) while enabling detailed usage tracking, token accounting, and administrative control.

This phase is designed to be **self‑hosted (PHP + MySQL)**, scalable, auditable, and upgrade‑ready for future OTP login or paid plans.

---

## 2. Feature Access Rules
The following features are **restricted to authenticated users only**:
- Speech‑to‑Code (Microphone)
- Editor Mode
- Import File

If a guest clicks any restricted feature:
- A modern login modal is shown
- After successful login, the user is redirected back to the requested action

---

## 3. Authentication System

### 3.1 Login Method
- Email + Password
- Passwords stored using secure hashing (bcrypt / argon2)
- Architecture supports future upgrade to **Email + OTP** login

### 3.2 Registration Fields
Required fields:
- Full Name
- Email (unique)
- Password
- Phone number (valid format with country code)
- Country (auto‑detected from IP)
- Role selection: Student / Teacher

### 3.3 Email Verification
- 6‑digit verification code
- Code expires after configurable time
- User cannot access restricted features until email is verified

### 3.4 Password Recovery
- Email‑based reset
- 6‑digit OTP verification

---

## 4. Student & Teacher System

### 4.1 Teacher Verification
Teachers can request verification from their dashboard:
- Upload campus/teacher ID card
- Allowed formats: JPG, PNG, PDF
- Max size: 3MB
- Re‑upload allowed after rejection

### 4.2 Verification Workflow
1. Teacher submits verification request
2. Status becomes `pending`
3. Admin reviews request
4. Admin approves or rejects
5. User is notified via email with friendly message

### 4.3 Verified Teacher Benefits
- Higher **daily speech credits**
- Higher **monthly speech credits**
- Limits are defined in admin settings

Admin can manually verify/unverify any user.

---

## 5. Credit System (Speech‑to‑Code)

### 5.1 Credit Definition
- 1 Credit = 1 Speech‑to‑Code action

### 5.2 Limits
- Daily credit limit (admin defined)
- Monthly credit limit (admin defined)
- Separate limits for:
  - Normal users
  - Verified teachers

### 5.3 Credit Deduction Rules
Configurable from admin panel:
- Default: credit deducted **after successful code generation**
- On failure: credit is **refunded**

Credits are reserved during processing to prevent double spending.

---

## 6. Token & Cost Accounting

### 6.1 Token Tracking (Per Request)
For each speech action:
- Audio input tokens (STT)
- Text input tokens (LLM)
- Text output tokens (LLM)

### 6.2 Cost Calculation
Admin defines cost per 1M tokens for:
- Audio model (e.g., Whisper)
- Text model (e.g., DeepSeek)

Each speech action stores:
- Audio token cost
- Text token cost
- Total estimated cost

### 6.3 Reports
- User‑wise token usage
- User‑wise cost usage
- Monthly and lifetime summaries

---

## 7. IP & Country Controls

### 7.1 IP Tracking
- User IP stored on:
  - Login
  - Speech‑to‑Code usage

### 7.2 Limits
- Monthly IP usage limit (admin defined)

### 7.3 Abuse Protection
- Detect multiple users from same IP
- Admin can:
  - Block specific IPs
  - Block entire countries

Blocked users see a friendly message explaining the restriction.

---

## 8. Dashboards

### 8.1 User Dashboard
Features:
- Profile management
- Email & phone status
- Teacher verification submission
- Daily & monthly credits usage
- Token usage & cost estimation
- Login / logout activity log (human‑readable)
- Export reports: CSV, PDF

### 8.2 Admin Dashboard
Features:
- Total users
- Verified / unverified teachers
- Total credits used
- Total token usage
- Total estimated cost
- Blocked IPs
- Blocked countries
- Export reports: CSV, PDF

---

## 9. Logs & Activity Timeline

Logs are stored as **human‑readable events**, not raw text.

Examples:
- "Logged in from Pakistan (Chrome, Windows)"
- "Speech limit exceeded (Daily quota used)"
- "Teacher verification approved"
- "Logged out successfully"

Each user has a separate activity timeline.

---

## 10. Feature Toggles (Admin Controlled)

Admin can enable or disable:
- Speech‑to‑Code
- Editor Mode
- Import File
- New registrations

When a feature is disabled:
- Custom admin‑defined message is shown to users

---

## 11. Email System (SMTP)

### 11.1 SMTP Settings
Configured from admin panel:
- Host
- Port
- Username
- Password
- Encryption

### 11.2 Email Templates (Phase 10)
Fixed templates for:
- Email verification
- Password reset
- Teacher approval
- Teacher rejection

Editable templates can be added in later phases.

---

## 12. Environment Configuration (.env Management)

Admin can update `.env` values from dashboard.
Changes are saved **blindly** and versioned for rollback.

```env
# ================================
# Speech-to-Text Configuration
# ================================
STT_PROVIDER=
STT_MODEL=
STT_API_KEY=

# ================================
# Python Code Generation (LLM)
# ================================
LLM_PROVIDER=
LLM_MODEL=
LLM_API_KEY=

# ================================
# Safety & Cost Controls
# ================================
MAX_AUDIO_SECONDS=10
MAX_OUTPUT_TOKENS=30
```

---

## 13. Security Measures
- Secure password hashing
- Login rate limiting
- Temporary lock after repeated failures
- Soft delete for users
- Account suspension support
- Consent timestamp stored

---

## 14. Roles & Permissions

Roles:
- Guest
- Authenticated User
- Verified Teacher
- Super Admin

Single Super Admin model is used in Phase 10.

---

## 15. Phase Completion Criteria

Phase 10 is complete when:
- All restricted features require authentication
- Credit limits are enforced correctly
- Token & cost logs are accurate
- Admin can fully control limits, costs, and access
- Reports are exportable
- System prevents abuse and uncontrolled API costs

---

**End of Phase 10 Specification**

