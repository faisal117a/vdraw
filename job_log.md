# Job Log

## [Job: Fix Login Redirect] - 2026-01-12
- **Purpose**: Fix incorrect login redirect URL for user 'adin'.
- **Changes**: 
    - Corrected redirect logic in `index.php` and `auth/Gatekeeper.php`.
- **Files**:
    - `index.php`
    - `auth/Gatekeeper.php`

## [Job: Fix PyViz Freeze] - 2026-01-12
- **Purpose**: Investigate and fix browser freeze/busy issue on PyViz remote.
- **Changes**: 
    - Fixed `HANDSHAKE_URL` in `pyviz.js` to be relative.
    - Removed infinite loop in `pyviz.js` to prevent browser freeze.
- **Files**:
    - `frontend/PyViz/js/pyviz/pyviz.js`

## [Job: Fix All Apps Freeze] - 2026-01-12
- **Purpose**: Fix browser freeze on remote for all remaining apps.
- **Changes**: 
    - Fixed `HANDSHAKE_URL` and removed infinite loop in:
        - `frontend/Linear/js/pdraw.js`
        - `frontend/Graph/js/tgdraw/tgdraw.js`
        - `frontend/Stats/js/app.js`
        - `frontend/DViz/js/dviz/dviz.js`
- **Files**:
    - `frontend/Linear/js/pdraw.js`
    - `frontend/Graph/js/tgdraw/tgdraw.js`
    - `frontend/Stats/js/app.js`
    - `frontend/DViz/js/dviz/dviz.js`

## [Job: Email Updates] - 2026-01-13
- **Purpose**: Brand updates and path corrections for emails.
- **Changes**: 
    - Updated email templates in `MailService.php` to use Vdraw branding.
    - Used `$_SERVER['HTTP_HOST']` for dynamic verification links.
- **Files**:
    - `auth/MailService.php`

## [Job: Path Audit] - 2026-01-13
- **Purpose**: Comprehensive audit to eliminate hardcoded paths.
- **Changes**: 
    - Updated `tracking.js` inside apps to usage `../../api/track.php`.
    - Removed `/vdraw-anti-auto` prefixes from `Gatekeeper.php`.
    - Made `forgot_password.php` and `debug_ads.php` path-agnostic.
    - Logged all changes in `plog.md`.
    - **Fix Login Redirect**: Changed `index.php` login link to use relative `index.php` instead of `/index.php` to avoid XAMPP root dashboard.
- **Files**:
    - `frontend/PyViz/index.php`, `frontend/Linear/index.php`, etc.
    - `auth/Gatekeeper.php`
    - `frontend/PyViz/forgot_password.php`
    - `frontend/ads/debug_ads.php`
    - `index.php`

## [Job: Fix Tracking JS] - 2026-01-13
- **Purpose**: Fix app crash due to missing helper function in tracking.js.
- **Changes**: 
    - Re-added `getOS` function to `frontend/js/tracking.js`.
- **Files**:
    - `frontend/js/tracking.js`

## [Job: Fix Gatekeeper] - 2026-01-13
- **Purpose**: Fix app crash due to syntax error in Gatekeeper.php.
- **Changes**: 
    - Fixed array syntax error in `auth/Gatekeeper.php`.
    - Updated hardcoded redirects to be relative (`../../login.php`).
- **Files**:
    - `auth/Gatekeeper.php`

## [Job: Slow Connection Optimizations] - 2026-01-13
- **Purpose**: Improve robustness on slow networks for Handshake and PyViz.
- **Changes**: 
    - **Handshake**: Implemented 3x retry loop with 5s timeout. Added "Soft Failure" (toast) instead of UI destruction for timeouts.
    - **PyViz**: Added visual "Downloading Runtime..." indicator and disabled "Run" button during load. Implemented adaptive timeout (60s + user prompt to wait).
- **Files**:
    - `frontend/PyViz/index.php`
    - `frontend/Linear/js/pdraw.js`
    - `frontend/Graph/js/tgdraw/tgdraw.js`
    - `frontend/Stats/js/app.js`
    - `frontend/DViz/js/dviz/dviz.js`
    - `frontend/PyViz/js/pyviz-modules/output-animation/runtime-loader.js`
    - `frontend/PyViz/js/pyviz/pyviz.js`

## [Job: UI Clean-up] - 2026-01-13
- **Purpose**: Remove dark mode toggle button from app headers.
- **Changes**: 
    - Removed `#theme-toggle` button from headers in Stats, Linear, and Graph apps.
    - PyViz and DViz were already strictly dark mode/controlled and did not have the toggle.
- **Files**:
    - `frontend/Stats/index.php`
    - `frontend/Stats/app.html`
    - `frontend/Linear/index.php`
    - `frontend/Linear/app.html`
    - `frontend/Graph/index.php`
    - `frontend/Graph/app.html`


    - `frontend/Graph/app.html`

