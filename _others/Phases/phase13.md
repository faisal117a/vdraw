# Index.php UI Handshake & Disruption Instructions (Antigravity)

## Objective
Add a **lightweight handshake check** inside `index.php` (main layout file) so that **if the app is opened without a server or the handshake fails, the UI is completely destroyed**.

This protects the **HTML/layout layer**, not just JS files.

---

## Where to Apply

- File: `index.php`
- Insert the script:
  - Preferably **just before `</body>`**
  - Can also be placed inside `<head>` if needed

---

## Mandatory Behavior

The injected script MUST:

1. Allow normal operation when:
   - App runs on `http://localhost` or a real server
   - `/api/handshake` responds with `{ status: "OK" }`

2. Immediately disrupt UI when:
   - App is opened via `file://`
   - Server is unreachable
   - Handshake endpoint fails or returns invalid data

---

## UI Disruption Rules

When disruption is triggered, the script MUST:

- Delete **all page content** (`document.body.innerHTML = ""`)
- Leave a blank or broken-looking page
- Show **no alerts, messages, or console warnings**

---

## Mandatory Comment Markers (HIGH PRIORITY)

Antigravity MUST wrap the code using these exact comments:

- Entire script:
  - `// start DP code`
  - `// end DP code`

- All UI-breaking logic:
  - `// start system application call`
  - `// end system application call`

These comments are mandatory so the protection can be easily found, disabled, or removed later.

---

## Rollout Rule

- Apply this change **only after PyViz JS handshake is confirmed working**
- Do NOT modify other apps until PyViz is validated

---

## Final Check (Required)

Before marking task complete, Antigravity MUST confirm:

1. `index.php` contains the handshake script
2. Required comment markers exist exactly as specified
3. UI is destroyed on `file://` execution
4. UI works normally on localhost and server

End of instructions.

