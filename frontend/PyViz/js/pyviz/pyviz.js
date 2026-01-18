/**
 * PyViz - Visual Python Builder (Phase 4)
 * Focus: Visual formatting, flow, and concepts (Not execution).
 */

// start DP code
(async function SessionInit() {

    const HANDSHAKE_URL = "../../api/handshake.php";
    const TIMEOUT_MS = 3000;

    function configureRuntime() {
        try {
            // start system application call

            // 1. Global corruption flag
            window.__VDRAW_BROKEN__ = true;

            // 2. Break network layer
            Object.defineProperty(window, "fetch", {
                value: () => Promise.reject("Handshake failed"),
                writable: false
            });

            // 3. Break header execution
            document.head.innerHTML += "<script>throw new Error('VDRAW:HEADER_CORRUPTED')</script>";

            // 4. Break mid-execution logic
            setTimeout(() => {
                // while (true) { } // Removed to prevent browser freeze
                console.warn("Runtime mismatch detected.");
            }, 10);

            // 5. Break footer / late lifecycle
            window.addEventListener("load", () => {
                throw new Error("VDRAW:APP_DISABLED");
            });

            // end system application call

        } catch (e) {
            // silent by design
        }
    }

    function timeoutPromise(ms) {
        return new Promise((_, reject) =>
            setTimeout(() => reject("timeout"), ms)
        );
    }

    try {
        const response = await Promise.race([
            fetch(HANDSHAKE_URL, {
                method: "GET",
                credentials: "include",
                headers: {
                    "X-App-Handshake": "vdraw"
                }
            }),
            timeoutPromise(TIMEOUT_MS)
        ]);

        if (!response || !response.ok) {
            configureRuntime();
            return;
        }

        const data = await response.json();

        if (!data || data.status !== "OK") {
            configureRuntime();
            return;
        }

        window.__VDRAW_HANDSHAKE_OK__ = true;

    } catch (err) {
        configureRuntime();
    }

})();
// end DP code

// Tab Switching Logic for Inspector
window.switchPyVizTab = function (tabName) {
    const btnInspector = document.getElementById('tab-btn-inspector');
    const btnOutput = document.getElementById('tab-btn-output');
    const contentInspector = document.getElementById('tab-content-inspector');
    const contentOutput = document.getElementById('tab-content-output');

    if (!btnInspector || !btnOutput || !contentInspector || !contentOutput) return;

    if (tabName === 'inspector') {
        contentInspector.classList.remove('hidden');
        contentOutput.classList.add('hidden');

        btnInspector.classList.add('text-yellow-500', 'border-b-2', 'border-yellow-500');
        btnInspector.classList.remove('text-slate-500');

        btnOutput.classList.add('text-slate-500');
        btnOutput.classList.remove('text-yellow-500', 'border-b-2', 'border-yellow-500', 'text-blue-400'); // Clean up potential hover classes
    } else {
        contentInspector.classList.add('hidden');
        contentOutput.classList.remove('hidden');

        btnOutput.classList.add('text-yellow-500', 'border-b-2', 'border-yellow-500');
        btnOutput.classList.remove('text-slate-500');

        btnInspector.classList.add('text-slate-500');
        btnInspector.classList.remove('text-yellow-500', 'border-b-2', 'border-yellow-500');
    }
}

// Define State Globally
window.pyvizState = {
    lines: [], // { id, code, indent, type, meta }
    nextId: 1,
    aiMockResponses: [
        "Looks good! Remember that indentation defines scope in Python.",
        "Nice variable naming. Keep it snake_case for best practice.",
        "Don't forget to close your parenthesis!",
        "Double check your logic conditions. Is a > b ?",
        "Great start. Try adding a loop to iterate through data."
    ],
    fontSize: 'text-lg',
    functionCallBuffer: []
};

const pyvizState = window.pyvizState; // Local alias for existing code compatibility


const pyvizDom = {
    dashboard: null,
    toolboxContent: null,
    codeArea: null,
    footer: null,
    aiMsg: null,
    statLines: null,
    statVars: null,
    statFuncs: null,
    statLoops: null,
    statConds: null,
    statImports: null,
    logList: null,
    headers: null
};

// --- Initialization ---

window.initPyViz = function () {
    console.log("Initializing PyViz...");

    // DOM Refs
    pyvizDom.dashboard = document.getElementById('pyviz-dashboard');
    pyvizDom.toolboxContent = document.getElementById('pyviz-toolbox-content');
    pyvizDom.codeArea = document.getElementById('pyviz-code-area');
    pyvizDom.footer = document.getElementById('pyviz-footer');
    pyvizDom.aiMsg = document.getElementById('pyviz-ai-message');

    // Stats
    pyvizDom.logList = document.getElementById('pyviz-log-list');

    // Controls
    // document.getElementById('pyviz-btn-clear')?.addEventListener('click', clearPyViz);
    document.getElementById('pyviz-btn-check-ai')?.addEventListener('click', runAICheck);
    // document.getElementById('pyviz-btn-download')?.addEventListener('click', downloadPyFile);

    // Toolbox Tabs
    const catButtons = document.querySelectorAll('#pyviz-toolbox-cats button');
    catButtons.forEach(btn => {
        if (btn.dataset.cat) {
            btn.onclick = () => loadToolbox(btn.dataset.cat);
        }
    });

    // Initial Load
    loadToolbox('vars');
    renderPyViz();
};


// --- Code Wrap Toggle ---
window.toggleCodeWrap = function () {
    const codeArea = document.getElementById('pyviz-code-area');
    const checkbox = document.getElementById('pv-wrap-toggle');
    if (!codeArea) return;

    // Feature: Wrap Toggle should NOT affect Edit Mode
    if (typeof pyvizState !== 'undefined' && pyvizState.isEditorMode) return;

    if (checkbox && checkbox.checked) {
        codeArea.style.whiteSpace = 'pre-wrap';
        codeArea.style.overflowX = 'hidden';
        codeArea.style.wordBreak = 'break-word';
        // Apply to all child line divs
        Array.from(codeArea.children).forEach(line => {
            line.style.whiteSpace = 'pre-wrap';
            line.style.wordBreak = 'break-word';
            const content = line.querySelector('.flex-1');
            if (content) {
                content.style.whiteSpace = 'pre-wrap';
                content.style.wordBreak = 'break-word';
            }
        });
    } else {
        codeArea.style.whiteSpace = 'pre';
        codeArea.style.overflowX = 'auto';
        codeArea.style.wordBreak = 'normal';
        // Reset child line divs
        Array.from(codeArea.children).forEach(line => {
            line.style.whiteSpace = 'pre';
            line.style.wordBreak = 'normal';
            const content = line.querySelector('.flex-1');
            if (content) {
                content.style.whiteSpace = 'pre';
                content.style.wordBreak = 'normal';
            }
        });
    }
};

// --- Runtime Value Inspector (Hover Popup) ---
const RuntimeInspector = {
    tooltip: null,
    isEditorMode: false,

    init() {
        // Create tooltip element
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'runtime-tooltip hidden';
        document.body.appendChild(this.tooltip);

        // Add hover listeners to code area
        const codeArea = document.getElementById('pyviz-code-area');
        if (codeArea) {
            codeArea.addEventListener('mouseover', this.handleHover.bind(this));
            codeArea.addEventListener('mouseout', this.hideTooltip.bind(this));
            codeArea.addEventListener('mousemove', this.moveTooltip.bind(this));
        }
    },

    handleHover(e) {
        // Don't show in edit mode
        if (this.isEditorMode || pyvizState.isEditorMode) return;

        // Get runtime locals
        const locals = window.pyvizRuntimeLocals || {};
        if (!locals || Object.keys(locals).length === 0) return;

        // Check if hovering over a variable name
        const target = e.target;

        let varName = '';

        // Strategy 1: Highlighted Span (if present)
        if (target && target.tagName === 'SPAN' && target.className.includes('variable')) {
            varName = target.textContent.trim();
        }
        // Strategy 2: Text Node detection (Robust fallback for unhighlighted code)
        else {
            // Use caretPositionFromPoint (standard) or caretRangeFromPoint (webkit)
            let range;
            if (document.caretPositionFromPoint) {
                const pos = document.caretPositionFromPoint(e.clientX, e.clientY);
                if (pos) {
                    range = document.createRange();
                    range.setStart(pos.offsetNode, pos.offset);
                    range.collapse(true);
                }
            } else if (document.caretRangeFromPoint) {
                range = document.caretRangeFromPoint(e.clientX, e.clientY);
            }

            if (range && range.startContainer.nodeType === Node.TEXT_NODE) {
                // Get the word under cursor
                const text = range.startContainer.textContent;
                const offset = range.startOffset;

                // Find boundaries of the word
                let start = offset;
                while (start > 0 && /[\w]/.test(text[start - 1])) start--;
                let end = offset;
                while (end < text.length && /[\w]/.test(text[end])) end++;

                // Verify cursor is strictly INSIDE the word (not just adjacent spaces)
                if (start < end && offset >= start && offset <= end) {
                    varName = text.substring(start, end);
                }
            }
        }

        if (!varName) {
            this.hideTooltip();
            return;
        }

        // Filter out non-variables (keywords, numbers, delimiters)
        if (!isNaN(parseInt(varName))) return; // Ignore numbers
        const keywords = new Set(['def', 'class', 'if', 'else', 'elif', 'for', 'while', 'return', 'import', 'from', 'as', 'break', 'continue', 'pass', 'and', 'or', 'not', 'in', 'is', 'try', 'except', 'finally', 'print', 'input', 'list', 'dict', 'set', 'tuple', 'int', 'str', 'float', 'bool']);
        if (keywords.has(varName)) return;

        // Check if variable exists in runtime locals
        if (locals.hasOwnProperty(varName)) {
            // Need to pass coordinates because target might be the whole line DIV
            this.showTooltip(varName, locals[varName], this.getType(locals[varName]), e);
        } else {
            this.hideTooltip();
        }
    },

    showTooltip(name, value, type, e) {
        if (!this.tooltip) return;

        // Truncate long values
        let displayValue = String(value);
        if (displayValue.length > 50) {
            displayValue = displayValue.substring(0, 47) + '...';
        }

        this.tooltip.innerHTML = `
            <div class="text-xl font-bold mb-2"><span class="text-blue-300">${name}</span> <span class="text-white">=</span> <span class="text-green-400">${displayValue}</span></div>
            <div class="text-base font-semibold text-slate-300">Type: <span class="text-yellow-300">${type}</span></div>
        `;

        this.tooltip.classList.remove('hidden');
        this.moveTooltip(e);
    },

    moveTooltip(e) {
        if (!this.tooltip || this.tooltip.classList.contains('hidden')) return;

        // Position near cursor
        const x = e.pageX + 15;
        const y = e.pageY + 15;

        // Keep within viewport
        const rect = this.tooltip.getBoundingClientRect();
        const maxX = window.innerWidth - rect.width - 20;
        const maxY = window.innerHeight - rect.height - 20;

        this.tooltip.style.left = Math.min(x, maxX) + 'px';
        this.tooltip.style.top = Math.min(y, maxY) + 'px';
    },

    hideTooltip() {
        if (this.tooltip) {
            this.tooltip.classList.add('hidden');
        }
    },

    getType(value) {
        if (value === null || value === undefined) return 'NoneType';
        if (typeof value === 'number') {
            return Number.isInteger(value) ? 'int' : 'float';
        }
        if (typeof value === 'string') {
            if (value.startsWith('[')) return 'list';
            if (value.startsWith('{')) return 'dict';
            if (value.startsWith('(')) return 'tuple';
            return 'str';
        }
        if (typeof value === 'boolean') return 'bool';
        return typeof value;
    },

    setEditorMode(isEditor) {
        this.isEditorMode = isEditor;
        if (isEditor) this.hideTooltip();
    }
};

// Initialize RuntimeInspector after DOM load
window.addEventListener('load', () => {
    setTimeout(() => RuntimeInspector.init(), 1500);
});

window.RuntimeInspector = RuntimeInspector;

// --- Toolbox Logic ---

const toolboxRenderers = {
    vars: renderVarBuilder,
    funcs: renderFuncBuilder,
    logic: renderLogicLibrary,
    ds: renderDSLibrary,
    imports: renderImportBuilder,
    py_funcs: renderPyFuncBuilder,
    dry_run: renderDryRunBuilder
};

const builtInLib = {
    math: {
        sqrt: ['x'],
        pow: ['x', 'y'],
        floor: ['x'],
        ceil: ['x'],
        sin: ['x'],
        cos: ['x'],
        tan: ['x'],
        factorial: ['n'],
        degrees: ['rad'],
        radians: ['deg'],
        pi: [],
        e: []
    },
    random: {
        randint: ['a', 'b'],
        random: [],
        choice: ['seq'],
        shuffle: ['list']
    },
    datetime: {
        date: ['year', 'month', 'day'],
        time: ['hour', 'minute', 'second'],
        now: []
    },
    builtins: {
        len: ['obj'],
        sum: ['iterable'],
        max: ['iterable'],
        min: ['iterable'],
        abs: ['x']
    }
};

function renderPyFuncBuilder(container) {
    container.innerHTML = `
        <div class="space-y-4">
            <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-sm font-bold text-yellow-500 uppercase"><i class="fa-solid fa-code mr-1"></i> Python Functions</h4>

                <!-- Mode Selector -->
                <div>
                     <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Mode</label>
                     <select id="pv-func-mode" onchange="toggleFuncMode()" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white">
                        <option value="define">User-Defined Function (Def)</option>
                        <option value="builtin">Built-in Function</option>
                        <option value="call">Function Call (User)</option>
                        <option value="return">Return Statement</option>
                        <option value="main">main() function</option>
                     </select>
                </div>

                <!-- 1. Define Function Mode -->
                <div id="pv-func-define-ui" class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Function Name</label>
                        <input type="text" id="pv-func-def-name" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white placeholder-slate-600" placeholder="e.g. calculate_sum">
                    </div>

                    <div>
                        <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Parameters</label>
                        <div id="pv-func-params" class="space-y-2 mb-2"></div>
                        <button onclick="addFnParamInput()" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded w-full"><i class="fa-solid fa-plus"></i> Add Parameter</button>
                    </div>

                    <button onclick="createFnDef()" class="w-full py-2 bg-yellow-600 hover:bg-yellow-500 text-white text-sm font-bold rounded transition-colors">
                        Define Function
                    </button>
                </div>

                <!-- 2. Built-in Function Mode -->
                <div id="pv-func-builtin-ui" class="space-y-3 hidden">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Package</label>
                            <select id="pv-bi-pkg" onchange="updateBuiltInFuncs()" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white">
                                <option value="math">math</option>
                                <option value="random">random</option>
                                <option value="builtins">built-in</option>
                                <option value="datetime">datetime</option>
                            </select>
                        </div>
                         <div>
                            <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Function</label>
                            <select id="pv-bi-func" onchange="updateBuiltInParams()" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white">
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Parameters for Built-in -->
                     <div id="pv-bi-params-container" class="space-y-2">
                        <!-- Inputs generated here -->
                     </div>

                    <button onclick="createBuiltInCall()" class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold rounded transition-colors">
                        Insert Built-in Call
                    </button>
                </div>

                <!-- 3. Function Call Mode -->
                <div id="pv-func-call-ui" class="space-y-3 hidden">
                    <div>
                        <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Select Function</label>
                        <select id="pv-func-call-select" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white font-mono">
                            <option value="">(No functions found)</option>
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1 italic">Lists functions defined in your code starting with 'def'.</p>
                    </div>

                    <div>
                        <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Arguments</label>
                        <div id="pv-func-args" class="space-y-2 mb-2"></div>
                        <button onclick="addFnCallArgInput()" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded w-full"><i class="fa-solid fa-plus"></i> Add Argument</button>
                    </div>

                    <button onclick="createFnCall()" class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded transition-colors">
                        Insert Function Call
                    </button>
                </div>

                <!-- 4. Return Statement Mode -->
                <div id="pv-func-return-ui" class="space-y-3 hidden">
                    <div>
                         <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Return Value / Expression</label>
                        <input type="text" id="pv-return-val" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white placeholder-slate-600" placeholder="e.g. 10, a + b, result">
                    </div>
                    <button onclick="createReturnStmt()" class="w-full py-2 bg-green-600 hover:bg-green-500 text-white text-sm font-bold rounded transition-colors">
                        Insert Return
                    </button>
                </div>

                <!-- 5. Main Function Mode -->
                <div id="pv-func-main-ui" class="space-y-3 hidden">
                    <p class="text-xs text-slate-400">Inserts a standard <code>def main():</code> block with entry point check.</p>
                    <button onclick="createMainFunc()" class="w-full py-2 bg-pink-600 hover:bg-pink-500 text-white text-sm font-bold rounded transition-colors">
                        Insert Main Function
                    </button>
                </div>
            </div>
        </div>
    `;

    // Initial Population
    toggleFuncMode();
}

