# Data Structures Visualizer (Phase 1)

A guided, interactive web app that helps students **see** how Python data structures change after each operation.

## Goals

- Teach **List, Tuple, Stack, Queue** via:
  - step-by-step operation execution
  - auto-print after every operation
  - clear explanations + time complexity + memory notes
  - optional diagram view (right sidebar)
- Support **multiple implementations** (e.g., Queue via list vs deque vs queue.Queue)
- Student-friendly UX: guided, safe errors, and readable output
- Extensible architecture for **Trees/Graphs/Animations** later

---

## UX Spec (What the student experiences)

### Layout

- **Left Sidebar (Controls)**
  1) **Input values textarea**: comma-separated, up to 100 items (mixed auto-detect)
  2) **Data structure dropdown**: List / Tuple / Stack / Queue
  3) **Implementation dropdown (dynamic)**:
     - Stack: `list` / `collections.deque`
     - Queue: `list` / `collections.deque` / `queue.Queue`
     - List: built-in list
     - Tuple: built-in tuple
     - If only one option exists: auto-selected
  4) **Operation builder**:
     - Operation dropdown (dynamic based on DS + implementation)
     - `+ Add` adds an operation to a **sequence list** (can repeat)
     - Each operation can have **dynamic parameter fields** (e.g., push(value))
     - Operations list is **reorderable (drag & drop)**
     - Remove operation button per row
  5) Toggle(s):
     - Show labels (TOP/FRONT/REAR)
     - Show diagram
  6) **Apply** button

- **Center (Playground Output)**
  - Shows:
    - Initial state row
    - Then for each operation:
      - operation name + parameters
      - resulting structure state (auto “print”)
      - short explanation + complexity + memory note
  - Font controls: `A+` / `A-`
  - Each step shown in its own row with readable line-height

- **Right Sidebar (Diagram)**
  - Optional toggle
  - Visual representation synced with the current state
  - For Phase 1:
    - Stack diagram: vertical boxes + TOP
    - Queue diagram: horizontal boxes + FRONT/REAR
    - List/Tuple diagram: linear boxes
  - Later: animated transitions, trees/graphs

---

## Core Concepts

### 1) Data types: auto-detect
- Parse tokens from textarea.
- Rules:
  - `10` → int
  - `10.5` → float
  - `"abc"` or `'abc'` → string
  - `abc` (no quotes) → reject + message: “Use quotes for strings e.g. \"abc\"”

### 2) Execution model
- Student builds an **operation plan** without executing.
- Clicking **Apply** runs the plan in order.
- After each operation:
  - compute updated state
  - generate:
    - printable representation
    - explanation
    - complexity
    - memory note

### 3) Education-first errors
- If operation invalid:
  - do NOT crash
  - show step as **error step** with:
    - why it failed
    - what concept it demonstrates
    - how to fix
  - continue to next step **(configurable later)**

---

## Supported Structures — Phase 1 Operation Matrix

> Keep operations minimal but meaningful; add more in Phase 1.1.

### List (built-in)
- `append(x)`
- `insert(i, x)`
- `pop()` / `pop(i)`
- `remove(x)`
- `clear()`
- `sort()` (optional)

### Tuple (built-in, immutable)
- `index(x)` (read-only)
- `count(x)` (read-only)
- “Attempted mutation” demo:
  - `set(i, x)` (educational error: tuples are immutable)

### Stack
Implementations:
- `list`
- `collections.deque`
Operations:
- `push(x)`
- `pop()`
- `peek()`
- `is_empty()`

### Queue
Implementations:
- `list` (with note: dequeue is O(n) if using pop(0))
- `collections.deque`
- `queue.Queue` (thread-safe; note: blocks by default; use non-blocking for demo)
Operations:
- `enqueue(x)`
- `dequeue()`
- `front()`
- `rear()` (where feasible)
- `is_empty()`

---

## Tech Stack

### Backend
- **FastAPI**
- Pydantic models for validation
- Endpoints:
  - `GET /api/catalog` (structures, implementations, operations metadata)
  - `POST /api/validate-input`
  - `POST /api/simulate` (input + plan → list of steps)

### Frontend
- Vite + (React) OR vanilla + Alpine (choose React for modular UI + future trees/graphs)
- TailwindCSS
- GSAP for subtle animations (step reveal, diagram transitions)
- Drag & drop operations list:
  - `@dnd-kit` (React) or SortableJS
- Diagram rendering:
  - Phase 1: SVG components (simple)
  - Phase 2: React Flow / D3 for graphs/trees

---

## Data Model (API)

### Catalog
- Data structures → implementations → operations
- Each operation defines:
  - `id`, `label`
  - parameters schema (name, type, required, placeholder)
  - complexity (avg/worst)
  - memory note

### Simulation Request
```json
{
  "structure": "queue",
  "implementation": "deque",
  "initial_values": [10, 20, 30],
  "operations": [
    {"op": "enqueue", "args": {"value": 99}},
    {"op": "dequeue", "args": {}},
    {"op": "front", "args": {}}
  ],
  "options": {
    "continue_on_error": true,
    "show_labels": true
  }
}
```

