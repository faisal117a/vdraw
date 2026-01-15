# Phase 14 — Email Verification System (MANDATORY)

## Objective
Implement a complete email verification system for user signup.
The system MUST work end-to-end even if SMTP fails on localhost.

---

## Core Flow (REQUIRED)

### 1. User Signup
When a user signs up:
- Create user record normally
- Set:
  - email_verified = 0
  - verification_code = random 6-digit numeric code
  - verification_expires_at = current_time + 15 minutes (recommended)

DO NOT block signup for any reason.

---

### 2. Verification Email (SMTP — Non-Blocking)
After user record is created:

- Generate:
  - 6-digit numeric verification code
  - Verification link

Example link:
http://localhost/vdraw-anti-auto/verify-email?email=USER_EMAIL

- Email content MUST include:
  - The 6-digit verification code
  - The verification link
  - Clear instruction to enter the code after clicking the link

IMPORTANT:
- SMTP MUST be fully implemented
- If SMTP fails:
  - DO NOT stop execution
  - DO NOT throw fatal error
  - Log the error
  - Continue application flow normally

(Localhost environment MUST tolerate SMTP failure)

---

### 3. Verification Page (Frontend)
When user opens the verification link:
- Show Email Verification page
- Page MUST contain:
  - Email (readonly or hidden)
  - Input field for 6-digit code
  - Verify button

If email is already verified:
- Show message: "Email already verified"
- Provide Login link

---

### 4. Code Validation Logic
On submit:
- Validate:
  - Email exists
  - verification_code matches
  - verification_code is not expired (if expiry is enabled)

If valid:
- Set email_verified = 1
- Clear verification_code
- Clear verification_expires_at
- Show success message:
  "Email verified successfully"
- Show Login page link

If invalid:
- Show error:
  "Invalid or expired verification code"

---

### 5. Login Restriction (MANDATORY)
Login MUST be blocked if:
email_verified != 1

Error message on login attempt:
"Please verify your email before logging in."

---

### 6. Admin Override (Temporary Requirement)
Admin dashboard MUST allow:
- Manually setting email_verified = 1

This is required because SMTP may fail on localhost.

---

## Database Requirements
Ensure users table has at least:
- email
- email_verified (TINYINT / BOOLEAN)
- verification_code (VARCHAR or INT)
- verification_expires_at (DATETIME, optional but recommended)

---

## Security Rules
- Verification code MUST be:
  - Numeric only
  - Exactly 6 digits
- Reject reuse of verification code
- Reject verification attempts for already verified users
- Basic rate limiting is recommended (not mandatory)

---

## Environment Rule
- Application base URL:
  http://localhost/vdraw-anti-auto/
- SMTP errors MUST NOT crash or block the application
- Production hard-fail SMTP enforcement can be added later

---

## Completion Checklist (MUST VERIFY)
1. Signup works even if SMTP fails
2. Verification email logic exists
3. Verification page works correctly
4. Login is blocked until email is verified
5. Admin can manually verify user
6. No fatal error occurs due to SMTP

---

END OF PHASE 14