## [Job: PyViz UI Refresh] - 2026-01-13
- **Purpose**: Implement "Downloading Runtime" state for PyViz Run button.
- **Changes**: 
    - **UI**: Run button is now disabled by default with a "Downloading Runtime..." spinner.
    - **Logic**: Runtime is silently pre-loaded in the background. Button enables only when ready.
    - **Safety**: Added `onComplete` callback to reset button state after execution finishes.
    - **Toast**: Added bottom-right blinky toast "Python Runtime Downloading..." while loading.
- **Files**:
    - `frontend/PyViz/js/pyviz-modules/output-animation/index.js`
    - `frontend/PyViz/js/pyviz-modules/output-animation/executor.js`

## [Job: AlgoViz Initialization] - 2026-01-14
- **Purpose**: Initialize new isolated app 'AlgoViz' according to Phase 17 specs.
- **Changes**: 
    - Created application directory structure.
    - Initialized app-specific logs (`ui_log.md`, `script_log.md`).
    - Created core files (`index.php`, `css/algoviz.css`, `js/app.js`).
- **Files**:
    - `frontend/AlgoViz/`
    - `frontend/AlgoViz/index.php`
    - `frontend/AlgoViz/css/algoviz.css`
    - `frontend/AlgoViz/js/app.js`



## [Job: AlgoViz Complete Features Phase 17] - 2026-01-15
- **Purpose**: Implement remaining Phase 17 features including cycle-based visualization, syntax highlighting, and top apps bar.
- **Features Implemented**:
    - **Top Apps Navigation Bar**: Dynamic header with database-loaded app links from AppHelper, matching PyViz style.
    - **Python Syntax Highlighting**: Token-based highlighter with color-coded keywords (orange), functions (blue), strings (green), numbers (purple), comments (gray).
    - **Cycle-Based Visualization**: Each iteration/pass of algorithms displayed on new line with cycle numbers. Includes step labels within each cycle.
    - **Dynamic Imports**: Algorithm modules now load with cache-busting timestamps to prevent stale cache issues.
- **Algorithms Updated with Cycle Tracking**:
    - Bubble Sort
    - Selection Sort  
    - Linear Search
- **Files Modified**:
    - frontend/AlgoViz/index.php (header navigation)
    - frontend/AlgoViz/css/algoviz.css (cycle styling, syntax colors)
    - frontend/AlgoViz/js/modules/ui.js (renderStep with cycle support, syntax highlighter)
    - frontend/AlgoViz/js/app.js (dynamic imports with cache-busting)
    - frontend/AlgoViz/algorithms/bubble_sort.js (cycle tracking)
    - frontend/AlgoViz/algorithms/selection_sort.js (cycle tracking)
    - frontend/AlgoViz/algorithms/linear_search.js (cycle tracking)


## [Job: AlgoViz UI Fixes] - 2026-01-15
- **Purpose**: Fix layout issues, header navigation, and implement button functionality
- **Issues Fixed**:
    - **Header App Links**: Fixed .hidden CSS conflict with Tailwind md:flex. Added override rule to force display.
    - **Cycle Layout**: Fixed viz-canvas from centered flex to column layout. Cycles now display as horizontal rows, steps arranged side-by-side.
    - **Playground Buttons**: Fully implemented text size toggle (3 levels), index toggle, and fullscreen functionality.
    - **PDF Export**: Implemented basic print-to-PDF functionality.
- **Files Modified**:
    - frontend/AlgoViz/css/algoviz.css (layout fixes, removed .hidden conflict, added text-size and hide-indexes classes)
    - frontend/AlgoViz/js/app.js (button event handlers for text size, index, fullscreen, PDF)


## [Job: AlgoViz Bug Fixes] - 2026-01-15

### Purpose
Fix multiple reported issues with AlgoViz including binary search, scrollbars, variable names, code display, and GIF export.

### Changes Made

**1. CSS Fixes:**
- Removed overflow-x from viz-array-wrapper (fixed step table scrollbars in Selection Sort)
- Changed flex-wrap to 'wrap' for better array element display

**2. Algorithm Fixes:**
- **Merge Sort**: Changed pointers from 'k' to 'i' and 'j' to match the Python code template
- **Quick Sort**: Added full while loop code implementation (was placeholder)
- **Binary Search**: Both for/while templates use the same while loop structure (standard for binary search)

**3. Engine Module:**
- Added 'state' getter to provide compatibility with GIF export code

**4. GIF Export:**
- Improved implementation with loading indicators
- Added progress feedback
- Fixed to use Engine.state getter

### Files Modified:
- frontend/AlgoViz/css/algoviz.css
- frontend/AlgoViz/algorithms/merge_sort.js 
- frontend/AlgoViz/algorithms/quick_sort.js
- frontend/AlgoViz/js/modules/engine.js
- frontend/AlgoViz/js/app.js

### Notes:
- GIF export requires hard page refresh to pick up new engine.js changes (browser cache)
- Binary search uses while loop by design (standard implementation)