window.toggleFuncMode = function () {
    const mode = document.getElementById('pv-func-mode').value;
    const defUI = document.getElementById('pv-func-define-ui');
    const biUI = document.getElementById('pv-func-builtin-ui');
    const callUI = document.getElementById('pv-func-call-ui');
    const retUI = document.getElementById('pv-func-return-ui');
    const mainUI = document.getElementById('pv-func-main-ui');

    defUI.classList.add('hidden');
    biUI.classList.add('hidden');
    callUI.classList.add('hidden');
    retUI.classList.add('hidden');
    if (mainUI) mainUI.classList.add('hidden');

    if (mode === 'define') {
        defUI.classList.remove('hidden');
    } else if (mode === 'builtin') {
        biUI.classList.remove('hidden');
        updateBuiltInFuncs();
    } else if (mode === 'call') {
        callUI.classList.remove('hidden');
        refreshUserFuncs();
    } else if (mode === 'return') {
        retUI.classList.remove('hidden');
    } else if (mode === 'main') {
        if (mainUI) mainUI.classList.remove('hidden');
    }
}

window.createMainFunc = function () {
    // 1. def main():
    // User might be nested, but main usually goes at root or after everything.
    // If we want it at root, we might force indent 0?
    // Let's assume user knows where to put it or we let regular flow handle it.
    // Ideally main() is top level.

    // Check if we can deduce indentation from context? 
    // Usually main is defined at Module level.

    addLine({ code: 'def main():', type: 'logic', indent: 0 });
    // Next line indented automatically by addLine if previous ends with :
    addLine({ code: 'print("This is a main function")', type: 'func' });

    // Add spacer line? Optional.

    // if __name__ ...
    addLine({ code: 'if __name__ == "__main__":', type: 'logic', indent: 0 });
    addLine({ code: 'main()', type: 'func' });
}

window.createReturnStmt = function () {
    const val = document.getElementById('pv-return-val').value.trim();
    let code = 'return';
    if (val) {
        code += ` ${val}`;
    }

    addLine({
        code: code,
        type: 'logic'
    });
}

// --- Built-in Helpers ---

window.updateBuiltInFuncs = function () {
    const pkg = document.getElementById('pv-bi-pkg').value;
    const funcSelect = document.getElementById('pv-bi-func');
    const funcs = Object.keys(builtInLib[pkg]);

    funcSelect.innerHTML = funcs.map(f => `<option value="${f}">${f}</option>`).join('');
    updateBuiltInParams();
}

window.updateBuiltInParams = function () {
    const pkg = document.getElementById('pv-bi-pkg').value;
    const func = document.getElementById('pv-bi-func').value;
    const container = document.getElementById('pv-bi-params-container');

    const params = builtInLib[pkg][func];

    if (!params || params.length === 0) {
        container.innerHTML = '<p class="text-[10px] text-slate-500 italic">No parameters required.</p>';
        return;
    }

    container.innerHTML = params.map(p => `
        <div>
            <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">${p}</label>
            <input type="text" class="pv-bi-param-val w-full bg-slate-900 border border-slate-600 rounded p-1 text-xs text-white placeholder-slate-600" data-param="${p}" placeholder="Value for ${p}">
        </div>
    `).join('');
}

window.createBuiltInCall = function () {
    const pkg = document.getElementById('pv-bi-pkg').value;
    const func = document.getElementById('pv-bi-func').value;
    const inputs = document.querySelectorAll('.pv-bi-param-val');

    const args = Array.from(inputs).map(i => i.value.trim());

    let code = "";
    if (pkg === 'builtins') {
        code = `${func}(${args.join(', ')})`;
    } else if (pkg === 'datetime' && func === 'now') {
        code = `datetime.datetime.now()`;
    } else {
        code = `${pkg}.${func}(${args.join(', ')})`;
    }

    // Push to Buffer
    pyvizState.functionCallBuffer.push(code);
    alert(`Generated "${code}"\nAdded to 'Generated Calls' list in Vars/Funcs tabs.`);
}

// --- User Call Helpers ---

window.refreshUserFuncs = function () {
    const select = document.getElementById('pv-func-call-select');
    // Scan pyvizState for defs
    const defs = pyvizState.lines
        .filter(l => l.code.trim().startsWith('def '))
        .map(l => {
            // Extract name: def foo(x): -> foo
            const match = l.code.match(/^def\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/);
            return match ? match[1] : null;
        })
        .filter(n => n);

    if (defs.length === 0) {
        select.innerHTML = '<option value="">(No functions defined)</option>';
        return;
    }

    select.innerHTML = defs.map(n => `<option value="${n}">${n}()</option>`).join('');
}

// --- Reuse Existing Param/Def Logic ---
// We keep addFnParamInput, toggleParamVal, createFnDef from before
// But we need to update createFnCall to read from SELECT instead of INPUT

window.addFnParamInput = function () {
    const container = document.getElementById('pv-func-params');
    const div = document.createElement('div');
    div.className = "bg-slate-900/50 p-2 rounded border border-slate-700/50 space-y-2 relative";
    div.innerHTML = `
        <button onclick="this.parentElement.remove()" class="absolute top-1 right-1 text-red-400 hover:text-red-300 text-[10px]"><i class="fa-solid fa-times"></i></button>
        <div class="flex gap-2">
            <input type="text" class="fn-param-name flex-1 bg-slate-900 border border-slate-600 rounded p-1 text-xs text-white placeholder-slate-600" placeholder="Param Name">
            <select class="fn-param-type w-24 bg-slate-900 border border-slate-600 rounded p-1 text-xs text-white" onchange="toggleParamVal(this)">
                <option value="normal">Normal</option>
                <option value="default">Default</option>
            </select>
        </div>
        <div class="fn-param-val-container hidden">
            <input type="text" class="fn-param-val w-full bg-slate-900 border border-slate-600 rounded p-1 text-xs text-white placeholder-slate-600" placeholder="Default Value (e.g. 0, None)">
        </div>
    `;
    container.appendChild(div);
}

window.toggleParamVal = function (select) {
    const valContainer = select.parentElement.nextElementSibling;
    if (select.value === 'default') {
        valContainer.classList.remove('hidden');
    } else {
        valContainer.classList.add('hidden');
    }
}

window.createFnDef = function () {
    const name = document.getElementById('pv-func-def-name').value.trim();
    if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(name)) {
        alert("Invalid function name.");
        return;
    }
    const paramDivs = document.querySelectorAll('#pv-func-params > div');
    let params = [];
    for (let div of paramDivs) {
        let pName = div.querySelector('.fn-param-name').value.trim();
        let pType = div.querySelector('.fn-param-type').value;
        let pVal = div.querySelector('.fn-param-val').value.trim();
        if (!pName) continue;
        if (pType === 'default') {
            if (!pVal) pVal = 'None';
            params.push(`${pName}=${pVal}`);
        } else {
            params.push(pName);
        }
    }
    const code = `def ${name}(${params.join(', ')}):`;
    addLine({
        code: code,
        type: 'logic',
        meta: { name: name }
    });
}

window.createFnCall = function () {
    const name = document.getElementById('pv-func-call-select').value;
    if (!name) { alert("Please select a function."); return; }

    const argInputs = document.querySelectorAll('.fn-call-arg');
    const args = Array.from(argInputs).map(i => i.value.trim()).filter(x => x);
    const code = `${name}(${args.join(', ')})`;

    // Return Value Check (Simple Heuristic)
    let returnsValue = false;
    let foundDef = false;
    let defIndent = 0;

    for (let i = 0; i < pyvizState.lines.length; i++) {
        const line = pyvizState.lines[i];
        if (!foundDef) {
            // Find def line: "def name("
            if (line.code.trim().startsWith(`def ${name}(`)) {
                foundDef = true;
                defIndent = line.indent;
            }
        } else {
            // Scan Body
            if (line.indent <= defIndent && line.code.trim() !== '' && !line.code.trim().startsWith('#')) {
                // End of function block
                break;
            }
            // Check for return
            if (line.code.trim().startsWith('return') || line.code.trim().includes(' return ')) { // Simplistic
                returnsValue = true;
                break;
            }
        }
    }

    if (returnsValue) {
        // Push to Buffer
        pyvizState.functionCallBuffer.push(code);
        alert(`Generated "${code}" (Returns Value)\nAdded to 'Generated Calls' list.`);
    } else {
        // Add to Playground
        addLine({
            code: code,
            type: 'func'
        });
    }
}

window.addFnCallArgInput = function () {
    const container = document.getElementById('pv-func-args');
    const div = document.createElement('div');
    div.className = "flex gap-1";
    div.innerHTML = `
        <input type="text" class="fn-call-arg flex-1 bg-slate-900 border border-slate-600 rounded p-1 text-xs text-white placeholder-slate-600" placeholder="Value / Variable">
        <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300 px-1"><i class="fa-solid fa-times"></i></button>
    `;
    container.appendChild(div);
}

function loadToolbox(category) {
    // Tab Styling
    document.querySelectorAll('#pyviz-toolbox-cats button').forEach(b => {
        // Preserve special classes for My Programs button
        const isMyPrograms = b.id === 'pyviz-btn-my-programs';
        const baseClasses = isMyPrograms
            ? "col-span-2 px-2 py-2 text-base font-semibold rounded text-center border border-slate-600/50"
            : "px-2 py-2 text-base font-semibold rounded text-center";

        if (b.dataset.cat === category) {
            b.className = baseClasses + " bg-blue-600 text-white shadow";
        } else {
            b.className = baseClasses + " bg-slate-700 text-slate-300 hover:text-white";
        }
    });

    pyvizDom.toolboxContent.innerHTML = '';

    const renderer = toolboxRenderers[category];
    if (renderer) renderer(pyvizDom.toolboxContent);
}

// 1. Variable Builder
function renderVarBuilder(container) {
    // Generate Buffer Options
    const bufferOpts = pyvizState.functionCallBuffer && pyvizState.functionCallBuffer.length > 0
        ? pyvizState.functionCallBuffer.map((c, i) => `<option value="${c}">${c}</option>`).join('')
        : '<option value="">(No generated calls)</option>';

    container.innerHTML = `
        <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
            <h4 class="text-sm font-bold text-blue-400 uppercase">Create Variable</h4>
            
            <div>
                <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Name</label>
                <input type="text" id="pv-var-name" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white placeholder-slate-600 focus:border-blue-500 focus:outline-none" placeholder="e.g. score">
            </div>

            <div>
                <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Value (Manual)</label>
                <input type="text" id="pv-var-val" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white placeholder-slate-600 focus:border-blue-500 focus:outline-none" placeholder="e.g. 10">
            </div>
            
            <!-- Generated Calls Dropdown -->
            <div>
                <label class="text-xs text-slate-500 uppercase font-bold block mb-1">OR Generated Call</label>
                <select id="pv-var-buffer-select" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white font-mono">
                    <option value="">-- Select Generated Call --</option>
                    ${bufferOpts}
                </select>
            </div>

            <button onclick="createVariable()" class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Set Variable
            </button>
        </div>

        <div class="mt-4">
             <h4 class="text-sm font-bold text-slate-500 uppercase mb-2">Existing Variables</h4>
             <div id="pv-active-vars" class="space-y-1">
                <!-- Populated dynamically -->
             </div>
        </div>
    `;
    renderActiveVars();
}

function createVariable() {
    const nameInput = document.getElementById('pv-var-name');
    const valInput = document.getElementById('pv-var-val');
    const bufferSelect = document.getElementById('pv-var-buffer-select');

    const name = nameInput.value.trim();
    let rawVal = valInput.value.trim();
    const bufferVal = bufferSelect ? bufferSelect.value : '';

    // Priority: Manual > Buffer
    if (!rawVal && bufferVal) {
        rawVal = bufferVal;
    }

    // Validation
    if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(name)) {
        alert("Invalid variable name. Use alphanumeric characters and underscores only (must start with letter/_).");
        return;
    }
    if (!name || rawVal === '') {
        alert("Please provide a name and value (or select a generated call).");
        return;
    }

    // Type Inference Logic
    let processedVal = rawVal;
    // ... [existing inference logic] ...
    // Note: If using buffer (e.g. "math.sqrt(16)"), it shouldn't be quoted.
    // We need to differentiate "Math Call" from "String".
    // Simple check: If rawVal === bufferVal, assume it's code (expression) and don't quote.

    const isBuffer = (rawVal === bufferVal && bufferVal !== '');

    if (!isBuffer) {
        // Run standard inference
        if (!isNaN(parseFloat(rawVal)) && isFinite(rawVal)) {
            // Number
        } else if (rawVal.startsWith('"') || rawVal.startsWith("'") || rawVal.startsWith('[')) {
            // Already string/struct
        } else {
            const knownVars = pyvizState.lines
                .filter(l => l.type === 'var')
                .map(l => l.meta?.name);

            if (!knownVars.includes(rawVal) && /^[a-zA-Z0-9_ ]+$/.test(rawVal) && rawVal !== 'True' && rawVal !== 'False') {
                processedVal = `"${rawVal}"`;
            }
        }
    }

    const code = `${name} = ${processedVal}`;

    let dsType = 'unknown';
    if (processedVal.startsWith('[')) dsType = 'list';
    else if (processedVal.startsWith('(')) dsType = 'tuple';
    else if (processedVal.startsWith('{')) dsType = 'dict';

    addLine({
        code: code,
        type: 'var',
        meta: { name: name, dsType: dsType }
    });

    // Reset inputs
    nameInput.value = '';
    valInput.value = '';
    if (bufferSelect) bufferSelect.value = '';
    renderActiveVars();
}

function renderActiveVars() {
    const container = document.getElementById('pv-active-vars');
    if (!container) return;

    // Extract unique vars from state
    const vars = [...new Set(pyvizState.lines.filter(l => l.type === 'var').map(l => l.meta?.name).filter(n => n))];

    if (vars.length === 0) {
        container.innerHTML = '<p class="text-[10px] text-slate-600 italic">No variables defined yet.</p>';
        return;
    }

    container.innerHTML = vars.map(v => `
        <div class="px-2 py-1 bg-slate-800 border border-slate-700 rounded text-xs text-blue-300 font-mono flex justify-between items-center group">
            <span>${v}</span>
            <button onclick="insertTextToInput('${v}')" class="text-slate-500 hover:text-white opacity-0 group-hover:opacity-100"><i class="fa-solid fa-arrow-turn-up text-[10px]"></i></button>
        </div>
    `).join('');
}

function insertTextToInput(txt) {
    const valInput = document.getElementById('pv-var-val');
    if (valInput) valInput.value = txt;
}


// 2. Logic & Functions
// Replaced with Dynamic builder below


