# Job Log

## Job: tabs
**Date:** 2026-01-02
**Description:** Convert the right-side "Inspector" panel into a tabbed interface with "Inspector" and "Output Result" tabs to solve width/font size issues and improve layout.
**Files to Modify:**
1.  `frontend/PyViz/index.php` - Restructure HTML to include tabs.
2.  `frontend/PyViz/js/pyviz-modules/output-animation/output-panel.js` - Adapt to render inside the new tab instead of a standalone box.
3.  `frontend/PyViz/js/pyviz-modules/output-animation/executor.js` - Target the new tab container and auto-switch tabs on run.
4.  `frontend/PyViz/js/pyviz/pyviz.js` - Add basic tab switching logic (or inline it in index.php for simplicity).

**Steps Taken:**
1.  Created this log file.
2.  Refactoring `index.php` to add tab headers and content wrappers.
3.  Refactoring `output-panel.js` to remove "popup" styles and attach to the specific DOM element.
4.  Refactoring `executor.js` to mount correctly and switch tabs.

## Job: Python Functions
**Date:** 2026-01-02
**Description:** Add a new toolbox section "Python Functions" for defining custom functions and calling functions.
**Features:**
- Option to select "User Defined Function" or "Built-in/Call Function".
- **User Defined:**
    - Input: Function Name (with validation).
    - Parameters: Dynamic list.
    - Parameter Type: Normal or Default (with value input).
    - Generates `def name(params):`.
- **Function Call:**
    - Input: Function Name.
    - Arguments: Dynamic list.
    - Generates `name(args)`.
**Files to Modify:**
- `frontend/PyViz/index.php`: Add toolbox button.
- `frontend/PyViz/js/pyviz/pyviz.js`: Add builder logic.

## Job: Function Call
**Date:** 2026-01-03
**Description:** Implement "Function Call Buffer" mechanism. Instead of adding function calls directly to code, store them in a temporary list. Expose this list in the "Vars" and "Funcs" toolboxes to allow using function results as values (e.g., `x = math.sqrt(16)` or `print(math.sqrt(16))`).
**Features:**
- **Capture Mode:** When "Insert Function Call" or "Insert Built-in Call" is clicked, if the function returns a value (implied for most math/builtins), add it to a global `functionCallBuffer` instead of the playground.
- **Vars Toolbox Integration:** Add a dropdown "Generated Calls" under the "Value" input. If selected, use this call as the variable's value unless user typed manually.
- **Funcs Toolbox Integration:** Add a "Generated Calls" list or dropdown. When "+ Add Arg" is clicked, if a call is selected, insert it as an argument.
**Files to Modify:**
- `frontend/PyViz/js/pyviz/pyviz.js`:
    - Add `functionCallBuffer` state.
    - Update `createBuiltInCall` and `createFnCall` to push to buffer.
    - Update `renderVarBuilder` to include the dropdown.
    - Update `createVariable` to read from dropdown.
    - Update `renderFuncBuilder` (Print) to include the list and `addPrintArgInput` logic.

## Job: Return Statement Part
**Date:** 2026-01-03
**Description:** Add a UI section in "Py Funcs" toolbox to insert `return` statements (e.g., `return 10`, `return a + b`).
**Features:**
- **Return Mode:** Add a 4th mode or a section in the "Define" mode (logic suggests separate or part of logic blocks, but requested in Py Funcs).
- **UI:** Simple input for "Return Value/Expression" and a button "Insert Return".
- **Validation:** Should probably warn if not inside a function scope (though visual builder might just insert).
**Files to Modify:**
- `frontend/PyViz/js/pyviz/pyviz.js`: Update `renderPyFuncBuilder` to include Return UI.


