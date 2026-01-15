# Optimization Options for Slow Connections

## Problem 1: UI Disruption Validation (Handshake)
**Issue:** The security handshake destroys the UI if it doesn't receive a response within 2-3 seconds, which causes false positives on slow networks.

### Option 1: Extended Retry Strategy (Recommended)
Increase the initial timeout to **10 seconds** and implement a **retry mechanism** (3 attempts with 2s delay between them). Only if all 3 fail will the UI be disrupted.
*   **Pros:** greatly reduces false positives; robust.
*   **Cons:** Real attacks might have a 15-20s window of UI usage before destruction.

### Option 2: Soft Failure / Feature Lock
If the handshake times out, **do not destroy the UI**. Instead, disable critical buttons (Run, Save, Export) and show a "Reconnecting..." toast. Continue checking in the background. Only destroy UI if the server explicitly responds "BANNED".
*   **Pros:** Best user experience; no accidental destruction.
*   **Cons:** Unprotected view-only access is allowed during network issues.

### Option 3: Passive Monitoring
Remove the timeout trigger for destruction entirely. Only destroy the UI if the server explicitly returns a "Failure" status. Treat timeouts as "Offline Mode" (allow full usage).
*   **Pros:** Zero disruption for legitimate users.
*   **Cons:** Weakest security; blocking the API URL effectively bypasses protection.

---

## Problem 2: PyViz Runtime Load Timeout
**Issue:** Pyodide (~19MB) takes longer than the hardcoded 30s limit to download on slow connections, causing a "Runtime Load Timeout".

### Option 1: Extended Timeout (Simple)
Increase the hardcoded timeout from **30 seconds to 300 seconds (5 minutes)**.
*   **Pros:** Simple implementation; allows sufficient time for 3G networks.
*   **Cons:** If the download is truly stuck, the user waits 5 minutes before seeing an error.

### Option 2: Adaptive Timeout with User Feedback (Recommended)
Set initial timeout to 60s. If it expires, **ask the user**: "Runtime is still loading (Slow Connection). Keep waiting?". If they click "Wait", extend for another 120s. If they click "Cancel", show the error.
*   **Pros:** Keeps user informed; avoids silent "hanging".
*   **Cons:** Requires UI implementation (modal/toast).

### Option 3: Pre-Cache Service Worker
Implement a Service Worker to cache the Pyodide runtime in the background.
*   **Pros:** Instant load on subsequent visits; offline support.
*   **Cons:** Complex implementation; significantly higher code complexity; doesn't solve the *first* load issue.