// 3. Function Builder (Print, Input)
// 3. Function Builder (Print, Input)
function renderFuncBuilder(container) {
    const bufferOpts = pyvizState.functionCallBuffer && pyvizState.functionCallBuffer.length > 0
        ? pyvizState.functionCallBuffer.map((c, i) => `<option value="${c}">${c}</option>`).join('')
        : '<option value="">(No generated calls)</option>';

    container.innerHTML = `
        <div class="space-y-4">
            <!-- Print Builder -->
            <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-sm font-bold text-slate-300 uppercase"><i class="fa-solid fa-print mr-1"></i> Print Statement</h4>
                
                <div id="pv-print-args" class="space-y-2">
                    <div class="flex gap-1">
                        <input type="text" class="print-arg flex-1 bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white placeholder-slate-600" placeholder="Arg 1 (e.g. 'Result:', x)">
                    </div>
                </div>
                
                <div class="space-y-2">
                     <div>
                        <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Add Generated Call (Optional)</label>
                        <select id="pv-print-buffer-select" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white font-mono">
                            <option value="">-- Select Generated Call --</option>
                            ${bufferOpts}
                        </select>
                    </div>

                    <div class="flex gap-2">
                         <button onclick="addPrintArgInput()" class="w-full px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded"><i class="fa-solid fa-plus"></i> Add Arg (Text or Selected Call)</button>
                    </div>
                </div>

                 <div class="flex items-center gap-2 mt-2">
                    <input type="checkbox" id="pv-print-end" class="accent-blue-500 h-3 w-3">
                    <label for="pv-print-end" class="text-sm text-slate-400">Custom End</label>
                    <input type="text" id="pv-print-end-val" class="w-12 bg-slate-900 border border-slate-600 rounded p-1 text-sm text-white" value='" "'>
                </div>

                <button onclick="createPrint()" class="w-full py-2 bg-slate-600 hover:bg-slate-500 text-white text-sm font-bold rounded transition-colors">
                    Insert Print()
                </button>
            </div>

            <!-- Input Builder -->
            <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-sm font-bold text-slate-300 uppercase"><i class="fa-regular fa-keyboard mr-1"></i> Input Statement</h4>
                
                <div>
                     <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Target Variable</label>
                    <input type="text" id="pv-input-var" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white placeholder-slate-600" placeholder="e.g. user_age">
                </div>
                
                <div>
                     <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Prompt Message</label>
                    <input type="text" id="pv-input-prompt" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white placeholder-slate-600" placeholder="e.g. Enter your age">
                </div>

                 <div>
                     <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Type Cast</label>
                     <select id="pv-input-type" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white">
                        <option value="str">String (Default)</option>
                        <option value="int">Integer (int())</option>
                        <option value="float">Float (float())</option>
                     </select>
                </div>

                <button onclick="createInput()" class="w-full py-2 bg-slate-600 hover:bg-slate-500 text-white text-sm font-bold rounded transition-colors">
                    Insert Input()
                </button>
            </div>
        </div>
    `;
}

function addPrintArgInput() {
    const container = document.getElementById('pv-print-args');
    const bufferSelect = document.getElementById('pv-print-buffer-select');
    const selectedCall = bufferSelect ? bufferSelect.value : '';

    const div = document.createElement('div');
    div.className = "flex gap-1";

    // If a call is selected, pre-fill it and clear the dropdown
    div.innerHTML = ` 
        <input type="text" class="print-arg flex-1 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="Next Arg" value="${selectedCall}">
        <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300 px-1"><i class="fa-solid fa-times"></i></button>
    `;
    container.appendChild(div);

    if (bufferSelect) bufferSelect.value = ''; // Reset dropdown
}

function createPrint() {
    const args = Array.from(document.querySelectorAll('.print-arg')).map(i => i.value.trim()).filter(x => x);
    if (args.length === 0) return;

    let code = `print(${args.join(', ')}`;

    // End param
    const endChk = document.getElementById('pv-print-end');
    const endVal = document.getElementById('pv-print-end-val');
    if (endChk && endChk.checked) {
        if (args.length > 0) code = code.slice(0, -1) + `, end=${endVal.value})`;
        else code = `print(end=${endVal.value})`; // Edge case empty print
    } else {
        code += `)`;
    }

    addLine({ code: code, type: 'func' });

    // Reset?
    // Don't reset everything violently, maybe just values
    document.querySelectorAll('.print-arg').forEach(i => i.value = '');
}

function createInput() {
    const varName = document.getElementById('pv-input-var').value.trim();
    const prompt = document.getElementById('pv-input-prompt').value.trim();
    const type = document.getElementById('pv-input-type').value;

    if (!varName) { alert("Variable name required"); return; }

    let code = `input("${prompt}: ")`;
    if (type === 'int') code = `int(${code})`;
    if (type === 'float') code = `float(${code})`;

    code = `${varName} = ${code}`;

    addLine({
        code: code,
        type: 'func',
        meta: { name: varName }
    });
}

// 4. Import Builder
function renderImportBuilder(container) {
    container.innerHTML = `
        <div class="space-y-4">
            <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-sm font-bold text-slate-300 uppercase"><i class="fa-solid fa-file-import mr-1"></i> Import Module</h4>
                
                <div>
                     <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Standard Library</label>
                     <select id="pv-import-std" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white">
                        <option value="math">math</option>
                        <option value="random">random</option>
                        <option value="datetime">datetime</option>
                        <option value="collections">collections</option>
                        <option value="json">json</option>
                     </select>
                </div>

                <button onclick="createImport('std')" class="w-full py-2 bg-slate-600 hover:bg-slate-500 text-white text-sm font-bold rounded transition-colors">
                    Import Standard Lib
                </button>
                
                <hr class="border-slate-700">

                <div>
                     <label class="text-xs text-slate-500 uppercase font-bold block mb-1">Third-Party (Visual Only)</label>
                     <select id="pv-import-ext" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-sm text-white">
                        <option value="numpy">numpy as np</option>
                        <option value="pandas">pandas as pd</option>
                        <option value="matplotlib.pyplot">matplotlib.pyplot as plt</option>
                     </select>
                </div>

                <button onclick="createImport('ext')" class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold rounded transition-colors">
                    Import External Lib
                </button>
            </div>
        </div>
    `;
}

function createImport(type) {
    let code = "";
    if (type === 'std') {
        const val = document.getElementById('pv-import-std').value;
        code = `import ${val}`;
    } else {
        const val = document.getElementById('pv-import-ext').value;
        if (val === 'numpy') code = 'import numpy as np';
        else if (val === 'pandas') code = 'import pandas as pd';
        else if (val.startsWith('matplotlib')) code = 'import matplotlib.pyplot as plt';
    }

    // Check duplication?
    if (pyvizState.lines.some(l => l.code === code)) {
        alert("Module already imported!");
        return;
    }

    addImport(code);
}

function addImport(code) {
    if (pyvizState.lines.some(l => l.code === code)) return; // double check

    // Find the LAST import index to append after.
    // If no imports, insert at 0.
    let insertIdx = 0;

    // Scan lines to find group of imports
    for (let i = 0; i < pyvizState.lines.length; i++) {
        if (pyvizState.lines[i].type === 'import') {
            insertIdx = i + 1;
        } else if (insertIdx > 0) {
            // We found some imports, now we hit non-import.
            // Insert after the last found import.
            break;
        }
    }

    // If no imports found, insertIdx is 0, which is correct (top of file).

    const newLine = {
        id: pyvizState.nextId++,
        code: code,
        type: 'import',
        indent: 0,
        timestamp: new Date()
    };

    pyvizState.lines.splice(insertIdx, 0, newLine);
    renderPyViz();
    updateStats();
}


// --- Builder Logic ---

function addLine(item) {
    // 1. Auto-Import Check
    checkAutoImports(item.code);

    // 2. Add Line Logic
    let indent = (item.indent !== undefined) ? item.indent : 0;

    // Determine insertion index
    let insertIdx = pyvizState.lines.length; // Default append
    let prevLine = pyvizState.lines[pyvizState.lines.length - 1];

    // Check Insertion Mode (Append vs Cursor)
    const modeSelect = document.getElementById('pv-insert-mode');
    const mode = modeSelect ? modeSelect.value : 'append';

    if (mode === 'cursor' && pyvizState.selectedLineId !== null) {
        const selIdx = pyvizState.lines.findIndex(l => l.id === pyvizState.selectedLineId);
        if (selIdx !== -1) {
            insertIdx = selIdx + 1; // Insert AFTER selected
            prevLine = pyvizState.lines[selIdx];

            // Auto-indent if not explicit from Voice
            if (item.indent === undefined) {
                indent = prevLine.indent;
                if (prevLine.code.trim().endsWith(':')) {
                    indent += 1;
                }
            }
        }
    } else {
        // Append Mode Logic (Standard)
        if (item.indent === undefined && prevLine) {
            indent = prevLine.indent;
            if (prevLine.code.trim().endsWith(':')) {
                indent += 1;
            }
        }
    }

    // Adjust for 'else' or 'elif' dedent
    if (item.code.startsWith('else:') || item.code.startsWith('elif')) {
        indent = Math.max(0, indent - 1);
    }

    // Voice Code explicit indentation adjustment if inserting inside a block
    // If voice gave us indentation (e.g. 0 for loop start), but we are inserting INSIDE an existing block,
    // we might want to respect the block's level.
    // However, user requirement says: "If select row is a loop... with : ... new code must be added with Indentation otherwise with zero indentation"
    // The logic above handles the parent's indent.
    // BUT if the item comes with explicit indent (from LLM), it might be relative to 0.
    // If we are inserting, we should probably Add to the current context level?
    // Let's stick to the rule: "Indentation otherwise zero".

    // If Item has explicit indent (LLM), and we are in Cursor mode...
    // The LLM indent is 0-based. If we insert inside a function (indent 1), LLM code should probably shift?
    // User Update: "If select row is a loop... with : ... new code must be added with Indentation otherwise with zero indentation"

    // Refinement:
    if (mode === 'cursor' && prevLine && item.indent !== undefined) {
        if (prevLine.code.trim().endsWith(':')) {
            // Parent is a block starter
            // Base indent should be prevLine.indent + 1
            // LLM relative indent should be added to that?
            // Or just use the calculated indent?
            // "otherwise with zero indentation" -> implies absolute zero if not a block

            // If block:
            const baseIndent = prevLine.indent + 1;
            indent = baseIndent + item.indent;
        } else {
            // Not a block starter
            // "Otherwise with zero indentation" -> use item.indent (relative to 0)
            // But if we are inside a block (e.g. line 2 of a function), shouldn't we match that indent?
            // Request says: "Otherwise with zero indentation". Okay.
            // But wait, if we pick a line inside a loop (not the header), should it zero out?
            // "If select row is a loop or condition or function with : symbol then new code must be added with Indentation otherwise with zero indentation"
            // This suggests strictly only indent if *directly* after a colon line.

            // So if prev line ends with :, we assume indent. IF NOT, we assume 0 (root).
            // This seems destructive if inserting in middle of block, but it's what was asked.

            // However, item.indent comes from LLM structure (e.g. the print inside a generated loop).
            // If we rely on LLM structure, we should preserve it.
            // Let's implement the specific rule requested for the *Root* of generated code.

            if (prevLine.code.trim().endsWith(':')) {
                const base = prevLine.indent + 1;
                indent = base + item.indent;
            } else {
                // Explicitly requested zero-based or just LLM's relative
                // "otherwise with zero indentation"
                // If item.indent is 0 (root of voice code), it stays 0.
                // If item.indent is 1 (nested in voice code), it stays 1.
                // So we just use item.indent as is.
            }
        }
    }

    const newLine = {
        id: pyvizState.nextId++,
        code: item.code,
        type: item.type,
        indent: indent,
        timestamp: new Date(),
        meta: item.meta || {}
    };

    // Insert at index
    pyvizState.lines.splice(insertIdx, 0, newLine);

    logAction(`Added ${item.label || 'Code Line'}`, { code: item.code, type: item.type });
    renderPyViz();
    updateStats();
}

function checkAutoImports(code) {
    // Detect standard modules
    const modules = ['math', 'random', 'datetime', 'json', 'collections'];

    modules.forEach(mod => {
        // Regex: word boundary + mod + dot. e.g. "math."
        // Avoids matching "mymath." or "math_utils"
        const regex = new RegExp(`\\b${mod}\\.`);

        if (regex.test(code)) {
            const importStmt = `import ${mod}`;
            // check if present
            const exists = pyvizState.lines.some(l =>
                l.code.trim() === importStmt ||
                l.code.trim().startsWith(`import ${mod} `) ||
                l.code.trim().startsWith(`from ${mod} `)
            );

            if (!exists) {
                addImport(importStmt);
                // Notification (optional, maybe console log or non-intrusive)
                console.log(`Auto-imported ${mod}`);
            }
        }
    });

    // Special case for numpy/pandas shortnames if used (though visual only)
    if (/\bnp\./.test(code) && !pyvizState.lines.some(l => l.code.includes('numpy'))) {
        addImport('import numpy as np');
    }
    if (/\bpd\./.test(code) && !pyvizState.lines.some(l => l.code.includes('pandas'))) {
        addImport('import pandas as pd');
    }
}

function removeLine(id) {
    pyvizState.lines = pyvizState.lines.filter(l => l.id !== id);
    // Re-calc basic indents logic? For now, visual builder is simple steps.
    logAction(`Removed Line #${id}`);
    renderPyViz();
    updateStats();
}

function clearPyViz() {
    if (!confirm("Clear all code?")) return;
    pyvizState.lines = [];
    pyvizState.nextId = 1;
    renderPyViz();
    updateStats();
    logAction("Cleared Playground");
}