### Simulation Response
```json
{
  "initial": {
    "print": "[10, 20, 30]",
    "diagram": {"type": "queue", "items": [10,20,30], "front": 0, "rear": 2}
  },
  "steps": [
    {
      "index": 1,
      "operation": "enqueue(99)",
      "status": "ok",
      "print": "[10, 20, 30, 99]",
      "explain": "Enqueue adds an element at the rear of the queue.",
      "complexity": "O(1)",
      "memory": "Stores one additional element.",
      "diagram": {"type": "queue", "items": [10,20,30,99], "front": 0, "rear": 3}
    }
  ]
}
```

---

## Backend Implementation Plan

### Folder structure
```
backend/
  app/
    main.py
    api/
      routes_catalog.py
      routes_simulate.py
      routes_validate.py
    core/
      catalog.py
      parsing.py
      explain.py
      complexity.py
    engine/
      base.py
      list_engine.py
      tuple_engine.py
      stack_engine.py
      queue_engine.py
      implementations/
        stack_list.py
        stack_deque.py
        queue_list.py
        queue_deque.py
        queue_queue_module.py
    models/
      schemas.py
    tests/
      test_parsing.py
      test_simulate_queue.py
  requirements.txt
```

### Key modules

**catalog.py**
- Single source of truth for:
  - available structures
  - implementations
  - operations + parameter schema
  - complexity + memory notes

**parsing.py**
- `parse_csv_values(text) -> list[Any]`
- strict validation + helpful errors

**engine/**
- Each structure has an engine that:
  - creates the DS using chosen implementation
  - applies operations and returns:
    - new state
    - printable view
    - diagram payload

**explain.py**
- Explanation templates per operation
- Also special explanations for common errors

---

## Frontend Implementation Plan

### Folder structure
```
frontend/
  src/
    app/
      App.tsx
    components/
      SidebarLeft/
        ValuesInput.tsx
        StructureSelect.tsx
        ImplementationSelect.tsx
        OperationPicker.tsx
        OperationList.tsx
        ApplyButton.tsx
      Playground/
        OutputPanel.tsx
        StepRow.tsx
        FontControls.tsx
      SidebarRight/
        DiagramToggle.tsx
        DiagramPanel.tsx
        diagrams/
          ListDiagram.tsx
          StackDiagram.tsx
          QueueDiagram.tsx
    lib/
      api.ts
      validators.ts
      types.ts
  index.html
  tailwind.config.js
  vite.config.ts
```

### UI behavior
- When structure changes:
  - fetch from cached catalog
  - auto-select implementation if only one
  - update operation dropdown accordingly

### Operation list (drag & drop)
- Each op row shows:
  - operation label
  - dynamic fields (value/index/etc.)
  - remove button

### Apply flow
- Validate input first (client-side quick checks)
- Call `POST /api/simulate`
- Render:
  - initial state row
  - step rows
- GSAP:
  - animate step appearance
  - subtle diagram transitions

---

## Guided Student Mode (Phase 1)

- Provide a “hint box” under operations:
  - Example: “Queue via list: dequeue uses pop(0) which shifts elements → O(n). Try deque for O(1).”
- Disable “Apply” until:
  - valid input
  - structure chosen
  - at least 1 operation

---

## Trees/Graphs (Future Add-on Notes)

### Trees
- Use diagram payload as a node/edge list
- Frontend render options:
  - SVG tree layout (simple)
  - D3 tree layout (advanced)
  - React Flow (developer-friendly)

### Graphs
- Represent as:
  - adjacency list input
  - or edge list input
- Use React Flow or D3 force layout
- Operations (later): add/remove node/edge, BFS, DFS

---

## Step-by-step Build Instructions (Cursor-friendly)

### 0) Repo setup
- Create mono-repo:
  - `backend/`
  - `frontend/`

### 1) Backend scaffolding
1. Create venv
2. Install FastAPI, uvicorn, pydantic
3. Create `/api/catalog` returning hard-coded catalog (start small)
4. Add parsing + `/api/validate-input`
5. Add `/api/simulate` for **Queue via deque** first
6. Expand to Stack, List, Tuple

### 2) Frontend scaffolding
1. Vite + React
2. Tailwind setup
3. Implement UI shell (3 columns)
4. Wire catalog → dynamic dropdowns
5. Implement operation builder + drag-drop
6. Call simulate endpoint and render steps
7. Add diagram panel
8. Add GSAP animations

### 3) Quality
- Unit tests for parsing and edge cases
- Add sample “guided presets” (buttons) for students:
  - “Stack: push/pop demo”
  - “Queue: list vs deque complexity demo”

---

## Deliverables (Phase 1)

- Working web app with:
  - dynamic selection of DS + implementation
  - operation sequencing with parameters
  - reorder via drag-drop
  - apply simulation with step-by-step output
  - explanations + complexity + memory note per step
  - optional diagram view

---

## Next actions (for you)

1) Confirm initial DS operation set (keep minimal or include more?)
2) Confirm whether `continue_on_error` should default to true
3) Confirm UI preference for drag-drop library (`@dnd-kit` recommended)
4) Choose whether we include “presets” in Phase 1 (recommended for student mode)

