# Phase 4 — PyViz: Visual Python Learning (Teacher Explainer Dashboard)

PyViz is a **visual Python language builder** designed for **teachers and students (Grade 9–12)** where Python code is **constructed visually**, **never typed**, and explained using **animations, tooltips, and friendly AI feedback**.

This phase focuses on **concept clarity**, not execution.

---

## 1. Core Philosophy

- ❌ No manual typing of code
- ✔ Python code is *assembled* visually
- ✔ Errors are **allowed but warned** (learning-first)
- ✔ AI explains mistakes simply (≤100 tokens)
- ✔ Strong focus on **indentation, structure, and flow**

PyViz is a **teacher explainer app**, not an IDE.

---

## 2. Dashboard & Layout

### Dashboard Name
**PyViz** (Finalized)

### Layout Structure

```
┌───────────────┬───────────────────────────────┬─────────────────────┐
│ Left Sidebar  │          Playground            │ Right Sidebar       │
│ (Toolbox)    │  ┌──────── Code Area ────────┐ │ (Inspector & Logs)  │
│               │  │ Large, colored Python code │ │                     │
│               │  ├──────── Footer ───────────┤ │                     │
│               │  │ AI + errors + explanation │ │                     │
└───────────────┴───────────────────────────────┴─────────────────────┘
```

---

## 3. Left Sidebar — Visual Toolbox (Part-wise)

### Part 1: Variables & Built-in Functions

#### Variables
- Create variable (Python naming rules enforced)
- Value auto-detection:
  - `12` → int
  - `12.0` → float
  - `pak` → string
  - `"12"` → string
  - `a` → variable reference (must exist)

Warnings (not blocks):
- Undefined variable usage
- Reassignment explanation

---

#### Built-in Functions (Visual Builder)

##### print()
- Add multiple arguments using `+`
- Argument auto-detection (value / variable)
- Checkbox: `end=""`
- Dynamic input field for end character

Generated example:
```python
print(a, 12, "Pak", end=" ")
```

##### input()
- Select type: int / float / string
- Optional prompt message
- Variable auto-created

Generated example:
```python
age = int(input("Enter age: "))
```

---

#### Indentation Control (Critical)

Each construct asks for **nesting level**:

| Level | Spaces |
|------|--------|
| 1 | 4 |
| 2 | 8 |
| 3 | 12 |

Indentation visually animated.

---

### Part 2: Conditions & Loops

#### If Statement Builder

- Keyword selector: if / elif / else
- Condition builder:
  - Expression 1 (e.g., `a+b`)
  - Relational operator
  - Expression 2
  - Logical operator (optional)

Valid expressions:
- `a`
- `a+b`
- `2*4+c`

Warnings:
- Invalid expression
- Variable not defined

Generated example:
```python
if a > b and c < 10:
```

---

#### Loop Builder

Supported loops:
- for
- while

Examples:
```python
for i in range(1, 10):
```
```python
while a < 10:
```

---

### Part 3: Comments

- Single-line (`#`)
- Multi-line (`""" """`)
- Textbox adapts automatically

---

### Part 4: Import Built-in Modules

Dropdown includes:
- math
- queue
- collections
- random

Rules:
- Imports always appear at top
- Auto-reordered if added later

---

### Part 5: Import Third-Party Libraries

Dropdown (curated):
- numpy
- pandas
- matplotlib

Visual label: “External Library”

---

### Part 6: Data Structures (Integrated)

Supported:
- List
- Tuple
- Stack (`collections.deque`)
- Queue (`queue.Queue`)

Flow:
- Name
- Type
- Values (comma separated)
- Auto-detect value types

Then:
- Function dropdown (based on DS type)
- Dropdown of existing DS names

---

### Part 7: Python Keywords Explorer

- Table view
- Color-coded groups:
  - Conditional
  - Loops
  - Functions
  - Keywords
- Hover tooltip explanations

---

### Part 8: AI Error Checker

- Button: **Check with AI**
- Uses API key from `.env`
- Key never committed to GitHub

AI rules:
- ≤ 100 tokens response
- Grade 9–12 language
- Friendly tone
- Explains what + how to fix

AI output:
- Appears in playground footer
- Animated text (GSAP typing effect)

---

## 4. Playground (Center Panel)

### Code Area

- Large font
- Syntax coloring by construct type
- Hover popup explains each line

Controls:
- Font + / –
- Copy code
- Download `.py` file

---

### Footer Area

- AI explanations
- Warning messages
- Friendly error log
- Scrollable

---

## 5. Right Sidebar — Inspector & Logs

### Live Code Summary

- Total variables
- User-defined functions
- Built-in functions
- Loops count
- Conditionals count
- Imports count
- Data structures count
- Elements per DS
- Total lines of code

---

### Action Log

- Deleted lines history
- Reorder history
- Scrollable list

---

## 6. Interaction & Animation Model

- GSAP animations:
  - Line insertion
  - Indentation reveal
  - Tooltip fade-in
  - AI typing effect

- Drag & drop line reordering
- Arrow up/down reorder
- Delete with log retention

---

## 7. Backend Architecture (Phase 4)

```
backend/app/engine/pyviz/
  parser.py        # validates expressions & variables
  builder.py       # builds code lines
  inspector.py     # counts & metadata
  warnings.py      # friendly warnings
  ai_helper.py     # AI error explanation
```

Backend does **NOT execute code**.

---

## 8. Frontend Architecture (Phase 4)

```
frontend/src/pyviz/
  SidebarLeft/
    Variables.tsx
    Functions.tsx
    Conditions.tsx
    Loops.tsx
    Imports.tsx
    DataStructures.tsx
  Playground/
    CodeArea.tsx
    FooterPanel.tsx
  SidebarRight/
    Inspector.tsx
    Logs.tsx
```

---

## 9. Presets & Guided Learning

Built-in lesson presets:
- Variables & Print
- If–Else Basics
- Loops Explained
- Lists & Functions

Displayed via popup with guided steps.

---

## 10. Summary

Phase 4 (PyViz) completes the ecosystem:

- Phase 1 → Linear DS
- Phase 3 → Trees & Graphs
- **Phase 4 → Python language concepts**

PyViz enables teachers to explain Python **visually, safely, and interactively**, without writing code.

This architecture is scalable, classroom-ready, and future-proof.

