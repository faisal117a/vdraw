# Job: Output Result for Loops

## Status
- [ ] Fix Loop Output Glitch (Real-time display, Clear previous result).
- [ ] Handle Third-Party Imports (Skip import hangs, Mock for usage error).
- [ ] Update Inspector Stats (Split Packages into StdLib vs 3rdParty).

## Glitch Details
-   **Issue**: Loop results not displaying per cycle. 
-   **Issue**: Previous result persists until new output arrives.
-   **Fix**: Ensure `stdout` is flushed to UI immediately during animation steps. Ensure `output` area is cleared *immediately* upon clicking Run.

## Import Handling
-   **Issue**: `import numpy` hangs/stalls.
-   **Fix**: Wrap imports in `try/except`. If fails (or if we preemptively decide to skip loading heavy libs), assign a Mock object that raises `ImportError` output only when accessed.

## Inspector Stats
-   **Requirement**: "Pack/Lib => X/Y".
-   **Logic**: 
    -   Standard Libs (math, random, etc.) -> Pack.
    -   External (numpy, pandas, etc.) -> Lib.
