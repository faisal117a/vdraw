# AlgoViz Script Log
## [Init] - 2026-01-14
- Created initial script structure.


## 2026-01-14 23:23 - Critical Bug Fixes
- **Fixed**: Binary Search code template missing 'for' key causing TypeError
- **Fixed**: UI module safe template access with optional chaining
- **Fixed**: Loop toggle handler preventing undefined errors
- **Impact**: Simulation engine now fully operational, all 6 algorithms work correctly
- **Testing**: Verified with Linear Search, Binary Search, and sorting algorithms


## 2026-01-15 07:40 - Dynamic Imports and Cycle Tracking
- **Dynamic Imports**: Changed app.js from static to dynamic imports with cache-busting
- **Cycle Tracking**: Added cycle and stepInCycle properties to Bubble Sort, Selection Sort, Linear Search
- **Syntax Highlighter**: Rewrote from regex-based to token-based approach to prevent HTML corruption
- **UI State**: Added lastCycle tracking for proper cycle container creation