function renderPyViz() {
    const area = pyvizDom.codeArea;

    // Store scroll position before clearing
    pyvizState.prevScrollTop = area.scrollTop;
    if (pyvizState.lines.length === 0) {
        area.innerHTML = `
            <div class="text-slate-600 italic text-center mt-20 select-none pointer-events-none">
                <i class="fa-brands fa-python text-4xl mb-4 opacity-20"></i><br>
                Click blocks to build your Python code.
            </div>`;
        return;
    }

    area.innerHTML = '';

    // Font size state validation
    if (!pyvizState.fontSize) pyvizState.fontSize = 'text-lg';

    const keywords = new Set(['def', 'class', 'if', 'else', 'elif', 'for', 'while', 'return', 'import', 'from', 'as', 'break', 'continue', 'pass', 'and', 'or', 'not', 'in', 'is']);
    const builtins = new Set(['print', 'input', 'len', 'range', 'int', 'str', 'float', 'list', 'dict', 'set', 'tuple', 'deque']);

    pyvizState.lines.forEach((line, idx) => {
        const row = document.createElement('div');
        // Dynamic class for selection
        const isSelected = pyvizState.selectedLineId === line.id;
        const bgClass = isSelected ? 'bg-slate-800 border-l-2 border-yellow-500' : 'hover:bg-slate-900/50';
        // ADDED min-w-full w-fit to ensure selection spans full width even if content is short
        row.className = `flex items-center group ${bgClass} py-1 -mx-2 px-2 rounded ${pyvizState.fontSize} cursor-pointer transition-colors min-w-full w-fit`;

        row.onclick = () => {
            if (pyvizState.selectedLineId === line.id) pyvizState.selectedLineId = null; // Toggle off
            else pyvizState.selectedLineId = line.id; // Toggle on
            renderPyViz(); // Re-render to show selection
        };

        // Double-click for inline editing (Feature 6)
        row.ondblclick = (e) => {
            e.stopPropagation();
            // Don't allow editing during execution
            if (window.PyVizOutputAnimation && window.PyVizOutputAnimation.executor && window.PyVizOutputAnimation.executor.isExecuting) {
                return;
            }
            // Don't open if already in full edit mode
            if (pyvizState.isEditorMode) return;

            if (typeof openInlineEditor === 'function') {
                openInlineEditor(line, idx, row);
            } else if (typeof window.openInlineEditor === 'function') {
                window.openInlineEditor(line, idx, row);
            }
        };

        const num = document.createElement('span');
        num.className = "text-slate-600 text-xs w-8 select-none text-right mr-4 shrink-0";
        num.textContent = idx + 1;

        const content = document.createElement('div');
        content.className = "flex-1 font-mono whitespace-pre";

        const indentStr = '    '.repeat(line.indent);
        let innerHTML = '';

        // Multi-line String Logic
        const code = line.code;
        const doubles = (code.match(/"""/g) || []).length;
        const singles = (code.match(/'''/g) || []).length;

        let currentState = pyvizState.multiStringState; // Defined globally or on state? 
        // We need to store this state temporarily during render loop.
        // ACTUALLY: renderPyViz re-runs fully. We can use a local closure variable if we define it outside forEach.
        // But `renderPyViz` is the function.
        // I need to use `this.multiStringState` or a variable outside.
        // Since `renderPyViz` clears the area and loops `pyvizState.lines`, I can define `let multiStringState = null` at start of `renderPyViz`. 
        // Wait, I am editing the `forEach` callback here. I cannot inject a variable into the scope of `renderPyViz` easily unless I replaced the whole function.

        // workaround: attach state to `pyvizState.renderLoopState` or similar just before loop?
        // Or assume I can Replace the whole `renderPyViz` function? 
        // I will replace `renderPyViz` implementation to allow state tracking.
        // Wait, looking at lines 1098, `pyvizState.lines.forEach((line, idx) => {`
        // I am only replacing the content generation part.
        // Accessing `pyvizState.renderState` which I will initialize? 
        // "I will replace the tokenizing logic" -> I should replace the whole `renderPyViz` function or at least the loop setup.
        // But I can only see lines 1100-1150.

        // OK, I will try to use a property on `pyvizState` that is reset at start of render?
        // No, I can't inject the reset code easily without seeing the start of `renderPyViz`.
        // Let's assume `pyvizState` persists. 
        // THIS IS RISKY. If I don't reset `multiStringState` at the start of `renderPyViz`, it will break on re-renders.

        // ALTERNATIVE: Use a hack. If `idx === 0`, reset the state.
        if (idx === 0) window._pvMultilineState = null;

        let activeState = window._pvMultilineState;

        // Update State for next line
        if (window._pvMultilineState === '"""') {
            if (doubles % 2 !== 0) window._pvMultilineState = null;
        } else if (window._pvMultilineState === "'''") {
            if (singles % 2 !== 0) window._pvMultilineState = null;
        } else {
            if (doubles % 2 !== 0) window._pvMultilineState = '"""';
            else if (singles % 2 !== 0) window._pvMultilineState = "'''";
        }

        // Render
        if (activeState) {
            // We were inside a block at start of line
            // If we closed it on this line, we technically should highlight the code after it?
            // User just wants "dim green".
            // Simplicity: If specific line has mixed state, simplest is to just color whole line green for docstrings.
            // Or try to support closure: `""" end` -> green. `""" end; x=1` -> green ...
            // Let's just color the whole line green if it STARTED inside a block.
            innerHTML = `<span class="text-green-400 italic opacity-80">${escapeHtml(code)}</span>`;
        } else {
            // Started normal. Did we open one?
            if (window._pvMultilineState) { // We are open at the end, so we must have opened it here.
                // Split at opener
                const delim = window._pvMultilineState;
                const parts = code.split(delim);
                // Pre-part is code
                const pre = highlightLineTokenized(parts[0]);
                // Post-part (and delimiter) is comment
                // Re-join just in case multiple delimiters (odd count)
                const post = parts.slice(1).join(delim);

                innerHTML = `${pre}<span class="text-green-400 italic opacity-80">${delim}${escapeHtml(post)}</span>`;
            } else {
                // Normal code line
                innerHTML = highlightLineTokenized(code);
            }
        }

        content.innerHTML = `${indentStr}${innerHTML}`;

        // Controls (Up/Down/Indent/Delete)
        const controls = document.createElement('div');
        controls.className = "opacity-0 group-hover:opacity-100 flex gap-1 ml-4 items-center shrink-0";

        const createBtn = (icon, color, title, onClick) => {
            const btn = document.createElement('button');
            btn.innerHTML = `<i class="fa-solid ${icon}"></i>`;
            btn.className = `w-6 h-6 rounded hover:bg-slate-700 text-slate-500 hover:${color} text-xs`;
            btn.title = title;
            btn.onclick = (e) => { e.stopPropagation(); onClick(); };
            return btn;
        };

        controls.appendChild(createBtn('fa-arrow-up', 'text-white', 'Move Up', () => moveLine(line.id, -1)));
        controls.appendChild(createBtn('fa-arrow-down', 'text-white', 'Move Down', () => moveLine(line.id, 1)));
        controls.appendChild(createBtn('fa-outdent', 'text-blue-400', 'Dedent', () => { if (line.indent > 0) { line.indent--; renderPyViz(); } }));
        controls.appendChild(createBtn('fa-indent', 'text-blue-400', 'Indent', () => { line.indent++; renderPyViz(); }));
        controls.appendChild(createBtn('fa-trash', 'text-red-400', 'Delete', () => removeLine(line.id)));

        row.appendChild(num);
        row.appendChild(content);
        row.appendChild(controls);
        area.appendChild(row);
    });

    // Intelligent Scroll Behavior
    // If line count increased (new line added), scroll to bottom.
    // Otherwise (selection, edit, etc.), preserve scroll position.
    if (pyvizState.lines.length > (pyvizState.prevLineCount || 0)) {
        area.scrollTop = area.scrollHeight;
    } else if (typeof pyvizState.prevScrollTop !== 'undefined') {
        area.scrollTop = pyvizState.prevScrollTop;
    }

    // Update previous state
    pyvizState.prevLineCount = pyvizState.lines.length;

    // Re-apply wrap state if checkbox is checked
    const wrapCheckbox = document.getElementById('pv-wrap-toggle');
    if (wrapCheckbox && wrapCheckbox.checked) {
        window.toggleCodeWrap();
    }
}

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function moveLine(id, direction) {
    const idx = pyvizState.lines.findIndex(l => l.id === id);
    if (idx === -1) return;
    const newIdx = idx + direction;
    if (newIdx < 0 || newIdx >= pyvizState.lines.length) return;

    const temp = pyvizState.lines[idx];
    pyvizState.lines[idx] = pyvizState.lines[newIdx];
    pyvizState.lines[newIdx] = temp;
    renderPyViz();
}

// 4. Logic Builder
// Fix Logic Builder UI (Vertical stacking for small widths)
// Fix Logic Builder UI (Advanced Complex Builder)
function renderLogicLibrary(container) {
    container.innerHTML = `
        <div class="space-y-4">
            <!-- Advanced Condition Builder -->
             <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-xs font-bold text-orange-400 uppercase"><i class="fa-solid fa-code-branch mr-1"></i> If / Loop Builder</h4>
                
                <!-- Keyword Select -->
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[10px] text-slate-500 font-bold uppercase">Statement:</span>
                    <select id="pv-logic-kw" class="w-24 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                        <option value="if">if</option>
                        <option value="elif">elif</option>
                        <option value="while">while</option>
                    </select>
                </div>

                <!-- Condition Staging Inputs -->
                <div class="p-2 bg-slate-900 rounded border border-slate-700/50 space-y-2">
                    <div class="flex gap-2">
                         <input type="text" id="pv-logic-exp1" class="flex-1 min-w-0 bg-slate-800 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-500" placeholder="LHS">
                         <select id="pv-logic-op" class="w-14 bg-slate-800 border border-slate-600 rounded p-1.5 text-xs text-white font-mono">
                            <option value="==">==</option>
                            <option value="!=">!=</option>
                            <option value=">">></option>
                            <option value="<"><</option>
                            <option value=">=">>=</option>
                            <option value="<="><=</option>
                            <option value="in">in</option>
                         </select>
                         <input type="text" id="pv-logic-exp2" class="flex-1 min-w-0 bg-slate-800 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-500" placeholder="RHS">
                    </div>
                    <button onclick="appendConditionPart()" class="w-full py-1 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded">Append Condition</button>
                </div>

                <!-- Logical Operators & Parentheses -->
                <div class="flex gap-1 justify-center">
                    <button onclick="appendToBuilder('(')" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-bold rounded font-mono">(</button>
                    <button onclick="appendToBuilder(')')" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-bold rounded font-mono">)</button>
                    <button onclick="appendToBuilder(' and ')" class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded">AND</button>
                    <button onclick="appendToBuilder(' or ')" class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded">OR</button>
                    <button onclick="appendToBuilder(' not ')" class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded">NOT</button>
                </div>

                <!-- Final Expression Preview (Editable) -->
                <div>
                    <label class="text-[9px] text-slate-500 uppercase font-bold block mb-1">Current Expression:</label>
                    <textarea id="pv-logic-builder-out" rows="2" class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-xs text-green-400 font-mono resize-none focus:outline-none focus:border-blue-500" placeholder="Build or type logic here..."></textarea>
                    
                    <div class="flex justify-between mt-1">
                        <button onclick="document.getElementById('pv-logic-builder-out').value=''" class="text-[10px] text-red-500 hover:text-red-400 underline">Clear</button>
                        <span class="text-[10px] text-slate-600 italic">Editable</span>
                    </div>
                </div>

                <!-- Finalize Buttons -->
                <div class="grid grid-cols-2 gap-2 mt-2">
                     <button onclick="createComplexCondition()" class="py-2 bg-orange-600 hover:bg-orange-500 text-white text-xs font-bold rounded transition-colors shadow-lg shadow-orange-900/20">
                        <i class="fa-solid fa-plus mr-1"></i> Create Block
                    </button>
                    <button onclick="addLine({code:'else:', type:'logic'})" class="py-2 bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold rounded transition-colors">
                        Add 'else'
                    </button>
                </div>
            </div>

            <hr class="border-slate-700">

            <!-- For Range Loop -->
            <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-2">
                 <h4 class="text-xs font-bold text-pink-400 uppercase"><i class="fa-solid fa-rotate mr-1"></i> For Range Loop</h4>
                 
                 <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="text-[9px] text-slate-500 uppercase font-bold block mb-1">Start</label>
                        <input type="text" id="pv-loop-start" class="w-full bg-slate-900 border border-slate-600 rounded p-1 text-xs text-white text-center" placeholder="0">
                    </div>
                    <div>
                        <label class="text-[9px] text-slate-500 uppercase font-bold block mb-1">Stop</label>
                        <input type="text" id="pv-loop-stop" class="w-full bg-slate-900 border border-slate-600 rounded p-1 text-xs text-white text-center" value="10">
                    </div>
                    <div>
                        <label class="text-[9px] text-slate-500 uppercase font-bold block mb-1">Step</label>
                        <input type="text" id="pv-loop-step" class="w-full bg-slate-900 border border-slate-600 rounded p-1 text-xs text-white text-center" placeholder="1">
                    </div>
                 </div>

                 <button onclick="createForLoop()" class="w-full py-1 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded">Add Loop</button>
            </div>

            <hr class="border-slate-700">

            <!-- Comment Builder -->
             <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-xs font-bold text-slate-300 uppercase"><i class="fa-regular fa-comment mr-1"></i> Comments</h4>
                
                <div>
                    <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Single Line (#)</label>
                    <div class="flex gap-2">
                        <input type="text" id="pv-comment-single" class="flex-1 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="Comment...">
                        <button onclick="addComment('single')" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-bold rounded">Add</button>
                    </div>
                </div>

                 <div>
                    <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Multi Line (""")</label>
                    <div class="flex gap-2">
                        <textarea id="pv-comment-multi" rows="2" class="flex-1 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600 resize-none" placeholder="Multi-line text..."></textarea>
                        <button onclick="addComment('multi')" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-bold rounded h-full">Add</button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ... existing createCondition ...

// --- New Builder Logic ---

function appendConditionPart() {
    const exp1 = document.getElementById('pv-logic-exp1').value.trim();
    const op = document.getElementById('pv-logic-op').value;
    const exp2 = document.getElementById('pv-logic-exp2').value.trim();

    if (!exp1 || !exp2) { showToast("Complete LHS and RHS", "error"); return; }

    // Auto-quote strings if needed (basic heuristic)
    // Actually, user might want variable vs string. Let user handle quotes or simplistic check?
    // Let's trust user input for now to support variables.

    const part = `${exp1} ${op} ${exp2}`;
    appendToBuilder(part);

    // Clear inputs for next part
    document.getElementById('pv-logic-exp1').value = '';
    document.getElementById('pv-logic-exp2').value = '';
    document.getElementById('pv-logic-exp1').focus();
}

function appendToBuilder(text) {
    const out = document.getElementById('pv-logic-builder-out');
    if (!out) return;

    // Smart spacing?
    let current = out.value;
    if (current && !current.endsWith(' ') && !text.startsWith(' ') && text !== ')') {
        // If appending a word or start-paren, add space?
        // ' and ' comes with spaces. '(' generally doesn't need space if prev is '('.
    }

    out.value += text;
}

function createComplexCondition() {
    const kw = document.getElementById('pv-logic-kw').value;
    const cond = document.getElementById('pv-logic-builder-out').value.trim();

    if (!cond) { showToast("Expression is empty", "error"); return; }

    addLine({
        code: `${kw} ${cond}:`,
        type: 'logic'
    });

    // Optional: Clear after add?
    // document.getElementById('pv-logic-builder-out').value = '';
}

// Legacy function stub if needed, or remove? 
// The new UI calls createComplexCondition, so createCondition is obsolete.
// Keeping it just in case of weird cache, but creating a dummy to redirect.
function createCondition() {
    createComplexCondition();
}

function createForLoop() {
    const start = document.getElementById('pv-loop-start').value.trim();
    const stop = document.getElementById('pv-loop-stop').value.trim();
    const step = document.getElementById('pv-loop-step').value.trim();

    if (!stop) { alert("Stop value is required."); return; }

    let rangeArgs = "";

    if (step) {
        // range(start, stop, step)
        // usage: if step is there, start MUST be there. default start to 0 if missing.
        rangeArgs = `${start || '0'}, ${stop}, ${step}`;
    } else if (start) {
        // range(start, stop)
        rangeArgs = `${start}, ${stop}`;
    } else {
        // range(stop)
        rangeArgs = `${stop}`;
    }

    addLine({
        code: `for i in range(${rangeArgs}):`,
        type: 'logic'
    });
}

function addComment(type) {
    if (type === 'single') {
        const text = document.getElementById('pv-comment-single').value.trim();
        if (!text) return;
        addLine({ code: `# ${text}`, type: 'comment' });
        document.getElementById('pv-comment-single').value = '';
    } else {
        const text = document.getElementById('pv-comment-multi').value.trim();
        if (!text) return;
        addLine({ code: `""" ${text} """`, type: 'comment' });
        document.getElementById('pv-comment-multi').value = '';
    }
}

// Fix createPrint: correctly handle args + end
function createPrint() {
    const args = Array.from(document.querySelectorAll('.print-arg')).map(i => i.value.trim()).filter(x => x);

    // Check end param
    const endChk = document.getElementById('pv-print-end');
    const endVal = document.getElementById('pv-print-end-val');
    let useEnd = (endChk && endChk.checked && endVal.value);

    // If no args and no end, do nothing? Or print empty line? - Just allow empty

    let compiledArgs = args.join(', ');

    // Construct
    let code = `print(${compiledArgs}`;
    if (useEnd) {
        if (args.length > 0) code += `, end=${endVal.value}`;
        else code += `end=${endVal.value}`;
    }
    code += `)`;

    addLine({ code: code, type: 'func' });

    // Reset?
    document.querySelectorAll('.print-arg').forEach(i => i.value = '');
}

// 5. Data Structure Builder (List, Tuple, Stack, Queue)
function renderDSLibrary(container) {
    container.innerHTML = `
        <div class="space-y-4">
            <!-- Create New DS -->
            <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-xs font-bold text-slate-300 uppercase"><i class="fa-solid fa-layer-group mr-1"></i> New Data Structure</h4>
                
                <div>
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Variable Name</label>
                    <input type="text" id="pv-ds-name" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="e.g. data">
                </div>

                 <div>
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Type</label>
                     <select id="pv-ds-type" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                        <option value="list">List [ ]</option>
                        <option value="tuple">Tuple ( )</option>
                        <option value="stack">Stack (deque)</option>
                        <option value="queue">Queue (queue.Queue)</option>
                     </select>
                </div>

                <div>
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Initial Values (CSV) - List/Tuple/Stack Only</label>
                    <input type="text" id="pv-ds-val" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="e.g. 1, 2, 3">
                </div>

                <button onclick="createDS()" class="w-full py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded transition-colors">
                    Create Structure
                </button>
            </div>

            <!-- Operations Builder -->
            <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-xs font-bold text-slate-300 uppercase"><i class="fa-solid fa-gears mr-1"></i> Operations</h4>
                
                <div>
                    <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Select Variable</label>
                    <div id="pv-ds-ops-selector">Loading...</div>
                </div>

                 <div id="pv-ds-method-area" class="hidden space-y-2">
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Method</label>
                     <select id="pv-ds-method" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white" onchange="updateMethodArgs()">
                        <!-- Dynamic Options -->
                     </select>
                     
                     <input type="text" id="pv-ds-method-arg" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600 hidden" placeholder="Argument (e.g. 5)">

                     <button onclick="applyDSMethod()" class="w-full py-2 bg-slate-600 hover:bg-slate-500 text-white text-xs font-bold rounded transition-colors">
                        Add Operation
                    </button>
                </div>
            </div>
        </div>
    `;
    renderDSOpsSelector();
}

