# AlgoViz UI Log
## [Init] - 2026-01-14
- Created initial UI structure.


## 2026-01-14 23:23 - Phase 17 Compliance Fixes
- **Fixed**: Dynamic input fields (Search Value, Sort Order) now appear correctly when algorithms are selected
- **Fixed**: Data type filtering now works - Text type shows only Linear Search
- **Fixed**: Code display panel now shows Python code for all algorithms
- **Fixed**: Complexity panel displays correct information for each algorithm
- **Status**: All UI components now functional and compliant with Phase 17 Section 6-9


## 2026-01-15 07:40 - Complete Features Implementation
- **Top Apps Bar**: Added PyViz-style navigation header with database-loaded links
- **Python Syntax Highlighting**: Implemented token-based highlighter (no regex corruption)
- **Cycle-Based Visualization**: Each pass/iteration shown on new line with cycle numbers
- **Step Labels**: Steps within each cycle show step numbers (Step 1, Step 2, etc.)
- **Visual Styling**: Cycle headers with gradient background and blue border


## 2026-01-16 15:35 - Input Field Padding Improvement
- **Fixed**: Increased inner padding of Input Values textarea and Search Value text box
- **Reason**: Cursor was hard to see when clicking in these fields due to Tailwind CSS Preflight resetting padding to 0px
- **Change**: Added `padding: 0.75rem !important` to override Tailwind's reset
- **Scope**: Affects all select, input, and textarea elements in the sidebar
- **Verified**: Computed padding now correctly shows 12px, cursor is visible


## 2026-01-16 16:35 - Scrollbar Theme Styling
- **Added**: Custom scrollbar CSS for center playground and right sidebar panels
- **Style**: Modern slim 6px width scrollbars with rounded corners
- **Colors**: Blue gradient thumb (#3b82f6 to #2563eb) matching AlgoViz app theme
- **Hover**: Lighter blue gradient on hover for interactive feedback
- **Targets**: viz-canvas, code-playground, code-container, complexity-panel, and aside elements
- **Firefox**: Added scrollbar-width: thin and scrollbar-color support
- **File**: css/algoviz.css

## 2026-01-18 11:15 - Ad Placement
- **Feature**: Added ad placement zone `algoviz_intro_bottom` in the "Empty State" area.
- **Logic**: Integrated `AdManager` initialization similar to other VDraw apps.
