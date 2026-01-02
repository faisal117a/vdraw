export class RuntimeLoader {
    constructor(config) {
        this.config = config;
        this.worker = null;
        this.runtimeReady = false;
        this.messageCallbacks = {};
    }

    load() {
        if (this.worker) return Promise.resolve();

        // Calculate absolute URLs to support Blob Worker
        // Use config URLs directly if absolute, otherwise prepend origin
        let runtimeUrl = this.config.RUNTIME_URL;
        let runtimeHome = this.config.RUNTIME_HOME;

        const isAbsolute = (url) => url.startsWith('http') || url.startsWith('file') || url.startsWith('blob');

        if (!isAbsolute(runtimeUrl)) {
            runtimeUrl = window.location.origin + runtimeUrl;
        }
        if (!isAbsolute(runtimeHome)) {
            runtimeHome = window.location.origin + runtimeHome;
        }

        // We use a Blob to inline the worker script so we don't need a separate file request setup
        // This worker will load pyodide
        const workerScript = `
try {
    importScripts("${runtimeUrl}");
    self.postMessage({ type: 'img_stdout', content: "DEBUG: Pyodide script loaded" });
} catch (e) {
    self.postMessage({ type: 'error', content: "Failed to load Pyodide from CDN (Network/Offline?): " + e });
}
self.postMessage({ type: 'img_stdout', content: "DEBUG: Worker Script Starting..." });

// INTERCEPTOR: Fix MIME type for WASM if server is misconfigured (XAMPP default)
const originalFetch = self.fetch;
self.fetch = async (input, init) => {
    // Determine URL from input which can be String, Request, or URL object
    let fetchUrl = "";
    if (typeof input === 'string') fetchUrl = input;
    else if (typeof input === 'object' && input.href) fetchUrl = input.href; // Handle URL objects
    else if (input && input.url) fetchUrl = input.url; // Handle Request objects

    const response = await originalFetch(input, init);
    
    // Check if it's the WASM file
    const resUrl = response.url || "";
    
    // Safe check for .wasm extension
    if (response.status === 200 && (
        (fetchUrl && typeof fetchUrl === 'string' && fetchUrl.endsWith('.wasm')) || 
        (resUrl && typeof resUrl === 'string' && resUrl.endsWith('.wasm'))
    )) {
        console.log("Worker: Intercepting WASM fetch to fix Content-Type");
        const newHeaders = new Headers(response.headers);
        newHeaders.set('Content-Type', 'application/wasm');
        return new Response(response.body, {
            status: response.status,
            statusText: response.statusText,
            headers: newHeaders
        });
    }
    return response;
};

let pyodide = null;
let inputResolver = null;

// Helper for Python to talk to JS without PyProxy cloning issues
self.sendPythonMessage = (type, data1) => {
    // data1 might be a PyProxy if not careful, but for strings/ints it's fine.
    // We explicitly unwrap if needed, but usually simple types pass by value.
    self.postMessage({ type: type, ...data1 });
}

async function loadPyodideRuntime() {
    try {
        // Explicitly set indexURL for local loading from Blob worker
        pyodide = await loadPyodide({ indexURL: "${runtimeHome}" });
        // Setup stdout/stderr redirection
        pyodide.setStdout({ batched: (msg) => self.postMessage({ type: 'img_stdout', content: msg }) });
        
        // Define system functions for trace and sleep
        await pyodide.runPythonAsync(\`
import sys
import time
import js

def _trace_dispatch(frame, event, arg):
    if event == 'line':
        # Call JS helper with simple types
        # We pass a dict, Pyodide converts it to a proxy, but we handle it in JS or pass args?
        # Let's pass args to be safe.
        # js.sendPythonMessage("trace_line", {"lineno": frame.f_lineno}) -- dangerous if dict conversion fails.
        # Let's just define a specific helper for trace?
        # No, let's use the helper but make sure we pass a python dict that converts cleanly or just simple args.
        
        js.sendPythonMessage("trace_line", js.Object.fromEntries([("lineno", frame.f_lineno)]))
        
        # input check delay
        try:
            delay = float(js.global_delay)
        except:
            delay = 1.0
        time.sleep(delay)
        
    return _trace_dispatch

def _input_wrapper(prompt=""):
    try:
        js.sendPythonMessage("input_request", js.Object.fromEntries([("prompt", prompt)]))
        return "0" 
    except:
        return "0"

import builtins
builtins.input = _input_wrapper
\`);
        self.postMessage({ type: 'ready' });
    } catch (e) {
        self.postMessage({ type: 'error', content: "CRITICAL RUNTIME ERROR: " + String(e) });
    }
}

// Global delay variable
self.global_delay = ${this.config.ANIMATION_DELAY_MS / 1000.0};

self.onmessage = async (e) => {
    const data = e.data;
    if (data.type === 'init') {
        await loadPyodideRuntime();
    } else if (data.type === 'run') {
        if (!pyodide) return;
        if (data.delay) self.global_delay = data.delay / 1000.0;

        try {
            const code = data.code;
            console.log("Worker: Running code");
            await pyodide.runPythonAsync(\`
import sys
sys.settrace(None)
def _user_wrapper():
    print("DEBUG: Executing")
    sys.settrace(_trace_dispatch)
    try:
        # Double escaped for JS string literal -> Python string
        src = \"\"\"\${code.replace(/\\\\/g, '\\\\\\\\').replace(/"/g, '\\\\"')}\"\"\"
        exec(src, globals())
    except Exception as e:
        print(f"Error: {e}")
    finally:
        sys.settrace(None)
        print("DEBUG: Finished")

_user_wrapper()
\`);
            self.postMessage({ type: 'finished' });
        } catch (err) {
            self.postMessage({ type: 'error', content: err.toString() });
        }
    }
};

`;
        const blob = new Blob([workerScript], { type: "application/javascript" });
        this.worker = new Worker(URL.createObjectURL(blob));

        this.worker.onmessage = (e) => {
            const msg = e.data;
            if (this.messageCallbacks[msg.type]) {
                this.messageCallbacks[msg.type](msg);
            }
        };

        return new Promise((resolve, reject) => {
            this.messageCallbacks['ready'] = () => {
                this.runtimeReady = true;
                resolve();
            };
            this.messageCallbacks['error'] = (msg) => {
                this.runtimeReady = false;
                reject(new Error(msg.content));
            };
            this.worker.onerror = (evt) => {
                this.runtimeReady = false;
                // Try to get useful info. Security restrictions might hide details ('Script error.').
                const errParams = [evt.message, evt.filename, evt.lineno].filter(x => x).join(':');
                reject(new Error("Worker Error (Network/CORS?): " + errParams));
            };

            this.worker.postMessage({ type: 'init' });

            // Safety timeout
            setTimeout(() => reject(new Error("Timeout loading Python Runtime (Check Internet Connection)")), 30000);
        });
    }

    run(code, callbacks) {
        if (!this.runtimeReady) return;

        // Register callbacks
        this.messageCallbacks['trace_line'] = (msg) => callbacks.onLine(msg.lineno);
        this.messageCallbacks['img_stdout'] = (msg) => callbacks.onPrint(msg.content);
        this.messageCallbacks['error'] = (msg) => callbacks.onError(msg.content);
        this.messageCallbacks['finished'] = () => callbacks.onFinished();
        this.messageCallbacks['input_request'] = (msg) => callbacks.onInput(msg.prompt);

        this.worker.postMessage({ type: 'run', code: code, delay: callbacks.delay || this.config.ANIMATION_DELAY_MS });
    }

    updateDelay(ms) {
        // Logic to update delay in worker
    }
}