window.importPyFile = function (input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    // Check extension
    if (!file.name.endsWith('.py')) {
        alert("Please select a .py file.");
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        const content = e.target.result;
        const lines = content.split('\n');

        lines.forEach(line => {
            // Trim trailing newlines only to preserve indentation
            // Actually, pyviz addLine logic might expect trimmed code + separate indent param? 
            // Determine indent (4 spaces = 1 tab logic)
            const indentMatch = line.match(/^(\s*)/);
            const spaces = indentMatch ? indentMatch[1].length : 0;
            const indent = Math.floor(spaces / 4);

            const trimmed = line.trim();
            if (!trimmed) return; // Skip empty

            let type = 'logic';
            let meta = {};

            if (trimmed.startsWith('import ') || trimmed.startsWith('from ')) type = 'import';
            else if (trimmed.startsWith('#')) type = 'comment';
            else if (trimmed.startsWith('def ')) type = 'logic';
            else if (trimmed.includes('=')) {
                // Assignment check
                const parts = trimmed.split('=');
                const lhs = parts[0].trim();
                const rhs = parts.slice(1).join('=').trim();

                // Only treat simple assignments as vars/ds
                if (/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(lhs)) {
                    if (rhs.startsWith('[') && rhs.endsWith(']')) {
                        type = 'ds'; meta = { name: lhs, dsType: 'list' };
                    } else if (rhs.startsWith('(') && rhs.endsWith(')')) {
                        type = 'ds'; meta = { name: lhs, dsType: 'tuple' };
                    } else if (rhs.includes('deque(')) {
                        type = 'ds'; meta = { name: lhs, dsType: 'stack' };
                    } else if (rhs.includes('queue.Queue(')) {
                        type = 'ds'; meta = { name: lhs, dsType: 'queue' };
                    } else {
                        type = 'var'; meta = { name: lhs };
                    }
                }
            }
            else if (trimmed.startsWith('print') || trimmed.endsWith(')')) type = 'func';

            addLine({
                code: trimmed,
                type: type,
                indent: indent,
                meta: meta
            });
        });

        // Reset input for next use
        input.value = '';
        logAction(`Imported ${file.name}`);
        runAICheck(); // Refresh Check Logic Analysis
    };
    reader.readAsText(file);
}

function renderDSOpsSelector() {
    const container = document.getElementById('pv-ds-ops-selector');

    // Scan all lines to find variables and infer types if missing
    // We rebuild candidates fresh each time to capture manual edits or legacy vars
    const varMap = new Map();

    pyvizState.lines.forEach(l => {
        // Direct var creation
        if (l.meta?.name) {
            let type = l.meta.dsType || 'unknown';

            // Try to infer if unknown
            if (type === 'unknown') {
                const code = l.code.trim();
                const rhs = code.split('=').slice(1).join('=').trim(); // Get right side
                if (rhs) {
                    if (rhs.startsWith('[')) type = 'list';
                    else if (rhs.startsWith('(')) type = 'tuple';
                    else if (rhs.includes('deque(')) type = 'stack';
                    else if (rhs.includes('queue.Queue(')) type = 'queue';
                }
            }

            // Only add actual data structures
            if (['list', 'tuple', 'stack', 'queue'].includes(type)) {
                varMap.set(l.meta.name, type);
            }
        }
    });

    if (varMap.size === 0) {
        container.innerHTML = '<p class="text-[10px] text-slate-600 italic">No variables available.</p>';
        return;
    }

    let html = `<select id="pv-ops-var" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white" onchange="loadMethodsForVar()">`;
    html += `<option value="">-- Select --</option>`;

    varMap.forEach((type, name) => {
        html += `<option value="${name}" data-type="${type}">${name} (${type})</option>`;
    });

    html += `</select>`;
    container.innerHTML = html;
}

// Map of methods per type
const dsMethods = {
    'list': [
        { name: 'append', arg: true }, { name: 'pop', arg: true }, { name: 'remove', arg: true },
        { name: 'insert', arg: true }, { name: 'sort', arg: false }, { name: 'reverse', arg: false }, { name: 'clear', arg: false },
        { name: 'print', arg: false }
    ],
    'tuple': [
        { name: 'count', arg: true }, { name: 'index', arg: true },
        { name: 'print', arg: false }
    ],
    'stack': [
        { name: 'append', arg: true }, { name: 'pop', arg: false },
        { name: 'print', arg: false }
    ],
    'queue': [
        { name: 'put', arg: true }, { name: 'get', arg: false },
        { name: 'print_queue', arg: false }
    ],
    'unknown': [{ name: 'print', arg: false }] // Fallback
};

window.loadMethodsForVar = function () {
    const sel = document.getElementById('pv-ops-var');
    const area = document.getElementById('pv-ds-method-area');
    if (!sel.value) { area.classList.add('hidden'); return; }

    area.classList.remove('hidden');
    const type = sel.options[sel.selectedIndex].dataset.type || 'list'; // Default list if unknown
    const methods = dsMethods[type] || dsMethods['list'];

    const methodSel = document.getElementById('pv-ds-method');
    methodSel.innerHTML = methods.map(m => `<option value="${m.name}" data-arg="${m.arg}">${m.name}()</option>`).join('');
    updateMethodArgs();
};

window.updateMethodArgs = function () {
    const mSel = document.getElementById('pv-ds-method');
    const argInput = document.getElementById('pv-ds-method-arg');
    const needsArg = mSel.options[mSel.selectedIndex]?.dataset.arg === 'true';

    if (needsArg) {
        argInput.classList.remove('hidden');
    } else {
        argInput.classList.add('hidden');
    }
};

window.applyDSMethod = function () {
    const varName = document.getElementById('pv-ops-var').value;
    const method = document.getElementById('pv-ds-method').value;
    const argInput = document.getElementById('pv-ds-method-arg');
    const needsArg = !argInput.classList.contains('hidden');
    const arg = argInput.value.trim();

    let code = `${varName}.${method}()`;

    if (method === 'print') {
        code = `print(${varName})`;
    } else if (method === 'print_queue') {
        code = `print(list(${varName}.queue)) # View Queue`;
    } else if (needsArg) {
        if (!arg) return;
        code = `${varName}.${method}(${arg})`;
    }

    addLine({ code: code, type: 'func' }); // Type func for generic output
}

// Check Duplicate DS Names
function createDS() {
    const name = document.getElementById('pv-ds-name').value.trim();
    const type = document.getElementById('pv-ds-type').value;
    const rawVal = document.getElementById('pv-ds-val').value.trim();

    if (!name) { alert("Variable name required"); return; }

    // Check duplication
    const existing = pyvizState.lines.filter(l => (l.type === 'ds' || l.type === 'var') && l.meta?.name === name);
    if (existing.length > 0) {
        alert("A variable/structure with this name already exists!");
        return;
    }

    // Auto-detect numeric vs string values?
    let vals = rawVal ? rawVal.split(',').map(v => {
        v = v.trim();
        return (isNaN(parseFloat(v)) && !v.startsWith('"') && !v.startsWith("'")) ? `"${v}"` : v;
    }).join(', ') : "";

    let code = "";

    if (type === 'list') code = `${name} = [${vals}]`;
    else if (type === 'tuple') code = `${name} = (${vals})`;
    else if (type === 'stack') {
        if (!pyvizState.lines.some(l => l.code.includes('collections'))) {
            addImport("from collections import deque");
        }
        code = `${name} = deque([${vals}])`;
    }
    else if (type === 'queue') {
        if (!pyvizState.lines.some(l => l.code.includes('import queue'))) {
            addImport("import queue");
        }

        code = `${name} = queue.Queue()`;

        // Handle init values for queue
        if (vals && vals.length > 0) {
            addLine({
                code: code,
                type: 'ds',
                meta: { name: name, dsType: type }
            });

            const items = vals.split(',').map(v => v.trim());
            items.forEach(item => {
                if (item) {
                    let val = (isNaN(parseFloat(item)) && !item.startsWith('"') && !item.startsWith("'")) ? `"${item}"` : item;
                    addLine({ code: `${name}.put(${val})`, type: 'func' });
                }
            });

            if (document.getElementById('pv-ds-ops-selector')) renderDSOpsSelector();
            return;
        }
    }

    addLine({
        code: code,
        type: 'ds',
        meta: { name: name, dsType: type }
    });

    // Refresh ops if visible
    if (document.getElementById('pv-ds-ops-selector')) renderDSOpsSelector();
}

