export class RuntimeLoader {
    constructor(config) {
        this.config = config;
        this.worker = null;
        this.runtimeReady = false;
        this.messageCallbacks = {};
        this.currentSessionId = null;
    }

    load() {
        if (this.worker) return Promise.resolve();

        let runtimeUrl = this.config.RUNTIME_URL;
        let runtimeHome = this.config.RUNTIME_HOME;

        // Determine backend IO proxy URL
        // Assuming we are at /frontend/PyViz/... -> go up to /backend/io_proxy.php
        let baseUrl = window.location.origin + window.location.pathname;
        if (baseUrl.includes('/frontend/')) {
            baseUrl = baseUrl.split('/frontend/')[0];
        } else if (baseUrl.endsWith('/')) {
            baseUrl = baseUrl.slice(0, -1);
        }
        const ioUrl = `${baseUrl}/backend/io_proxy.php`;

        const isAbsolute = (url) => url.startsWith('http') || url.startsWith('file') || url.startsWith('blob');

        if (!isAbsolute(runtimeUrl)) {
            runtimeUrl = window.location.origin + runtimeUrl;
        }
        if (!isAbsolute(runtimeHome)) {
            runtimeHome = window.location.origin + runtimeHome;
        }

        const workerScript = `
try {
    importScripts("${runtimeUrl}");
    self.postMessage({ type: 'img_stdout', content: "DEBUG: Pyodide script loaded" });
} catch (e) {
    self.postMessage({ type: 'error', content: "Failed to load Pyodide from CDN: " + e });
}

self.io_url_base = "${ioUrl}";
self.current_session_id = "init";

// Patch fetch for WASM MIME type
const originalFetch = self.fetch;
self.fetch = async (input, init) => {
    let fetchUrl = "";
    if (typeof input === 'string') fetchUrl = input;
    else if (typeof input === 'object' && input.href) fetchUrl = input.href;
    else if (input && input.url) fetchUrl = input.url;

    const response = await originalFetch(input, init);
    const resUrl = response.url || "";
    
    if (response.status === 200 && (
        (fetchUrl && typeof fetchUrl === 'string' && fetchUrl.endsWith('.wasm')) || 
        (resUrl && typeof resUrl === 'string' && resUrl.endsWith('.wasm'))
    )) {
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

self.sendPythonMessage = (type, data1) => {
    self.postMessage({ type: type, ...data1 });
}

async function loadPyodideRuntime() {
    try {
        pyodide = await loadPyodide({ indexURL: "${runtimeHome}" });
        pyodide.setStdout({ batched: (msg) => self.postMessage({ type: 'img_stdout', content: msg }) });
        
        await pyodide.runPythonAsync(\`
import sys
import time
import js
import builtins
from js import XMLHttpRequest

# --- Helper Classes (Defined in Init) ---
class Unbuffered(object):
   def __init__(self, stream=None):
       # We don't use the stream anymore, but keep arg for compatibility
       pass
   def write(self, data):
       # Send directly to Main Thread via JS, bypassing Pyodide buffering
       js.sendPythonMessage("img_stdout", js.Object.fromEntries([("content", data)]))
   def writelines(self, datas):
       for data in datas:
           self.write(data)
   def flush(self):
       pass
   def __getattr__(self, attr):
       return getattr(sys.__stdout__, attr)

sys.stdout = Unbuffered()
sys.stderr = Unbuffered() # Capture stderr too if needed, or keep separate

_real_import = builtins.__import__
class MockLib:
    def __init__(self, name):
        self._name = name
    def __getattr__(self, attr):
        raise ImportError(f"Library '{self._name}' is not supported in this demo environment.")
    def __call__(self, *args, **kwargs):
        raise ImportError(f"Library '{self._name}' is not supported in this demo environment.")

def _safe_import(name, globals=None, locals=None, fromlist=(), level=0):
    BLOCK_LIST = ['numpy', 'pandas', 'scipy', 'matplotlib', 'sklearn', 'tensorflow', 'pytorch', 'cv2', 'requests']
    base = name.split('.')[0]
    if base in BLOCK_LIST:
         return MockLib(name)
    return _real_import(name, globals, locals, fromlist, level)

builtins.__import__ = _safe_import

def _trace_dispatch(frame, event, arg):
    # ONLY trace user code, ignore helpers/wrappers
    if event == 'line' and frame.f_code.co_filename == '<user_code>':
        js.sendPythonMessage("trace_line", js.Object.fromEntries([("lineno", frame.f_lineno)]))
        try:
            delay = float(js.global_delay)
        except:
            delay = 1.0
        time.sleep(delay)
    return _trace_dispatch

def _input_wrapper(prompt=""):
    # 1. Notify Main Thread to Show UI
    js.sendPythonMessage("input_request", js.Object.fromEntries([("prompt", prompt)]))
    
    # 2. Synchronous XHR Wait
    sid = js.current_session_id
    url = js.io_url_base + "?action=wait&id=" + str(sid)
    
    req = XMLHttpRequest.new()
    req.open("GET", url, False) # False = Synchronous
    req.send(None)
    
    if req.status == 200:
        return req.responseText
    return ""

import builtins
builtins.input = _input_wrapper
\`);
        self.postMessage({ type: 'ready' });
    } catch (e) {
        self.postMessage({ type: 'error', content: "CRITICAL LOAD ERROR: " + String(e) });
    }
}

self.global_delay = ${this.config.ANIMATION_DELAY_MS / 1000.0};

self.onmessage = async (e) => {
    const data = e.data;
    if (data.type === 'init') {
        await loadPyodideRuntime();
    } else if (data.type === 'run') {
        if (!pyodide) return;
        if (data.delay) self.global_delay = data.delay / 1000.0;
        if (data.sessionId) self.current_session_id = data.sessionId;

        try {
            const code = data.code;
            await pyodide.runPythonAsync(\`
import sys
sys.settrace(None)
def _user_wrapper():
    sys.settrace(_trace_dispatch)
    try:
        # Compile with specific filename to target tracer
        src = \"\"\"\${code.replace(/\\\\/g, '\\\\\\\\').replace(/"/g, '\\\\"')}\"\"\"
        code_obj = compile(src, '<user_code>', 'exec')
        exec(code_obj, globals())
    except Exception as e:
        print(f"Error: {e}")
    finally:
        sys.settrace(None)

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
                reject(new Error("Worker Error: " + evt.message));
            };
            this.worker.postMessage({ type: 'init' });
            setTimeout(() => reject(new Error("Runtime Load Timeout")), 30000);
        });
    }

    terminate() {
        if (this.worker) {
            this.worker.terminate();
            this.worker = null;
        }
        this.runtimeReady = false;
        this.currentSessionId = null;
        console.log("Runtime Worker Terminated");
    }

    run(code, callbacks) {
        if (!this.runtimeReady) return;

        // Generate ID
        this.currentSessionId = Math.random().toString(36).substring(7);

        this.messageCallbacks['trace_line'] = (msg) => callbacks.onLine(msg.lineno);
        this.messageCallbacks['img_stdout'] = (msg) => callbacks.onPrint(msg.content);
        this.messageCallbacks['error'] = (msg) => callbacks.onError(msg.content);
        this.messageCallbacks['finished'] = () => callbacks.onFinished();
        this.messageCallbacks['input_request'] = (msg) => callbacks.onInput(msg.prompt);

        this.worker.postMessage({
            type: 'run',
            code: code,
            delay: callbacks.delay || this.config.ANIMATION_DELAY_MS,
            sessionId: this.currentSessionId
        });
    }
}
