Pyodide Local Runtime Files
===========================

To run the PyViz Output Animation module offline or if CDNs are blocked, you must download the Pyodide runtime files and place them in this directory.

1.  Download the **v0.24.1** release files from:
    https://github.com/pyodide/pyodide/releases/tag/0.24.1
    OR download directly from CDN:
    - https://cdn.jsdelivr.net/pyodide/v0.24.1/full/pyodide.js
    - https://cdn.jsdelivr.net/pyodide/v0.24.1/full/pyodide.asm.js
    - https://cdn.jsdelivr.net/pyodide/v0.24.1/full/pyodide.asm.wasm
    - https://cdn.jsdelivr.net/pyodide/v0.24.1/full/python_stdlib.zip

2.  Place the following files DIRECTLY in this folder (`frontend/PyViz/js/pyviz-modules/output-animation/lib/`):
    - pyodide.js
    - pyodide.asm.js
    - pyodide.asm.wasm
    - python_stdlib.zip

3.  The application is configured to look for `./lib/pyodide.js` relative to the module config.