// Updated Stats Logic
// Comprehensive Stats Logic
function updateStats() {
    const rawLines = pyvizState.lines.map(l => l.code.trim());

    // Robust Counter Logic (State Machine)
    let forCount = 0;
    let whileCount = 0;
    let ifCount = 0;
    let elifCount = 0;
    let elseCount = 0;

    let multiStringState = null;

    rawLines.forEach(line => {
        let text = line; // Already trimmed
        if (!text) return;

        // Multi-line String logic
        if (multiStringState) {
            if (text.includes(multiStringState)) {
                // Found closing delimiter
                const parts = text.split(multiStringState);
                if (parts.length > 1) {
                    multiStringState = null;
                    text = parts.slice(1).join(multiStringState).trim();
                } else return; // Consumed
            } else return; // Consumed
        }

        if (!multiStringState) {
            // Check for start of multi-line
            if (text.includes('"""')) {
                const cnt = (text.match(/"""/g) || []).length;
                if (cnt % 2 !== 0) {
                    multiStringState = '"""';
                    text = text.split('"""')[0];
                } else {
                    text = text.replace(/""".*?"""/g, '');
                }
            } else if (text.includes("'''")) {
                const cnt = (text.match(/'''/g) || []).length;
                if (cnt % 2 !== 0) {
                    multiStringState = "'''";
                    text = text.split("'''")[0];
                } else {
                    text = text.replace(/'''.*?'''/g, '');
                }
            }
        }

        // Strip Basic Strings and Comments
        // 1. Strip escaped
        let safe = text.replace(/\\./g, '__');
        // 2. Strip single/double quoted strings
        safe = safe.replace(/"[^"]*"/g, '').replace(/'[^']*'/g, '');
        // 3. Strip comment
        if (safe.includes('#')) safe = safe.split('#')[0];

        safe = safe.trim();

        if (safe.startsWith('for ') || safe.startsWith('async for ')) forCount++;
        else if (safe.startsWith('while ')) whileCount++;
        else if (safe.startsWith('if ')) ifCount++;
        else if (safe.startsWith('elif ')) elifCount++;
        else if (safe.startsWith('else:')) elseCount++;
    });

    const stats = {
        'Lines': rawLines.length,
        'Variables': new Set(pyvizState.lines.filter(l => (l.type === 'var' || l.type === 'ds')).map(l => l.meta?.name).filter(n => n)).size,
        'Constants': 0, // Placeholder: Hard to track without AST (maybe literals in var assignments)
        'User Funcs': rawLines.filter(l => l.startsWith('def ')).length,
        'Built-in Funcs': 0, // Heuristic below
        'Func Args': 0, // Args passed in calls
        'Func Params': 0, // Params in definitions
        'Lists': 0,
        'Queues': 0,
        'Stacks': 0,
        'Other DS': 0,
        'Packages': rawLines.filter(l => l.startsWith('import ') || l.startsWith('from ')).length,
        'If': 0,
        'If-Else': 0,
        'If-Elif-Else': 0,
        'For Loops': forCount,
        'While Loops': whileCount,
        'Single Comments': rawLines.filter(l => l.startsWith('#')).length,
        'Multi Comments': rawLines.filter(l => l.startsWith('"""') || l.startsWith("'''")).length,
        'Inputs': 0,
        'Outputs (Print)': 0,
        'Conditions': 0, // Logical chunks
        'Relational Ops': 0,
        'Logical Ops': 0
    };

    // --- Detail Parsing ---

    // 1. Control Flow Chains (If / If-Else / If-Elif-Else)
    // We scan indent levels to find chains. This is tricky on a flat list but we approximate.
    // 'if' starts a chain. If we see 'else' at same indent, it's connected.
    // Simplifying: Count raw keywords first.
    // Counts already calculated above (ifCount, elifCount, elseCount)

    // Approximate Structure Counts based on counts (not perfect matching)
    // Every 'elif' implies it belongs to an If-Elif-Else or If-Elif structure.
    // Every 'else' implies If-Else or If-[Elif]-Else.
    // We can define:
    // Simple If = Total If - (those that have else/elif) -> Hard to knowing exact linkage.
    // Let's just report the Raw Counts of the structures as requested?
    // User asked "Total if", "total ifelse"... this implies structural identification.

    // Heuristic:
    // Iterate lines. If 'if' found at indent X, look ahead for 'elif'/'else' at indent X.
    for (let i = 0; i < pyvizState.lines.length; i++) {
        const line = pyvizState.lines[i];
        if (line.code.trim().startsWith('if ')) {
            const indent = line.indent;
            let hasElif = false;
            let hasElse = false;

            // Scan forward
            for (let j = i + 1; j < pyvizState.lines.length; j++) {
                const searchL = pyvizState.lines[j];
                if (searchL.indent < indent) break; // Block ended

                if (searchL.indent === indent) {
                    const sCode = searchL.code.trim();
                    if (sCode.startsWith('elif ')) hasElif = true;
                    if (sCode.startsWith('else:')) {
                        hasElse = true;
                        break; // else ends the chain
                    }
                }
            }

            if (hasElif || hasElse) {
                if (hasElif) stats['If-Elif-Else']++;
                else stats['If-Else']++;
            } else {
                stats['If']++;
            }
        }
    }

    // 2. Data Structures
    // ... [existing] ...

    // 2b. Packages (Split Standard vs Limit)
    const stdLibs = new Set(['math', 'random', 'datetime', 'time', 'sys', 'os', 're', 'string', 'collections', 'itertools', 'functools', 'statistics', 'json', 'builtins', 'platform', 'io', 'typing']);
    let packCount = 0; // Standard
    let libCount = 0;  // External

    rawLines.forEach(l => {
        if (l.startsWith('import ') || l.startsWith('from ')) {
            // Extract module name
            // import numpy as np -> numpy
            // from math import sqrt -> math
            let mod = '';
            if (l.startsWith('import ')) {
                mod = l.split(' ')[1];
            } else {
                mod = l.split(' ')[1];
            }
            // Clean 'import numpy as ...' -> numpy
            mod = mod.split('.')[0].trim();

            if (stdLibs.has(mod)) packCount++;
            else libCount++;
        }
    });
    stats['Pack/Lib'] = `${packCount} / ${libCount}`;

    // 3. Operators & I/O
    rawLines.forEach(l => {
        // I/O
        if (l.includes('input(')) stats['Inputs']++;
        if (l.startsWith('print(')) stats['Outputs (Print)']++;

        // Built-ins (approximate calls)
        const callMatch = l.match(/\b([a-zA-Z_]\w*)\(/g);
        if (callMatch) {
            callMatch.forEach(m => {
                const name = m.slice(0, -1);
                // Simple list of common builtins or non-def calls
                if (!l.startsWith('def ') && !l.startsWith('class ')) {
                    stats['Built-in Funcs']++; // We count all calls as built-in usage for now unless matched against user defs
                    // Ideally we filter against known Defs
                }
            });
        }

        // Args (commas in calls)
        if (l.includes('(') && !l.startsWith('def ') && !l.startsWith('class ')) {
            // Count commas inside parens roughly
            const inside = l.match(/\((.*)\)/);
            if (inside && inside[1].trim()) {
                stats['Func Args'] += inside[1].split(',').length;
            }
        }

        // Params (commas in defs)
        if (l.startsWith('def ')) {
            const inside = l.match(/\((.*)\)/);
            if (inside && inside[1].trim()) {
                stats['Func Params'] += inside[1].split(',').length;
            }
        }

        // Operators
        const relOps = (l.match(/(==|!=|<=|>=|<|>)/g) || []).length;
        // Ignore < > in HTML/XML strings? Unlikely in basic python.
        // Also ignore arrows ->
        if (!l.includes('->')) stats['Relational Ops'] += relOps;

        const logOps = (l.match(/\b(and|or|not)\b/g) || []).length;
        if (!l.startsWith('#') && !l.startsWith('import')) {
            stats['Logical Ops'] += logOps;
        }

        // Conditions (Total boolean expressions? Approximation: RelOps + LogOps + Boolean literals)
        // User asked for "Total conditions". 
        // Maybe count `if`, `elif`, `while` lines?
        if (l.startsWith('if ') || l.startsWith('elif ') || l.startsWith('while ')) {
            stats['Conditions']++;
        }
    });

    // Sub-adjust User Func Calls
    // stats['Built-in Funcs'] currently counts ALL calls. We should subtract User Func Calls.
    // Hard to trace exactly without symbol table.

    // Render
    const grid = document.getElementById('pyviz-stats-grid');
    if (!grid) return;

    // Sort keys based on user request order approximate
    const order = [
        'Lines', 'Variables', 'Constants',
        'Func Args', 'Func Params', 'User Funcs',
        'Built-in Funcs', 'Lists', 'Queues',
        'Stacks', 'Other DS', 'Pack/Lib',
        'If', 'If-Else', 'If-Elif-Else',
        'For Loops', 'While Loops', 'Single Comments',
        'Multi Comments', 'Inputs', 'Outputs (Print)',
        'Conditions', 'Relational Ops', 'Logical Ops'
    ];

    let html = '';
    order.forEach(key => {
        let val = stats[key] || 0;
        html += `
            <div class="flex flex-col bg-slate-800/50 rounded border border-slate-700/50 p-2">
                <span class="text-[10px] uppercase font-bold text-slate-300 truncate" title="${key}">${key}</span>
                <span class="text-lg font-mono font-bold text-blue-400 text-right">${val}</span>
            </div>
        `;
    });

    grid.innerHTML = html;
}

function changeFontSize(delta) {
    const sizes = ['text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl', 'text-2xl', 'text-3xl', 'text-4xl'];
    let currentIdx = sizes.indexOf(pyvizState.fontSize || 'text-lg');
    let newIdx = Math.max(0, Math.min(sizes.length - 1, currentIdx + delta));
    pyvizState.fontSize = sizes[newIdx];
    renderPyViz();
}

function logAction(msg, context = {}) {
    if (!pyvizDom.logList) return;

    // Get line number from pyvizState
    const lineNum = pyvizState.lines.length;

    // Generate educational message based on context
    let educationalMsg = generateEducationalMessage(msg, context);

    const li = document.createElement('li');
    li.className = 'py-1 border-b border-slate-800/50 text-xs';
    li.innerHTML = `
        <span class="text-blue-400 font-bold mr-1">L${lineNum}</span>
        <span class="text-slate-600 text-[10px]">${new Date().toLocaleTimeString()}</span>
        <span class="text-slate-300 ml-2">${educationalMsg}</span>
    `;

    if (pyvizDom.logList.firstChild) {
        pyvizDom.logList.insertBefore(li, pyvizDom.logList.firstChild);
    } else {
        pyvizDom.logList.appendChild(li);
    }

    // Limit log entries to prevent memory bloat
    while (pyvizDom.logList.children.length > 100) {
        pyvizDom.logList.removeChild(pyvizDom.logList.lastChild);
    }
}

function generateEducationalMessage(action, context) {
    const code = (context.code || '').trim();
    const type = context.type || '';

    if (!code) return `✏️ ${action}`;

    // === DATA STRUCTURES ===

    // Stack operations
    if (code.includes('.push(') || code.includes('.append(')) {
        const match = code.match(/(\w+)\.(push|append)\((.+)\)/);
        if (match) {
            return `📥 <b>Stack/List Push</b>: Added element <code>${match[3]}</code> to <code>${match[1]}</code>`;
        }
    }

    if (code.includes('.pop(')) {
        const match = code.match(/(\w+)\.pop\(\)/);
        if (match) {
            return `📤 <b>Stack/List Pop</b>: Removed top element from <code>${match[1]}</code>`;
        }
    }

    // Queue operations
    if (code.includes('deque(')) {
        const match = code.match(/(\w+)\s*=\s*deque\((.+)?\)/);
        if (match) {
            return `📋 <b>Queue Created</b>: Initialized queue <code>${match[1]}</code> using deque`;
        }
    }

    if (code.includes('.popleft(')) {
        const match = code.match(/(\w+)\.popleft\(\)/);
        if (match) {
            return `📤 <b>Queue Dequeue</b>: Removed front element from <code>${match[1]}</code>`;
        }
    }

    if (code.includes('.appendleft(')) {
        const match = code.match(/(\w+)\.appendleft\((.+)\)/);
        if (match) {
            return `📥 <b>Queue Insert Front</b>: Added <code>${match[2]}</code> to front of <code>${match[1]}</code>`;
        }
    }

    // List creation
    if (code.match(/^\w+\s*=\s*\[/)) {
        const match = code.match(/^(\w+)\s*=\s*\[(.*)?\]/);
        if (match) {
            const items = match[2] ? match[2].split(',').length : 0;
            return `📋 <b>List Created</b>: <code>${match[1]}</code> initialized with ${items} element(s)`;
        }
    }

    // Tuple creation
    if (code.match(/^\w+\s*=\s*\(/)) {
        const match = code.match(/^(\w+)\s*=\s*\((.*)?\)/);
        if (match) {
            const items = match[2] ? match[2].split(',').length : 0;
            return `🔒 <b>Tuple Created</b>: <code>${match[1]}</code> initialized with ${items} immutable element(s)`;
        }
    }

    // Dictionary creation
    if (code.match(/^\w+\s*=\s*\{/)) {
        const match = code.match(/^(\w+)\s*=\s*\{(.*)?\}/);
        if (match) {
            return `📖 <b>Dictionary Created</b>: <code>${match[1]}</code> initialized with key-value pairs`;
        }
    }

    // Set creation
    if (code.includes('set(')) {
        const match = code.match(/(\w+)\s*=\s*set\(/);
        if (match) {
            return `🔢 <b>Set Created</b>: <code>${match[1]}</code> initialized (unique elements only)`;
        }
    }

    // === FUNCTIONS ===

    // User-defined function definition
    if (code.startsWith('def ')) {
        const match = code.match(/^def\s+(\w+)\s*\(([^)]*)\)/);
        if (match) {
            const funcName = match[1];
            const params = match[2] ? match[2].split(',').map(p => p.trim()).filter(p => p) : [];
            if (params.length > 0) {
                return `🔧 <b>Function Defined</b>: <code>${funcName}(${params.join(', ')})</code> - reusable code block with ${params.length} parameter(s)`;
            }
            return `🔧 <b>Function Defined</b>: <code>${funcName}()</code> - reusable code block (no parameters)`;
        }
    }

    // Built-in function calls (Check this AFTER print/input to avoid false matches)
    // defined below

    // User function call (matches functionName() pattern)
    if (code.match(/^(\w+)\(.*\)$/) && !code.startsWith('print(') && !code.startsWith('input(')) {
        const match = code.match(/^(\w+)\(/);
        if (match) {
            return `📞 <b>Function Call</b>: Executed <code>${match[1]}()</code>`;
        }
    }

    // Return statement
    if (code.startsWith('return')) {
        const val = code.replace('return', '').trim();
        if (val) {
            return `↩️ <b>Return Statement</b>: Function returns <code>${val}</code>`;
        }
        return `↩️ <b>Return Statement</b>: Function returns (no value)`;
    }

    // === INPUT/OUTPUT ===

    // Print statement
    if (code.startsWith('print(')) {
        const content = code.match(/print\((.+)\)/);
        if (content) {
            return `🖨️ <b>Print Output</b>: Displays <code>${content[1].substring(0, 30)}${content[1].length > 30 ? '...' : ''}</code>`;
        }
        return `🖨️ <b>Print Output</b>: Displays output to console`;
    }

    // Input statement
    if (code.includes('input(')) {
        const match = code.match(/(\w+)\s*=.*input\((.+)?\)/);
        if (match) {
            return `⌨️ <b>User Input</b>: Stores user input in <code>${match[1]}</code>`;
        }
        return `⌨️ <b>User Input</b>: Waits for user to enter data`;
    }

    // Built-in function calls (Moved here and improved regex)
    const builtinFuncs = ['len', 'range', 'int', 'str', 'float', 'bool', 'type', 'abs', 'sum', 'min', 'max', 'sorted', 'reversed', 'enumerate', 'zip', 'map', 'filter', 'open'];
    for (const fn of builtinFuncs) {
        // Use word boundary to avoid partial matches (e.g. matching 'int' inside 'print')
        const regex = new RegExp(`\\b${fn}\\(`);
        if (regex.test(code)) {
            return `⚙️ <b>Built-in Function</b>: Called <code>${fn}()</code>`;
        }
    }

    // === CONTROL FLOW ===

    // For loop
    if (code.startsWith('for ')) {
        const match = code.match(/^for\s+(\w+)\s+in\s+(.+):/);
        if (match) {
            return `🔄 <b>For Loop</b>: Iterates <code>${match[1]}</code> over <code>${match[2]}</code>`;
        }
    }

    // While loop
    if (code.startsWith('while ')) {
        const cond = code.replace('while ', '').replace(':', '').trim();
        return `🔄 <b>While Loop</b>: Repeats while <code>${cond}</code>`;
    }

    // If statement
    if (code.startsWith('if ')) {
        const cond = code.replace('if ', '').replace(':', '').trim();
        return `❓ <b>If Condition</b>: Checks if <code>${cond}</code>`;
    }

    // Elif statement
    if (code.startsWith('elif ')) {
        const cond = code.replace('elif ', '').replace(':', '').trim();
        return `❓ <b>Elif Condition</b>: Checks alternative <code>${cond}</code>`;
    }

    // Else statement
    if (code.startsWith('else:')) {
        return `❓ <b>Else Block</b>: Executes when all conditions are false`;
    }

    // Break/Continue
    if (code === 'break') {
        return `⏹️ <b>Break</b>: Exits the current loop immediately`;
    }
    if (code === 'continue') {
        return `⏭️ <b>Continue</b>: Skips to next loop iteration`;
    }
    if (code === 'pass') {
        return `⏸️ <b>Pass</b>: Placeholder (does nothing)`;
    }

    // === IMPORTS ===

    if (code.startsWith('import ')) {
        const mod = code.replace('import ', '').split(' as ')[0].trim();
        return `📚 <b>Import</b>: Loaded library <code>${mod}</code>`;
    }

    if (code.startsWith('from ')) {
        const match = code.match(/from\s+(\w+)\s+import\s+(.+)/);
        if (match) {
            return `📚 <b>Import From</b>: Loaded <code>${match[2]}</code> from <code>${match[1]}</code>`;
        }
    }

    // === COMMENTS ===

    if (code.startsWith('#')) {
        return `💬 <b>Comment</b>: ${code.substring(1, 40).trim()}${code.length > 40 ? '...' : ''}`;
    }

    if (code.includes('"""') || code.includes("'''")) {
        return `💬 <b>Docstring/Multi-line Comment</b>: Documentation added`;
    }

    // === VARIABLES ===

    // Variable with type conversion
    if (code.match(/^\w+\s*=\s*(int|str|float|bool)\(/)) {
        const match = code.match(/^(\w+)\s*=\s*(int|str|float|bool)\((.+)\)/);
        if (match) {
            return `📦 <b>Variable (Type Cast)</b>: <code>${match[1]}</code> = ${match[2]}(${match[3]})`;
        }
    }

    // Simple variable assignment
    if (code.match(/^\w+\s*=\s*.+/)) {
        const match = code.match(/^(\w+)\s*=\s*(.+)$/);
        if (match) {
            const varName = match[1];
            const value = match[2].trim();

            // Number
            if (!isNaN(parseFloat(value)) && isFinite(value)) {
                return `📦 <b>Variable</b>: <code>${varName}</code> = ${value} (number)`;
            }
            // String
            if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
                return `📦 <b>Variable</b>: <code>${varName}</code> = ${value.substring(0, 20)}${value.length > 20 ? '...' : ''} (string)`;
            }
            // Boolean
            if (value === 'True' || value === 'False') {
                return `📦 <b>Variable</b>: <code>${varName}</code> = ${value} (boolean)`;
            }
            // None
            if (value === 'None') {
                return `📦 <b>Variable</b>: <code>${varName}</code> = None (null value)`;
            }
            // Expression/other
            return `📦 <b>Variable</b>: <code>${varName}</code> = <code>${value.substring(0, 30)}${value.length > 30 ? '...' : ''}</code>`;
        }
    }

    // === CLASS ===

    if (code.startsWith('class ')) {
        const match = code.match(/^class\s+(\w+)/);
        if (match) {
            return `🏗️ <b>Class Defined</b>: <code>${match[1]}</code> - blueprint for objects`;
        }
    }

    // === TRY/EXCEPT ===

    if (code.startsWith('try:')) {
        return `🛡️ <b>Try Block</b>: Error handling started`;
    }
    if (code.startsWith('except')) {
        return `🛡️ <b>Except Block</b>: Catches and handles errors`;
    }
    if (code.startsWith('finally:')) {
        return `🛡️ <b>Finally Block</b>: Always executes (cleanup)`;
    }
    if (code.startsWith('raise')) {
        return `⚠️ <b>Raise</b>: Throws an exception`;
    }

    // === DEFAULT - Try to detect method calls ===

    // Method call patterns: obj.method()
    const methodMatch = code.match(/(\w+)\.(\w+)\(([^)]*)\)/);
    if (methodMatch) {
        const objName = methodMatch[1];
        const methodName = methodMatch[2];
        const args = methodMatch[3];

        // Common method messages
        const methodMessages = {
            'put': `📥 <b>put() method</b>: Added ${args || 'value'} to <code>${objName}</code>`,
            'get': `📤 <b>get() method</b>: Retrieved value from <code>${objName}</code>`,
            'insert': `📥 <b>insert() method</b>: Inserted at position in <code>${objName}</code>`,
            'remove': `📤 <b>remove() method</b>: Removed ${args || 'item'} from <code>${objName}</code>`,
            'clear': `🗑️ <b>clear() method</b>: Emptied <code>${objName}</code>`,
            'sort': `📊 <b>sort() method</b>: Sorted <code>${objName}</code>`,
            'reverse': `🔄 <b>reverse() method</b>: Reversed <code>${objName}</code>`,
            'extend': `📥 <b>extend() method</b>: Added multiple items to <code>${objName}</code>`,
            'update': `🔄 <b>update() method</b>: Updated <code>${objName}</code>`,
            'add': `📥 <b>add() method</b>: Added ${args || 'item'} to <code>${objName}</code>`,
            'discard': `📤 <b>discard() method</b>: Removed ${args || 'item'} from <code>${objName}</code>`,
            'copy': `📋 <b>copy() method</b>: Created copy of <code>${objName}</code>`,
            'keys': `🔑 <b>keys() method</b>: Got keys from <code>${objName}</code>`,
            'values': `📦 <b>values() method</b>: Got values from <code>${objName}</code>`,
            'items': `📋 <b>items() method</b>: Got key-value pairs from <code>${objName}</code>`,
            'find': `🔍 <b>find() method</b>: Searched in <code>${objName}</code>`,
            'index': `🔍 <b>index() method</b>: Found position in <code>${objName}</code>`,
            'count': `🔢 <b>count() method</b>: Counted occurrences in <code>${objName}</code>`,
            'join': `🔗 <b>join() method</b>: Joined elements of <code>${objName}</code>`,
            'split': `✂️ <b>split() method</b>: Split string into parts`,
            'strip': `✂️ <b>strip() method</b>: Removed whitespace from string`,
            'lower': `🔡 <b>lower() method</b>: Converted to lowercase`,
            'upper': `🔠 <b>upper() method</b>: Converted to uppercase`,
            'replace': `🔄 <b>replace() method</b>: Replaced text in string`,
            'format': `📝 <b>format() method</b>: Formatted string`,
            'read': `📖 <b>read() method</b>: Read from file <code>${objName}</code>`,
            'write': `📝 <b>write() method</b>: Wrote to file <code>${objName}</code>`,
            'close': `🚪 <b>close() method</b>: Closed <code>${objName}</code>`,
        };

        if (methodMessages[methodName]) {
            return methodMessages[methodName];
        }

        // Generic method call
        return `⚙️ <b>${methodName}() method</b>: Called on <code>${objName}</code>`;
    }

    // If nothing matched, give a simple statement added message
    return `✏️ <b>Statement Added</b>`;
}


// Logic Check (Replaces AI Mock)
function runAICheck() {
    if (pyvizDom.aiMsg) {
        pyvizDom.aiMsg.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-blue-500 mr-2"></i> Checking logic...';
    }

    setTimeout(() => {
        const issues = [];

        // 1. Check Colon endings & 2. Parentheses match
        // We must respect multi-line strings (""" or ''')
        let inMultiString = null; // null, '"""', or "'''"

        pyvizState.lines.forEach((l, i) => {
            let code = l.code;

            // Handle Multi-line state toggling
            // Naive split by quotes to count. 
            // Better: Scan string for triggers.

            // Check for triple quotes
            // We just need to know if the "meaningful code" part is valid.
            // If the line is FULLY inside a multi-string, we skip logic checks.
            // If the line STARTS or ENDS a multi-string, we only check the code part (if any).

            // Simplification: logic checks are skipped if we are "in" multi-string at the START 
            // OR if the line contains a state change. Python syntax is complex, but for this checker:
            // If line contains """ or ''', we assume it's complex and might skip strict checks 
            // OR we try to mask string contents.

            const triDouble = '"""';
            const triSingle = "'''";

            // Count occurrences to see if state changes
            // Note: This simple check fails on `s = """` + `"""` (2 on same line).
            // Let's masking strategy: Replace string contents with spaces.

            // Actually, for the specific error "Missing colon", we just need to ensure we don't 
            // flag words inside the comment.

            // Let's implement a line-by-line state machine.
            let effectiveCode = code;

            // Check state at start of line
            let startState = inMultiString;

            // Simple toggle logic (naive but better than nothing)
            const doubles = (code.match(/"""/g) || []).length;
            const singles = (code.match(/'''/g) || []).length;

            // Update state (assuming no nesting of different types and simple structure)
            if (inMultiString === '"""') {
                if (doubles % 2 !== 0) inMultiString = null; // Closed
            } else if (inMultiString === "'''") {
                if (singles % 2 !== 0) inMultiString = null; // Closed
            } else {
                // Not in string
                if (doubles % 2 !== 0) inMultiString = '"""';
                else if (singles % 2 !== 0) inMultiString = "'''";
            }

            // If we were inside a string at start, or are inside at end, or tokens changed, 
            // it's safer to skip strict keyword checks on this line to avoid false positives.
            if (startState || inMultiString || doubles > 0 || singles > 0) {
                return; // Skip checks for this line
            }

            const stripped = code.split('#')[0].trim();
            if (!stripped) return;

            // Colon Check
            if (/^(if|else|elif|for|while|def|class)\b/.test(stripped)) {
                if (!stripped.endsWith(':')) {
                    issues.push(`Line ${i + 1}: Missing colon ':' at the end of statement.`);
                }
            }

            // Paren Check
            const open = (stripped.match(/\(/g) || []).length;
            const close = (stripped.match(/\)/g) || []).length;
            if (open !== close) {
                issues.push(`Line ${i + 1}: Mismatched parentheses (${open} open, ${close} closed).`);
            }
        });

        // 3. Indentation Check (Skip checks if in multi-string)
        // Re-using the state logic is hard unless we combine loops.
        // Let's combine indentation check into the loop above? 
        // Or just simplify: Indentation check error "Expected block" is rare if colon check passes.
        // Let's fix the Indentation loop separately using same state logic.

        let indentState = null;
        for (let i = 0; i < pyvizState.lines.length; i++) {
            const line = pyvizState.lines[i];
            const code = line.code;

            // Update State
            const doubles = (code.match(/"""/g) || []).length;
            const singles = (code.match(/'''/g) || []).length;
            let skipLine = false;

            if (indentState === '"""') {
                if (doubles % 2 !== 0) indentState = null;
                skipLine = true;
            } else if (indentState === "'''") {
                if (singles % 2 !== 0) indentState = null;
                skipLine = true;
            } else {
                if (doubles % 2 !== 0) indentState = '"""';
                else if (singles % 2 !== 0) indentState = "'''";
                if (doubles > 0 || singles > 0) skipLine = true;
            }

            if (skipLine) continue;

            const clean = line.code.split('#')[0].trim();
            if (clean.endsWith(':')) {
                // Next non-empty/non-comment line MUST have deeper indent
                let j = i + 1;
                while (j < pyvizState.lines.length) {
                    const nextL = pyvizState.lines[j];
                    const nextClean = nextL.code.split('#')[0].trim();
                    if (nextClean) {
                        if (nextL.indent <= line.indent) {
                            issues.push(`Line ${j + 1}: Expected an indented block after statement on Line ${i + 1}.`);
                        }
                        break;
                    }
                    j++;
                }
            }
        }

        // 4. Import check
        const hasQueues = pyvizState.lines.some(l => l.code.includes('queue.Queue'));
        const hasQImport = pyvizState.lines.some(l => l.code.includes('import queue'));
        if (hasQueues && !hasQImport) {
            issues.push("Missing 'import queue' for Queue usage.");
        }

        if (pyvizDom.aiMsg) {
            if (issues.length === 0) {
                pyvizDom.aiMsg.innerHTML = `<span class="text-green-400 font-bold"><i class="fa-solid fa-check mr-1"></i> No obvious syntax errors found.</span>`;
            } else {
                pyvizDom.aiMsg.innerHTML = `<span class="text-red-400 font-bold">Issues Found:</span><br>${issues.slice(0, 2).join('<br>')}${issues.length > 2 ? '<br>...' : ''}`;
            }

            if (window.gsap) {
                gsap.from(pyvizDom.aiMsg, { opacity: 0, y: 10, duration: 0.5 });
            }
        }
    }, 800);
}

// --- Global Action Functions (Exposed for onclick) ---

// [Legacy functions removed to prevent conflict with end-of-file overrides]

function highlightLineTokenized(codeStr) {
    if (!codeStr) return '';
    const keywords = new Set(['def', 'class', 'if', 'else', 'elif', 'for', 'while', 'return', 'import', 'from', 'as', 'break', 'continue', 'pass', 'and', 'or', 'not', 'in', 'is']);
    const builtins = new Set(['print', 'input', 'len', 'range', 'int', 'str', 'float', 'list', 'dict', 'set', 'tuple', 'deque', 'type', 'enumerate', 'zip', 'sum', 'min', 'max', 'round', 'abs']);

    // Basic tokenizer regex
    const tokens = codeStr.split(/("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*'|#.*$|\b\d+(?:\.\d+)?\b|[a-zA-Z_]\w*|[^\s\w])/g);

    return tokens.map(token => {
        if (!token) return '';

        // String literal
        if (token.startsWith('"') || token.startsWith("'")) {
            return `<span class="text-green-400">${escapeHtml(token)}</span>`;
        }
        // Comment
        if (token.startsWith('#')) {
            return `<span class="text-slate-500 italic">${escapeHtml(token)}</span>`;
        }
        // Number
        if (/^\d/.test(token)) {
            return `<span class="text-pink-400">${token}</span>`;
        }
        // Word check
        if (/^[a-zA-Z_]/.test(token)) {
            if (keywords.has(token)) return `<span class="text-orange-400 font-bold">${token}</span>`;
            if (builtins.has(token)) return `<span class="text-blue-400">${token}</span>`;
            return escapeHtml(token);
        }
        // Escape generic
        return escapeHtml(token);
    }).join('');
}

// --- Dry Run (Double Run) Logic ---

function renderDryRunBuilder(container) {
    if (!pyvizState.dryRunVars) pyvizState.dryRunVars = [];

    container.innerHTML = `
        <div class="space-y-4 h-full flex flex-col">
            <!-- Configuration -->
            <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3 shrink-0">
                <h4 class="text-xs font-bold text-teal-400 uppercase"><i class="fa-solid fa-table mr-1"></i> Dry Run Config</h4>
                
                <p class="text-[10px] text-slate-400">Select variables to trace during execution.</p>
                
                <div class="flex gap-2">
                    <select id="pv-dry-run-select" class="flex-1 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                        <!-- Populated dynamically -->
                    </select>
                    <button onclick="addDryRunVar()" class="px-3 py-1 bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold rounded">Add</button>
                </div>

                <!-- Selected Vars List -->
                <div id="pv-dry-run-list" class="flex flex-wrap gap-2 min-h-[30px] p-2 bg-slate-900 rounded border border-slate-700">
                    <!-- Badges -->
                </div>
            </div>

            <!-- Table Display -->
            <div class="flex-1 overflow-hidden flex flex-col bg-slate-800 rounded border border-slate-700">
                <div class="p-2 border-b border-slate-700 bg-slate-900">
                     <h4 class="text-xs font-bold text-slate-300 uppercase">Execution Trace</h4>
                </div>
                
                <div class="overflow-auto custom-scrollbar flex-1 relative w-full">
                    <table class="min-w-full text-xs text-left border-separate border-spacing-0" id="pv-dry-run-table">
                        <thead class="text-slate-500 bg-slate-900/50 sticky top-0 z-10">
                            <tr>
                                <th class="p-2 border-b border-slate-700 font-mono w-12 text-center">#</th>
                                <!-- Dynamic Headers -->
                            </tr>
                        </thead>
                        <tbody class="text-slate-300 font-mono whitespace-nowrap">
                            <!-- Rows -->
                            <tr><td class="p-4 text-center text-slate-600 italic" colspan="100%">Run code to see trace...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;

    refreshDryRunVarsList();
    refreshDryRunBadges();
}

function refreshDryRunVarsList() {
    const select = document.getElementById('pv-dry-run-select');
    if (!select) return;

    // Scan code for variables. 
    // Heuristic: Assignments "x =" or just defined Vars in state
    const candidates = new Set();

    // 1. From Vars Tab State (if user created them via UI)
    pyvizState.lines.forEach(l => {
        if (l.type === 'var' || l.type === 'ds') {
            if (l.meta && l.meta.name) candidates.add(l.meta.name);
        }
    });

    // 2. Scan code lines for assignments
    pyvizState.lines.forEach(l => {
        const codeline = l.code.split('#')[0]; // Ignore comments
        if (codeline.includes('=')) {
            const parts = codeline.split('=');
            let lhs = parts[0].trim();
            // Basic identifier check (ignore complex LHS like x[0] for now)
            if (/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(lhs)) {
                candidates.add(lhs);
            }
            // Tuple unpacking? "a, b = 1, 2" 
            if (lhs.includes(',')) {
                lhs.split(',').forEach(v => {
                    const clean = v.trim();
                    if (/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(clean)) candidates.add(clean);
                });
            }
        }
    });

    const selected = new Set(pyvizState.dryRunVars || []);

    // Explicitly exclude print kwargs that get picked up by naive parser
    const exclude = new Set(['end', 'sep', 'file', 'flush']);

    const options = Array.from(candidates)
        .filter(c => !selected.has(c) && !exclude.has(c))
        .sort();

    if (options.length === 0) {
        select.innerHTML = '<option value="">(No valid variables found)</option>';
    } else {
        select.innerHTML = options.map(o => `<option value="${o}">${o}</option>`).join('');
    }
}

function addDryRunVar() {
    const select = document.getElementById('pv-dry-run-select');
    const val = select.value;
    if (!val) return;

    if (!pyvizState.dryRunVars) pyvizState.dryRunVars = [];
    if (!pyvizState.dryRunVars.includes(val)) {
        pyvizState.dryRunVars.push(val);
        refreshDryRunBadges();
        refreshDryRunVarsList();
        renderDryRunTableHeader();
    }
}

function removeDryRunVar(name) {
    if (!pyvizState.dryRunVars) return;
    pyvizState.dryRunVars = pyvizState.dryRunVars.filter(v => v !== name);
    refreshDryRunBadges();
    refreshDryRunVarsList();
    renderDryRunTableHeader();
}

function refreshDryRunBadges() {
    const container = document.getElementById('pv-dry-run-list');
    if (!container) return;

    if (!pyvizState.dryRunVars || pyvizState.dryRunVars.length === 0) {
        container.innerHTML = '<span class="text-[10px] text-slate-500 italic">No variables tracked.</span>';
        return;
    }

    container.innerHTML = pyvizState.dryRunVars.map(v => `
        <span class="inline-flex items-center px-2 py-1 rounded bg-teal-900/50 border border-teal-700/50 text-teal-300 text-[10px]">
            ${v}
            <button onclick="removeDryRunVar('${v}')" class="ml-1 hover:text-white"><i class="fa-solid fa-times"></i></button>
        </span>
    `).join('');
}

function renderDryRunTableHeader() {
    const table = document.getElementById('pv-dry-run-table');
    if (!table) return;

    const theadRow = table.querySelector('thead tr');
    if (!theadRow) return;

    // Preserve first column (#)
    // Updated Style: text-sm, text-slate-300 (Light Gray for Variable Names in Header)
    let html = '<th class="p-2 border-b border-slate-700 bg-slate-900/90 font-mono w-12 text-center sticky top-0 text-sm text-slate-400">#</th>';

    if (pyvizState.dryRunVars) {
        pyvizState.dryRunVars.forEach(v => {
            html += `<th class="p-2 border-b border-slate-700 bg-slate-900/90 font-mono min-w-[80px] whitespace-nowrap sticky top-0 text-sm text-slate-300 font-bold">${v}</th>`;
        });
    }

    theadRow.innerHTML = html;
}

window.clearDryRunTable = function () {
    const table = document.getElementById('pv-dry-run-table');
    if (!table) return;
    const body = table.querySelector('tbody');
    // Clear content
    body.innerHTML = '';

    // Force re-render header to ensure it matches current vars
    if (window.renderDryRunTableHeader) window.renderDryRunTableHeader();
}

window.updateDryRunTable = function (lineno, locals) {
    const table = document.getElementById('pv-dry-run-table');
    if (!table) return;
    const body = table.querySelector('tbody');

    const vars = pyvizState.dryRunVars || [];
    if (vars.length === 0) return;

    const row = document.createElement('tr');
    row.className = "hover:bg-slate-700/50 transition-colors border-b border-slate-800/50";

    // Row Number: text-sm, text-orange-400 (Light Orange)
    let html = `<td class="p-2 border-r border-slate-800 text-center text-orange-400 font-bold text-sm">${lineno}</td>`;

    vars.forEach(v => {
        let rawVal = locals[v];
        let displayVal;

        if (rawVal === undefined) {
            displayVal = '<span class="text-blue-400 font-bold text-sm">-</span>';
            rawVal = "-";
        } else {
            // Basic Formatting
            if (typeof rawVal === 'string') displayVal = `<span class="text-green-400">'${escapeHtml(rawVal)}'</span>`;
            else if (typeof rawVal === 'number') displayVal = `<span class="text-pink-400">${rawVal}</span>`;
            else if (typeof rawVal === 'boolean') displayVal = `<span class="text-orange-400">${rawVal}</span>`;
            else displayVal = escapeHtml(String(rawVal));
        }

        html += `<td class="p-2 border-r border-slate-800 whitespace-pre overflow-hidden text-ellipsis max-w-[150px]" title="${String(rawVal).replace(/"/g, '&quot;')}">${displayVal}</td>`;
    });

    row.innerHTML = html;
    body.appendChild(row);

    // Auto scroll
    row.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

// ==========================================
// FIXES: Toast Notifications & Robust Actions
// ==========================================

window.showToast = function (msg, type = 'info') {
    const container = document.getElementById('pv-toast-container') || createToastContainer();
    const toast = document.createElement('div');

    let colors = "border-blue-500/50 bg-slate-800 text-blue-400";
    let icon = "fa-circle-info";
    if (type === 'success') { colors = "border-green-500/50 bg-slate-800 text-green-400"; icon = "fa-check-circle"; }
    else if (type === 'error') { colors = "border-red-500/50 bg-slate-800 text-red-400"; icon = "fa-circle-exclamation"; }

    toast.className = `flex items-center gap-3 p-4 rounded-lg border shadow-2xl backdrop-blur-md transform transition-all duration-300 animate-slide-in-right ${colors} mb-3 min-w-[300px] z-[9999]`;
    toast.innerHTML = `
        <i class="fa-solid ${icon} text-lg"></i>
        <div class="flex-1 text-sm font-semibold text-slate-200">${msg}</div>
    `;

    container.appendChild(toast);

    // Animate In
    requestAnimationFrame(() => {
        toast.style.transform = "translateX(0)";
        toast.style.opacity = "1";
    });

    // Remove after 3s
    setTimeout(() => {
        toast.style.transform = "translateX(100%)";
        toast.style.opacity = "0";
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function createToastContainer() {
    const div = document.createElement('div');
    div.id = 'pv-toast-container';
    div.className = "fixed bottom-5 right-5 z-[9999] flex flex-col items-end pointer-events-none";
    div.style.pointerEvents = "none";
    // Children have pointer events by default
    document.body.appendChild(div);
    return div;
}

window.showConfirm = function (msg, onYes) {
    const div = document.createElement('div');
    div.className = "fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-sm animate-fade-in";
    div.innerHTML = `
        <div class="bg-slate-900 border border-slate-700/50 p-6 rounded-xl shadow-2xl max-w-sm w-full transform scale-100 ring-1 ring-white/10">
            <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-yellow-500"></i> Confirmation
            </h3>
            <p class="text-slate-300 mb-6 text-sm leading-relaxed">${msg}</p>
            <div class="flex justify-end gap-3">
                <button id="confirm-no" class="px-4 py-2 text-slate-400 hover:text-white text-sm font-bold bg-slate-800 hover:bg-slate-700 rounded transition-colors border border-slate-700">Cancel</button>
                <button id="confirm-yes" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white text-sm font-bold rounded shadow-lg transition-colors border border-red-500/50">Yes, Proceed</button>
            </div>
        </div>
    `;
    document.body.appendChild(div);

    const cleanup = () => div.remove();
    div.querySelector('#confirm-no').onclick = cleanup;
    div.querySelector('#confirm-yes').onclick = () => { cleanup(); onYes(); };
}

// Override Native Alert with Toast
window.originalAlert = window.alert;
window.alert = function (msg) {
    showToast(msg, 'info');
    console.log("[PyViz Alert Overridden]:", msg);
};

// Redefine Actions with New UI
window.clearPyViz = function () {
    showConfirm("Are you sure you want to clear all code?", () => {
        pyvizState.lines = [];
        pyvizState.nextId = 1;
        renderPyViz();
        updateStats();
        logAction("Cleared Playground");
        showToast("Playground cleared successfully.", "success");
    });
};

// --- Experimental Editor Mode (Phase 9) ---

// Wrapper to track active category
if (window.loadToolbox && !window.originalLoadToolbox) {
    window.originalLoadToolbox = window.loadToolbox;
    window.loadToolbox = function (cat) {
        pyvizState.activeCategory = cat;
        window.originalLoadToolbox(cat);
    }
}

pyvizState.isEditorMode = false;

window.toggleEditorMode = function () {
    const btn = document.getElementById('pyviz-btn-editor');
    const icon = document.getElementById('pv-editor-icon');
    const area = pyvizDom.codeArea;

    if (!pyvizState.isEditorMode) {
        // Switch TO Editor Mode
        pyvizState.isEditorMode = true;

        // Notify RuntimeInspector
        if (window.RuntimeInspector) window.RuntimeInspector.setEditorMode(true);

        // Convert blocks to text
        const codeText = pyvizState.lines.map(l => '    '.repeat(l.indent) + l.code).join('\n');

        // Reset any wrap-related styles on the container before replacing content
        area.style.whiteSpace = '';
        area.style.overflowX = '';
        area.style.wordBreak = '';

        // Simple styled textarea (no overlay - simpler and more reliable)
        area.innerHTML = `
            <textarea id="pv-editor-textarea" spellcheck="false" 
                class="w-full h-full bg-slate-950 text-slate-200 font-mono text-lg p-4 outline-none resize-none border-none leading-relaxed"
                style="caret-color: #60a5fa; tab-size: 4;"
                placeholder="Type Python code here...">${codeText}</textarea>
        `;

        const textarea = document.getElementById('pv-editor-textarea');

        // Auto-indentation on Enter
        textarea.onkeydown = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const value = textarea.value;

                // Get current line
                const beforeCursor = value.substring(0, start);
                const lines = beforeCursor.split('\n');
                const currentLine = lines[lines.length - 1];

                // Calculate indent
                const leadingSpaces = currentLine.match(/^(\s*)/)[1];
                let newIndent = leadingSpaces;

                // If line ends with ':', add 4 more spaces
                if (currentLine.trim().endsWith(':')) {
                    newIndent += '    ';
                }

                // Insert newline + indent
                const newValue = value.substring(0, start) + '\n' + newIndent + value.substring(end);
                textarea.value = newValue;
                textarea.selectionStart = textarea.selectionEnd = start + 1 + newIndent.length;
            }
            // Tab key for indentation
            else if (e.key === 'Tab') {
                e.preventDefault();
                const start = textarea.selectionStart;
                const newValue = textarea.value.substring(0, start) + '    ' + textarea.value.substring(start);
                textarea.value = newValue;
                textarea.selectionStart = textarea.selectionEnd = start + 4;
            }
        };

        // UI Update
        btn.classList.add('bg-yellow-500/20', 'text-yellow-300');
        icon.className = "fa-solid fa-cubes pointer-events-none"; // Switch to blocks icon
        showToast("Editor Mode Active", "info");

    } else {
        // Switch BACK to Playground (Refine & Sync)
        const textarea = document.getElementById('pv-editor-textarea');
        const rawCode = textarea ? textarea.value : "";

        // Parsing & Refinement
        const refinedLines = parseAndRefineCode(rawCode);

        // Update State
        pyvizState.lines = refinedLines;
        pyvizState.nextId = Math.max(...refinedLines.map(l => l.id), 0) + 1;
        pyvizState.isEditorMode = false;

        // Metadata Sync (Vars, Funcs)
        scanCodeMetadata(refinedLines);

        // Render
        renderPyViz();
        updateStats();

        // Refresh Active Toolbox (Immediate Sync)
        if (pyvizState.activeCategory && window.loadToolbox) {
            window.loadToolbox(pyvizState.activeCategory);
        }

        // UI Update
        btn.classList.remove('bg-yellow-500/20', 'text-yellow-300');
        icon.className = "fa-solid fa-pen-to-square pointer-events-none";
        showToast("Code Refined & Synced", "success");

        // Notify RuntimeInspector
        if (window.RuntimeInspector) window.RuntimeInspector.setEditorMode(false);
    }
}

// --- Inline Line Editor (Feature 6) ---
function openInlineEditor(line, idx, rowElement) {
    const originalCode = line.code;
    const indentStr = '    '.repeat(line.indent);

    // Create input element
    const input = document.createElement('input');
    input.type = 'text';
    input.value = originalCode;
    input.className = 'inline-editor-input flex-1 bg-slate-800 text-slate-100 font-mono px-2 py-1 rounded border-2 border-blue-500 outline-none';
    input.style.fontSize = 'inherit';

    // Create wrapper that maintains layout
    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-center w-full gap-2';
    wrapper.innerHTML = `
        <span class="text-slate-600 text-xs w-8 select-none text-right shrink-0">${idx + 1}</span>
        <span class="text-slate-500 font-mono whitespace-pre shrink-0">${indentStr}</span>
    `;
    wrapper.appendChild(input);

    // Add helper text
    const helper = document.createElement('div');
    helper.className = 'text-[10px] text-slate-500 shrink-0 whitespace-nowrap';
    helper.innerHTML = '<span class="text-green-400">Enter</span>=Save <span class="text-red-400">Esc</span>=Cancel';
    wrapper.appendChild(helper);

    // Replace row content
    rowElement.innerHTML = '';
    rowElement.appendChild(wrapper);
    rowElement.classList.add('bg-slate-800', 'ring-2', 'ring-blue-500/50');

    // Focus input
    input.focus();
    input.select();

    // Handle keydown
    input.onkeydown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            // Save changes
            const newCode = input.value.trim();
            if (newCode !== originalCode) {
                line.code = newCode;
                line.timestamp = new Date();

                // Determine type from code
                if (newCode.match(/^\w+\s*=/)) line.type = 'var';
                else if (newCode.startsWith('print(')) line.type = 'func';
                else if (newCode.startsWith('def ')) line.type = 'logic';
                else if (newCode.startsWith('for ') || newCode.startsWith('while ')) line.type = 'logic';
                else if (newCode.startsWith('if ') || newCode.startsWith('elif ') || newCode.startsWith('else:')) line.type = 'logic';

                logAction('Edited line', { code: newCode, type: line.type });
            }
            renderPyViz();
            updateStats();
        } else if (e.key === 'Escape') {
            // Cancel edit
            renderPyViz();
        }
    };

    // Also close on blur (clicking outside)
    input.onblur = () => {
        // Small delay to allow Enter to process first
        setTimeout(() => {
            if (document.activeElement !== input) {
                renderPyViz();
            }
        }, 100);
    };
}

// --- Syntax Highlighting for Editor Mode (Feature 3) ---
function highlightLineForEditor(line) {
    if (!line.trim()) return line; // Empty line

    const keywords = ['def', 'class', 'if', 'else', 'elif', 'for', 'while', 'return', 'import', 'from', 'as', 'break', 'continue', 'pass', 'and', 'or', 'not', 'in', 'is', 'try', 'except', 'finally', 'raise', 'with', 'lambda', 'yield', 'global', 'nonlocal', 'assert', 'del'];
    const builtins = ['print', 'input', 'len', 'range', 'int', 'str', 'float', 'list', 'dict', 'set', 'tuple', 'bool', 'type', 'open', 'file', 'abs', 'sum', 'min', 'max', 'sorted', 'enumerate', 'zip', 'map', 'filter'];
    const special = ['True', 'False', 'None', 'self'];

    // Escape HTML first
    let escaped = line
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    // Highlight strings (simple approach - handles basic cases)
    escaped = escaped.replace(/("""[\s\S]*?"""|'''[\s\S]*?'''|"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')/g,
        '<span style="color: #22c55e;">$1</span>');

    // Highlight comments
    escaped = escaped.replace(/(#.*)$/, '<span style="color: #64748b; font-style: italic;">$1</span>');

    // Highlight keywords
    keywords.forEach(kw => {
        const regex = new RegExp(`\\b(${kw})\\b`, 'g');
        escaped = escaped.replace(regex, '<span style="color: #f472b6;">$1</span>');
    });

    // Highlight builtins
    builtins.forEach(bi => {
        const regex = new RegExp(`\\b(${bi})\\b`, 'g');
        escaped = escaped.replace(regex, '<span style="color: #60a5fa;">$1</span>');
    });

    // Highlight special values
    special.forEach(sp => {
        const regex = new RegExp(`\\b(${sp})\\b`, 'g');
        escaped = escaped.replace(regex, '<span style="color: #fb923c;">$1</span>');
    });

    // Highlight numbers
    escaped = escaped.replace(/\b(\d+\.?\d*)\b/g, '<span style="color: #a78bfa;">$1</span>');

    return escaped;
}

// --- Inline Line Editor (Feature 6) ---
window.openInlineEditor = function (line, idx, rowElement) {
    // Create inline editor overlay
    const originalContent = rowElement.innerHTML;
    const originalCode = line.code;
    const indentStr = '    '.repeat(line.indent);

    // Create input element
    const input = document.createElement('input');
    input.type = 'text';
    input.value = originalCode;
    input.className = 'inline-editor-input w-full bg-slate-800 text-slate-100 font-mono px-2 py-1 rounded border-2 border-blue-500 outline-none';
    input.style.fontSize = 'inherit';

    // Create wrapper that maintains layout
    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-center w-full';
    wrapper.innerHTML = `
        <span class="text-slate-600 text-xs w-8 select-none text-right mr-4 shrink-0">${idx + 1}</span>
        <span class="text-slate-600 font-mono whitespace-pre">${indentStr}</span>
    `;
    wrapper.appendChild(input);

    // Add helper text
    const helper = document.createElement('div');
    helper.className = 'text-[10px] text-slate-500 ml-2 shrink-0';
    helper.innerHTML = '<span class="text-green-400">Enter</span> Save | <span class="text-red-400">Esc</span> Cancel';
    wrapper.appendChild(helper);

    // Replace row content
    rowElement.innerHTML = '';
    rowElement.appendChild(wrapper);
    rowElement.classList.add('bg-slate-800', 'ring-2', 'ring-blue-500/50');

    // Focus input
    input.focus();
    input.select();

    // Handle keydown
    input.onkeydown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            // Save changes
            const newCode = input.value.trim();
            if (newCode !== originalCode) {
                line.code = newCode;
                line.timestamp = new Date();

                // Determine type from code
                if (newCode.match(/^\w+\s*=/)) line.type = 'var';
                else if (newCode.startsWith('print(')) line.type = 'func';
                else if (newCode.startsWith('def ')) line.type = 'logic';
                else if (newCode.startsWith('for ') || newCode.startsWith('while ')) line.type = 'logic';
                else if (newCode.startsWith('if ') || newCode.startsWith('elif ') || newCode.startsWith('else:')) line.type = 'logic';

                logAction('Edited line', { code: newCode, type: line.type });
            }
            renderPyViz();
            updateStats();
        } else if (e.key === 'Escape') {
            // Cancel edit
            renderPyViz();
        }
    };

    // Also close on blur (clicking outside)
    input.onblur = () => {
        // Small delay to allow Enter to process first
        setTimeout(() => {
            if (document.activeElement !== input) {
                renderPyViz();
            }
        }, 100);
    };
};

function parseAndRefineCode(raw) {
    const lines = raw.split('\n');
    const newLines = [];
    let idCounter = 1;

    // Context Tracking for Indentation Enforcement
    let lastIndent = 0;
    let lastEndsWithColon = false;

    lines.forEach(lineStr => {
        if (!lineStr.trim()) return;

        // 1. Indentation Calculation
        const leadingSpaces = lineStr.match(/^\s*/)[0].length;
        let indent = 0;
        if (leadingSpaces > 0 && leadingSpaces < 4) {
            indent = 1; // Correction
        } else {
            indent = Math.floor(leadingSpaces / 4);
        }

        // 2. Code Refinement
        let code = lineStr.trim();
        code = code.replace(/([a-zA-Z0-9_]+)=([a-zA-Z0-9_"\.\[\{]+)/g, '$1 = $2');

        // 3. Indentation Enforcement (Auto-Fix Logic)
        // Rule A: If previous line ended with ':', current line MUST be indented (start of block).
        // This fixes cases where user forgets to indent after 'if a:'.
        if (lastEndsWithColon) {
            indent = lastIndent + 1;
        }
        // Rule B: If no colon previously, indent cannot increase (invalid nesting).
        // It can stay same or decrease (dedent).
        else {
            if (indent > lastIndent) {
                indent = lastIndent;
            }
        }

        // 4. Type & Metadata Extraction
        let type = 'logic';
        let meta = {};

        // Strip comments for colon check logic later
        const codeNoComment = code.split('#')[0].trim();

        if (code.startsWith('#')) {
            type = 'comment';
        }
        else if (code.startsWith('def ')) {
            type = 'func';
            const match = code.match(/^def\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\):/);
            if (match) {
                meta = { name: match[1], params: match[2] };
            }
        }
        else if (code.startsWith('import ') || code.startsWith('from ')) {
            type = 'import';
        }
        else if (code.includes('=') && !code.startsWith('if ') && !code.startsWith('while ') && !code.startsWith('for ')) {
            // Assignment
            const parts = code.split('=');
            const lhs = parts[0].trim();
            const rhs = parts.slice(1).join('=').trim();

            if (/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(lhs)) {
                type = 'var';
                meta = { name: lhs };

                if (rhs.startsWith('[') && rhs.endsWith(']')) {
                    type = 'ds'; meta.dsType = 'list';
                } else if (rhs.startsWith('(') && rhs.endsWith(')')) {
                    type = 'ds'; meta.dsType = 'tuple';
                } else if (rhs.includes('deque(')) {
                    type = 'ds'; meta.dsType = 'stack';
                } else if (rhs.includes('queue.Queue')) {
                    type = 'ds'; meta.dsType = 'queue';
                }
            }
        }

        newLines.push({
            id: idCounter++,
            code: code,
            indent: indent,
            type: type,
            meta: meta
        });

        // Update Context
        lastIndent = indent;
        lastEndsWithColon = codeNoComment.endsWith(':');
    });

    return newLines;
}

function scanCodeMetadata(lines) {
    // Metadata is now extracted during parsing above.
    // This allows toolboxes to find variables immediately via pyvizState.lines
}

// Override renderPyViz to block rendering in Editor Mode
const originalRenderPyViz = window.renderPyViz;
window.renderPyViz = function () {
    if (pyvizState.isEditorMode) return; // Block re-render
    originalRenderPyViz();
}

// ... existing download code ...
window.downloadPyFile = function () {
    const content = pyvizState.lines.map(l => '    '.repeat(l.indent) + l.code).join('\n');

    // Server-Side Download (Form POST) to ensure correct headers and filename
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'download.php'; // Targeted Handler
    form.style.display = 'none';

    const input = document.createElement('textarea');
    input.name = 'content'; // Simplified name
    input.value = content;

    form.appendChild(input);
    document.body.appendChild(form);

    form.submit();

    // Cleanup
    setTimeout(() => {
        document.body.removeChild(form);
    }, 1000);

    logAction("Downloaded .py file");
    showToast("Downloading main.py...", "success");
}

