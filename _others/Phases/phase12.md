# VDraw JavaScript Handshake Protection

## Purpose
This document provides **mandatory instructions for Antigravity** to implement a **JavaScript handshake + self‑break mechanism** across all VDraw / PyViz style applications.

The goal is to ensure:
- JavaScript **cannot run locally (file://)**
- JavaScript **cannot run without server connectivity**
- Stolen or copied JS **breaks itself automatically**
- No clear error messages are shown to attackers

This is **client‑side deterrence**, always combined with **server‑side enforcement**.

---

## Rollout & Deployment Strategy (MANDATORY)

Antigravity MUST follow a **phased rollout strategy** to avoid accidental disruption.

### Phase 1 – PyViz (Pilot Application)

1. Implement the handshake protection **ONLY in PyViz** first
2. Deploy to:
   - `localhost` (development testing)
   - Remote test/staging server
3. Verify the following scenarios:
   - ✅ Works on localhost with running server
   - ✅ Works on remote server
   - ❌ Fails when opened via `file://`
   - ❌ Fails when JS files are downloaded and run directly

Only after **all checks pass** may Antigravity proceed to Phase 2.

### Phase 2 – Other Applications

After PyViz is confirmed stable:

- Apply the **same handshake mechanism** to:
  - VDraw
  - All other current and future apps

The same code block and rules MUST be reused consistently.

No application other than PyViz may receive this change before Phase 1 validation is complete.

---

## Handshake Rules

1. JS must request a **server handshake endpoint**
2. If the endpoint:
   - Does not exist
   - Is unreachable
   - Returns non‑OK response
   - Returns invalid JSON

   ➜ the app must **break itself intentionally**

3. If handshake is valid ➜ app continues normally

---

## Required Server Contract

The server MUST expose a route:

```
GET /api/handshake
```

Expected JSON response:

```
{
  "status": "OK",
  "app": "vdraw",
  "ts": 1736400000
}
```

Anything else is treated as **failure**.

---

## Mandatory JavaScript Code (DO NOT MODIFY LOGIC)

### ⚠️ CRITICAL COMMENT MARKERS (HIGH PRIORITY)

Antigravity MUST add **explicit comment markers** so this security layer can be **easily found, audited, or disabled in future**.

These comments are **MANDATORY**.

---

### Required Comment Rules

1. The **entire handshake script** MUST be wrapped with:

```js
// start DP code
```

at the very beginning, and:

```js
// end DP code
```

at the very end.

2. **All intentional break / sabotage code** MUST be wrapped with:

```js
// start system application call
```

before the first break statement, and:

```js
// end system application call
```

after the last break statement.

These markers are required so the code can be quickly identified and disabled if needed.

---

### Mandatory JavaScript Block (WITH REQUIRED COMMENTS)

```js
// start DP code
/* === VDraw Handshake Guard (DO NOT REMOVE) === */
(async function VDrawHandshakeGuard() {

    const HANDSHAKE_URL = "/api/handshake";
    const TIMEOUT_MS = 3000;

    function injectBreakage() {
        try {
            // start system application call

            // 1. Global corruption flag
            window.__VDRAW_BROKEN__ = true;

            // 2. Break network layer
            Object.defineProperty(window, "fetch", {
                value: () => Promise.reject("Handshake failed"),
                writable: false
            });

            // 3. Break header execution
            document.head.innerHTML += "<script>throw new Error('VDRAW:HEADER_CORRUPTED')</script>";

            // 4. Break mid-execution logic
            setTimeout(() => {
                while (true) {}
            }, 10);

            // 5. Break footer / late lifecycle
            window.addEventListener("load", () => {
                throw new Error("VDRAW:APP_DISABLED");
            });

            // end system application call

        } catch (e) {
            // silent by design
        }
    }

    function timeoutPromise(ms) {
        return new Promise((_, reject) =>
            setTimeout(() => reject("timeout"), ms)
        );
    }

    try {
        const response = await Promise.race([
            fetch(HANDSHAKE_URL, {
                method: "GET",
                credentials: "include",
                headers: {
                    "X-App-Handshake": "vdraw"
                }
            }),
            timeoutPromise(TIMEOUT_MS)
        ]);

        if (!response || !response.ok) {
            injectBreakage();
            return;
        }

        const data = await response.json();

        if (!data || data.status !== "OK") {
            injectBreakage();
            return;
        }

        window.__VDRAW_HANDSHAKE_OK__ = true;

    } catch (err) {
        injectBreakage();
    }

})();
// end DP code
```
js
/* === VDraw Handshake Guard (DO NOT REMOVE) === */
(async function VDrawHandshakeGuard() {

    const HANDSHAKE_URL = "/api/handshake";
    const TIMEOUT_MS = 3000;

    function injectBreakage() {
        try {
            // 1. Global corruption flag
            window.__VDRAW_BROKEN__ = true;

            // 2. Break network layer
            Object.defineProperty(window, "fetch", {
                value: () => Promise.reject("Handshake failed"),
                writable: false
            });

            // 3. Break header execution
            document.head.innerHTML += "<script>throw new Error('VDRAW:HEADER_CORRUPTED')</script>";

            // 4. Break mid‑execution logic
            setTimeout(() => {
                while (true) {}
            }, 10);

            // 5. Break footer / late lifecycle
            window.addEventListener("load", () => {
                throw new Error("VDRAW:APP_DISABLED");
            });

        } catch (e) {
            // silent by design
        }
    }

    function timeoutPromise(ms) {
        return new Promise((_, reject) =>
            setTimeout(() => reject("timeout"), ms)
        );
    }

    try {
        const response = await Promise.race([
            fetch(HANDSHAKE_URL, {
                method: "GET",
                credentials: "include",
                headers: {
                    "X-App-Handshake": "vdraw"
                }
            }),
            timeoutPromise(TIMEOUT_MS)
        ]);

        if (!response || !response.ok) {
            injectBreakage();
            return;
        }

        const data = await response.json();

        if (!data || data.status !== "OK") {
            injectBreakage();
            return;
        }

        // Handshake success
        window.__VDRAW_HANDSHAKE_OK__ = true;

    } catch (err) {
        injectBreakage();
    }

})();
```

---

## Mandatory Behavior on Failure

When handshake fails, the app MUST:

1. Corrupt global runtime state
2. Break fetch / API access
3. Inject failing script into document head
4. Freeze UI execution loop
5. Crash app during load completion

No alerts. No console warnings. No user‑friendly messages.

---

## Execution Environment Rule (UPDATED)

Antigravity MUST apply the following execution rules:

### Allowed Environments
The handshake **MUST PASS** and the app **MUST WORK** when:

- Running on `http://localhost` or `http://127.0.0.1`
- Running on any valid remote server domain
- Handshake endpoint (`/api/handshake`) is reachable

### Strictly Blocked Environment
The handshake **MUST FAIL** and the app **MUST BREAK ITSELF** when:

- JavaScript is executed via `file://` protocol
- Files are opened directly from disk after download
- No active web server is present

Localhost is treated as a **valid server**, but direct file execution is **never allowed**.

---

## Obfuscation Rule

After insertion:

- Obfuscate this block separately
- Do NOT rename `HANDSHAKE_URL`
- Do NOT remove breakage logic
- Do NOT simplify infinite loop

---

## Final Enforcement Rule

Antigravity MUST treat this handshake as:

> **Non‑removable, non‑optional, mandatory security layer**

Any build missing this block is considered **INVALID**.

---

## Final Note

This mechanism is **not standalone security**.
It is a **client‑side breaker** that must always be paired with:

- Server‑side permission checks
- Token/session validation
- Feature gating on backend

End of instructions.

---

## Final Verification Checklist (MANDATORY)

After completing the implementation, **Antigravity MUST re-check every item below** before marking the task as complete:

1. **Handshake Injection**
   - The handshake script is inserted at the **top of every main JS entry file** (pilot: PyViz only).
   - The script is wrapped with the exact comments:
     - `// start DP code`
     - `// end DP code`

2. **Breakage Markers**
   - All intentional break/sabotage logic is fully wrapped between:
     - `// start system application call`
     - `// end system application call`
   - No break logic exists outside these markers.

3. **Environment Behavior**
   - App works on **http://localhost** with a running server.
   - App works on **remote server** with valid `/api/handshake` response.
   - App **fails when opened via `file://`** or when files are run directly after download.

4. **Server Contract**
   - `/api/handshake` endpoint exists and returns valid JSON with `status: OK`.
   - Any invalid, missing, or unreachable response triggers breakage.

5. **Rollout Control**
   - The protection is applied **only to PyViz** during Phase 1.
   - No other apps are modified before PyViz validation is complete.

6. **No User-Facing Messages**
   - No alerts, toasts, or friendly error messages are shown when breakage occurs.

7. **Obfuscation Safety**
   - The handshake block may be obfuscated, but **comment markers remain intact**.
   - The logic flow is not simplified or altered.

8. **Reversibility**
   - The DP code and system application call blocks can be easily located and disabled using the comment markers.

Only when **ALL items above are verified** may Antigravity mark the task as **DONE**.


