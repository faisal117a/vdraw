# AlgoViz — Condensed Build Specification (NO RULES FILE)

> **Audience:** Antigravity (implementation agent)
> **Purpose:** Build AlgoViz exactly as specified, without ambiguity.
> **Note:** Logging rules are **NOT included** here and must be read from the separate logging spec file.

---

## 0. Codebase Isolation & Safety (CRITICAL)
- AlgoViz must be implemented as a **fully isolated app**, just like other VDraw apps.
- Create **separate folders, files, and modules** for AlgoViz.
- **DO NOT touch, modify, refactor, or reuse code** from other existing apps unless explicitly allowed.
- Shared utilities may be used **only if they already exist as global/shared services** in VDraw.
- AlgoViz must be removable without breaking any other app.

Isolation rules:
- No imports from other app folders
- No changes to global CSS unless scoped to AlgoViz
- No changes to existing routes, APIs, or state managers

---

## 1. App Identity & Positioning
- **App Name:** AlgoViz
- **Parent Platform:** VDraw
- **Type:** Sub‑app / category (future sub‑apps will exist)
- **Target Users:**
  - Phase‑1: ICS / beginner learners
  - Phase‑2+: University‑level students
- **Core Goal:**
  Teach algorithms visually using **step‑by‑step synchronized animation + read‑only Python code highlighting**.

---

## 2. Phase‑1 Algorithms (MANDATORY)
### Searching
1. Linear Search
2. Binary Search

### Sorting
3. Selection Sort
4. Bubble Sort
5. Merge Sort
6. Quick Sort

> ❗ Each algorithm MUST be implemented in its **own module** and must not affect others.

---

## 3. Supported Data Types & Constraints
### Value Types
- Numbers (default)
- Text (strings)

### Algorithm Availability
| Algorithm Type | Numbers | Text |
|---------------|---------|------|
| Linear Search | ✅ | ✅ |
| Binary Search | ✅ | ❌ |
| All Sorts | ✅ | ❌ |

### Global Constraints
- Max values allowed: **50**
- Values accepted via comma / space / newline
- Invalid input must show **clear inline error messages**

---

## 4. Binary Search Special Handling (CRITICAL)
- Binary Search **requires sorted input**.
- If input is unsorted, user MUST be prompted:
  - **Option A:** Auto‑sort then continue (recommended)
  - **Option B:** Cancel execution
- Auto‑sort explanation must be shown briefly before search starts.

---

## 5. UI Layout (STRICT)
### Desktop / Tablet
```
[ Left Panel ] [ Main Playground ] [ Right Panel ]
```

### Right Panel Split
```
[ Code Playground (Top) ]
[ Complexity & Properties (Bottom) ]
```

### Mobile Layout (STACKED ONLY)
Order:
1. Left Panel
2. Main Playground
3. Code Playground
4. Complexity Panel

### Size & Scroll Rules
- Each panel must be independently scrollable.
- Horizontal scrolling must activate for wide arrays.
- On mobile, **each panel height must be fixed between 350px–450px**.

❌ No teacher mode
❌ No height‑lock feature

---

## 6. Left Panel — Controls
### Mandatory Controls
1. Value Type selector (Numbers / Text)
2. Values input box
3. Algorithm dropdown (filtered by value type)
4. Algorithm‑specific options:
   - Search value (search algorithms)
   - Sort order: Asc / Desc (sorting algorithms)
5. Loop construct selector:
   - For loop
   - While loop
6. Recursion visualization toggle:
   - ONLY for Merge Sort & Quick Sort
7. Buttons:
   - **Simulate** (prepare steps)
   - **Reset** (clear state)

---

## 7. Main Playground (Middle Panel)
### Main Playground Buttons (TOP BAR)
Must work on desktop + mobile:
- AlgoViz label
- Play / Pause
- Step Forward
- Step Backward
- Speed control
- Reset
- Full Screen
- Text size (A− / A+)
- Show / Hide Indexes
- Export (GIF / PDF)

### Array Visualization Rules
- Values displayed as **horizontal table cells**.
- Each cell must show:
  - Index
  - Value
- Index visibility must be toggleable.

### Visual Semantics (MANDATORY COLORS)
- Active pointer → Blue
- Comparison → Yellow
- Swap → Red
- Sorted / final position → Green
- Pivot / partition → Purple

Animations must be smooth, readable, and not flashy.

---

## 8. Code Playground (Right Top)
- **Read‑only only** (no editing, no execution).
- Python code only.
- Code changes ONLY by:
  - Selected algorithm
  - Selected loop construct (for / while)
- Code highlighting MUST stay synchronized with animation steps.

---

## 9. Complexity & Properties Panel (Right Bottom)
Must display:
- Time complexity (Best / Average / Worst)
- Space complexity
- Stable? (Yes / No)
- In‑place? (Yes / No)
- Requires sorted input? (Yes / No)
- Short explanatory notes (easy English)

---

## 10. Step Engine & Synchronization (NON‑NEGOTIABLE)
### Golden Rule
**One animation step = one code step.**

### Engine Capabilities
- Play
- Pause
- Step Forward
- Step Backward
- Reset
- Speed change at runtime

### Technical Requirement
- Timeline must be **event‑driven**, not timer‑only.
- State must be reversible to support backward stepping.

---

## 11. Modular Algorithm Contract (REQUIRED)
Each algorithm module MUST export:
- Metadata (name, category, supported value types)
- Input validation function
- Step generator → returns ordered step objects
- Read‑only Python code template + line map
- Complexity & properties object

### Step Object MUST Include
- Array snapshot or diff
- Highlighted indexes
- Pointer positions (i, j, low, high, pivot, etc.)
- Human‑readable message
- Code line numbers to highlight

---

## 12. Merge Sort & Quick Sort — Recursion Mode
When recursion toggle is ON:
- Show recursion / partition level indicator
- Highlight current sub‑array bounds

When OFF:
- Show flattened step sequence only

---

## 13. Export Features
### Export GIF
- Capture **main playground animation only**

### Export PDF
- Algorithm name
- Input values
- Selected options
- Complexity & properties
- Key snapshots (start / mid / end)

Exports must work for up to 50 values.

---

## 14. Styling Guidelines
- Must be visually different from PyViz
- Modern dark base
- Clear contrast
- Smooth transitions
- Readable fonts (students first)

---

## 15. FINAL IMPLEMENTATION CHECKLIST (DO NOT SKIP)

### Layout & Responsiveness
- [ ] Desktop 3‑panel layout correct
- [ ] Mobile stacked order correct
- [ ] Independent scrollbars working
- [ ] Mobile height limits enforced

### Input & Validation
- [ ] Value parsing works correctly
- [ ] Max 50 values enforced
- [ ] Algorithm list filters by value type
- [ ] Binary Search sorted‑input handling works

### Main Playground
- [ ] All control buttons functional
- [ ] Step forward/backward accurate
- [ ] Index display toggle works
- [ ] Colors follow semantic rules

### Code Playground
- [ ] Read‑only enforced
- [ ] Loop selection changes code
- [ ] Code highlights sync with animation

### Algorithms
- [ ] Linear Search correct
- [ ] Binary Search correct
- [ ] Selection Sort correct
- [ ] Bubble Sort correct
- [ ] Merge Sort recursion toggle works
- [ ] Quick Sort recursion toggle works

### Exports
- [ ] GIF export works
- [ ] PDF export works

### Architecture
- [ ] Algorithms fully isolated
- [ ] Shared logic centralized
- [ ] New algorithm can be added without refactor

---

## END OF FILE

