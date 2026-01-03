# PyViz Explainer – Output Animation Module (Browser‑Only WASM Python)

## Purpose
Design and implement a **fully isolated, modular feature** named **"Output Animation"** for the existing **PyViz Explainer** app.

This module must animate Python code execution **line‑by‑line**, display progressive output in a dedicated panel, and handle `input()` interactively — **without modifying or breaking any existing UI, logic, or workflows**.

The implementation must be **100% client‑side**, using a **Python WebAssembly (WASM) runtime**, and must work on **shared hosting** without any backend server (no XAMPP, no Uvicorn, no ports).

---

## ⚠️ Critical Non‑Negotiable Rules (VERY IMPORTANT)

1. **DO NOT touch or refactor any existing UI components**
   - Playground editor
   - Inspector panel
   - Existing buttons, menus, layout, styles

2. **DO NOT change existing logic or event handlers**
   - No edits to current execution flow
   - No changes to state management

3. **Implement this feature as a completely separate module**
   - Plug‑in style
   - Self‑contained
   - Can be enabled/disabled with a single flag

4. **Must be removable safely**
   - If the module is disabled, the app must behave exactly as before
   - No side effects

5. **No backend of any kind**
   - No Python server
   - No Node server
   - No WebSocket
   - No HTTP API

---

## Feature Overview

### New UI Elements (Additive Only)

1. **Button**
   - Label: `Output Animation`
   - Placement: Either
     - Top of Playground **OR**
     - Bottom of Playground
   - Must not shift or resize existing UI

2. **Output Results Panel**
   - New panel added **above the Inspector**
   - Title: `Output Results`
   - Scrollable
   - Shows output progressively, line‑by‑line

---

## Execution Model (Core Requirement)

- Use a **Python interpreter compiled to WebAssembly (WASM)**
- Python code must run **inside the browser sandbox**
- No server execution
- No network calls during execution

### Runtime Behavior

1. User clicks **Output Animation**
2. Current code from Playground is read (read‑only copy)
3. Code is executed **step‑by‑step**
4. Each executed line:
   - Is highlighted in Playground
   - Appears with a configurable delay
5. Output appears incrementally in **Output Results** panel

---

## Line‑by‑Line Animation Rules

- Execution must pause between logical lines
- Highlight current line being executed
- Delay should be configurable (default: educational speed)
- Loops must visibly iterate
- Conditional branches must visually show which path is taken

Execution must be **real**, not simulated text playback.

---

## Handling `print()`

- Intercept Python `print()` calls
- Capture output immediately
- Append output line‑by‑line to **Output Results** panel
- Preserve order and formatting

Example display:
```
Hello
5
Loop started
```

---

## Handling `input()` (Blocking Execution)

When Python code calls:
```python
name = input("Enter name:")
```

The system must:

1. Pause execution
2. Show modal / alert dialog
   - Display prompt text
3. Wait for user input
4. Resume execution with provided value
5. Continue animation normally

Input dialog must be:
- Simple
- Accessible
- Non‑blocking to rest of app

---

## Error Handling

- Python syntax errors must be caught
- Runtime errors must be displayed in **Output Results**
- Execution must stop gracefully on error
- Existing app must remain unaffected

Example:
```
Traceback (most recent call last):
  Line 3
ZeroDivisionError
```

---

## Performance & Safety Constraints

- Limit maximum execution time
- Prevent infinite loops from freezing UI
- Disable file system access
- Disable OS/system calls
- Sandbox execution strictly inside browser

---

## Modular Architecture Requirement

Implement this feature as an **independent module**, for example:

```
/pyviz-modules/
  output-animation/
    index.js
    runtime-loader.js
    executor.js
    input-handler.js
    output-panel.js
    config.js
```

### Module Characteristics

- No global variable pollution
- No dependency on internal app state
- Uses clearly defined public hooks only
- Can be toggled with:

```js
ENABLE_OUTPUT_ANIMATION = true | false
```

If disabled:
- Button not rendered
- No runtime loaded
- Zero impact on app

---

## Integration Rules

- Module initializes **only after app is fully loaded**
- Lazy‑load Python WASM runtime on first use
- Cache runtime for future runs

---

## Educational UX Principles

- Designed for students (Grade 9–12)
- Slow, clear, visible execution
- No overwhelming information
- Focus on understanding flow, not speed

---

## Future‑Safe Design (Do Not Implement Now)

Design must allow later addition of:
- Variable state viewer
- Stack trace visualizer
- Step / Next / Resume buttons
- Speed slider

But **do NOT implement these now**.

---

## Deliverable

- Fully functional **Output Animation** module
- Zero regression in existing PyViz Explainer behavior
- Clean, readable, maintainable code
- Strict adherence to modular isolation

---

## Final Instruction to Antigravity

> Treat this module as a **plug‑in**.
> Do **NOT** refactor, rename, or optimize any existing PyViz code.
> If any conflict occurs, the module must fail silently without affecting the app.

---

**End of Prompt**

