/**
 * PyViz - Visual Python Builder (Phase 4)
 * Focus: Visual formatting, flow, and concepts (Not execution).
 */

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
    fontSize: 'text-base',
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
    pyvizDom.statLines = document.getElementById('pyviz-stat-lines');
    pyvizDom.statVars = document.getElementById('pyviz-stat-vars');
    pyvizDom.statFuncs = document.getElementById('pyviz-stat-funcs');
    pyvizDom.statLoops = document.getElementById('pyviz-stat-loops');
    pyvizDom.statConds = document.getElementById('pyviz-stat-conds');
    pyvizDom.statImports = document.getElementById('pyviz-stat-imports');
    pyvizDom.logList = document.getElementById('pyviz-log-list');

    // Controls
    document.getElementById('pyviz-btn-clear')?.addEventListener('click', clearPyViz);
    document.getElementById('pyviz-btn-check-ai')?.addEventListener('click', runAICheck);
    document.getElementById('pyviz-btn-download')?.addEventListener('click', downloadPyFile);

    // Toolbox Tabs
    const catButtons = document.querySelectorAll('#pyviz-toolbox-cats button');
    catButtons.forEach(btn => {
        btn.onclick = () => loadToolbox(btn.dataset.cat);
    });

    // Initial Load
    loadToolbox('vars');
    renderPyViz();
};



// --- Toolbox Logic ---

const toolboxRenderers = {
    vars: renderVarBuilder,
    funcs: renderFuncBuilder,
    logic: renderLogicLibrary,
    ds: renderDSLibrary,
    imports: renderImportBuilder,
    py_funcs: renderPyFuncBuilder
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
                <h4 class="text-xs font-bold text-yellow-500 uppercase"><i class="fa-solid fa-code mr-1"></i> Python Functions</h4>

                <!-- Mode Selector -->
                <div>
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Mode</label>
                     <select id="pv-func-mode" onchange="toggleFuncMode()" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                        <option value="define">User-Defined Function (Def)</option>
                        <option value="builtin">Built-in Function</option>
                        <option value="call">Function Call (User)</option>
                        <option value="return">Return Statement</option>
                     </select>
                </div>

                <!-- 1. Define Function Mode -->
                <div id="pv-func-define-ui" class="space-y-3">
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Function Name</label>
                        <input type="text" id="pv-func-def-name" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="e.g. calculate_sum">
                    </div>

                    <div>
                        <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Parameters</label>
                        <div id="pv-func-params" class="space-y-2 mb-2"></div>
                        <button onclick="addFnParamInput()" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-[10px] rounded w-full"><i class="fa-solid fa-plus"></i> Add Parameter</button>
                    </div>

                    <button onclick="createFnDef()" class="w-full py-2 bg-yellow-600 hover:bg-yellow-500 text-white text-xs font-bold rounded transition-colors">
                        Define Function
                    </button>
                </div>

                <!-- 2. Built-in Function Mode -->
                <div id="pv-func-builtin-ui" class="space-y-3 hidden">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Package</label>
                            <select id="pv-bi-pkg" onchange="updateBuiltInFuncs()" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                                <option value="math">math</option>
                                <option value="random">random</option>
                                <option value="builtins">built-in</option>
                                <option value="datetime">datetime</option>
                            </select>
                        </div>
                         <div>
                            <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Function</label>
                            <select id="pv-bi-func" onchange="updateBuiltInParams()" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Parameters for Built-in -->
                     <div id="pv-bi-params-container" class="space-y-2">
                        <!-- Inputs generated here -->
                     </div>

                    <button onclick="createBuiltInCall()" class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded transition-colors">
                        Insert Built-in Call
                    </button>
                </div>

                <!-- 3. Function Call Mode -->
                <div id="pv-func-call-ui" class="space-y-3 hidden">
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Select Function</label>
                        <select id="pv-func-call-select" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white font-mono">
                            <option value="">(No functions found)</option>
                        </select>
                        <p class="text-[9px] text-slate-500 mt-1 italic">Lists functions defined in your code starting with 'def'.</p>
                    </div>

                    <div>
                        <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Arguments</label>
                        <div id="pv-func-args" class="space-y-2 mb-2"></div>
                        <button onclick="addFnCallArgInput()" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-[10px] rounded w-full"><i class="fa-solid fa-plus"></i> Add Argument</button>
                    </div>

                    <button onclick="createFnCall()" class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded transition-colors">
                        Insert Function Call
                    </button>
                </div>

                <!-- 4. Return Statement Mode -->
                <div id="pv-func-return-ui" class="space-y-3 hidden">
                    <div>
                         <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Return Value / Expression</label>
                        <input type="text" id="pv-return-val" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="e.g. 10, a + b, result">
                    </div>
                    <button onclick="createReturnStmt()" class="w-full py-2 bg-green-600 hover:bg-green-500 text-white text-xs font-bold rounded transition-colors">
                        Insert Return
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

    defUI.classList.add('hidden');
    biUI.classList.add('hidden');
    callUI.classList.add('hidden');
    retUI.classList.add('hidden');

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
    }
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
        b.className = (b.dataset.cat === category)
            ? "px-2 py-1 text-[10px] font-bold rounded bg-blue-600 text-white shadow"
            : "px-2 py-1 text-[10px] font-bold rounded bg-slate-700 text-slate-300 hover:text-white";
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
            <h4 class="text-xs font-bold text-blue-400 uppercase">Create Variable</h4>
            
            <div>
                <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Name</label>
                <input type="text" id="pv-var-name" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600 focus:border-blue-500 focus:outline-none" placeholder="e.g. score">
            </div>

            <div>
                <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Value (Manual)</label>
                <input type="text" id="pv-var-val" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600 focus:border-blue-500 focus:outline-none" placeholder="e.g. 10">
            </div>
            
            <!-- Generated Calls Dropdown -->
            <div>
                <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">OR Generated Call</label>
                <select id="pv-var-buffer-select" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white font-mono">
                    <option value="">-- Select Generated Call --</option>
                    ${bufferOpts}
                </select>
            </div>

            <button onclick="createVariable()" class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Set Variable
            </button>
        </div>

        <div class="mt-4">
             <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Existing Variables</h4>
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
                <h4 class="text-xs font-bold text-slate-300 uppercase"><i class="fa-solid fa-print mr-1"></i> Print Statement</h4>
                
                <div id="pv-print-args" class="space-y-2">
                    <div class="flex gap-1">
                        <input type="text" class="print-arg flex-1 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="Arg 1 (e.g. 'Result:', x)">
                    </div>
                </div>
                
                <div class="space-y-2">
                     <div>
                        <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Add Generated Call (Optional)</label>
                        <select id="pv-print-buffer-select" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white font-mono">
                            <option value="">-- Select Generated Call --</option>
                            ${bufferOpts}
                        </select>
                    </div>

                    <div class="flex gap-2">
                         <button onclick="addPrintArgInput()" class="w-full px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-[10px] rounded"><i class="fa-solid fa-plus"></i> Add Arg (Text or Selected Call)</button>
                    </div>
                </div>

                 <div class="flex items-center gap-2 mt-2">
                    <input type="checkbox" id="pv-print-end" class="accent-blue-500 h-3 w-3">
                    <label for="pv-print-end" class="text-xs text-slate-400">Custom End</label>
                    <input type="text" id="pv-print-end-val" class="w-12 bg-slate-900 border border-slate-600 rounded p-1 text-xs text-white" value='" "'>
                </div>

                <button onclick="createPrint()" class="w-full py-2 bg-slate-600 hover:bg-slate-500 text-white text-xs font-bold rounded transition-colors">
                    Insert Print()
                </button>
            </div>

            <!-- Input Builder -->
            <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-xs font-bold text-slate-300 uppercase"><i class="fa-regular fa-keyboard mr-1"></i> Input Statement</h4>
                
                <div>
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Target Variable</label>
                    <input type="text" id="pv-input-var" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="e.g. user_age">
                </div>
                
                <div>
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Prompt Message</label>
                    <input type="text" id="pv-input-prompt" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="e.g. Enter your age">
                </div>

                 <div>
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Type Cast</label>
                     <select id="pv-input-type" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                        <option value="str">String (Default)</option>
                        <option value="int">Integer (int())</option>
                        <option value="float">Float (float())</option>
                     </select>
                </div>

                <button onclick="createInput()" class="w-full py-2 bg-slate-600 hover:bg-slate-500 text-white text-xs font-bold rounded transition-colors">
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
                <h4 class="text-xs font-bold text-slate-300 uppercase"><i class="fa-solid fa-file-import mr-1"></i> Import Module</h4>
                
                <div>
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Standard Library</label>
                     <select id="pv-import-std" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                        <option value="math">math</option>
                        <option value="random">random</option>
                        <option value="datetime">datetime</option>
                        <option value="collections">collections</option>
                        <option value="json">json</option>
                     </select>
                </div>

                <button onclick="createImport('std')" class="w-full py-2 bg-slate-600 hover:bg-slate-500 text-white text-xs font-bold rounded transition-colors">
                    Import Standard Lib
                </button>
                
                <hr class="border-slate-700">

                <div>
                     <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Third-Party (Visual Only)</label>
                     <select id="pv-import-ext" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                        <option value="numpy">numpy as np</option>
                        <option value="pandas">pandas as pd</option>
                        <option value="matplotlib.pyplot">matplotlib.pyplot as plt</option>
                     </select>
                </div>

                <button onclick="createImport('ext')" class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded transition-colors">
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

    logAction(`Added ${item.label || 'Code Line'}`);
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
    if (pyvizState.lines.length === 0) {
        area.innerHTML = `
            <div class="text-slate-600 italic text-center mt-20 select-none pointer-events-none">
                <i class="fa-brands fa-python text-4xl mb-4 opacity-20"></i><br>
                Drag and drop or click blocks to build your Python code.
            </div>`;
        return;
    }

    area.innerHTML = '';

    // Font size state validation
    if (!pyvizState.fontSize) pyvizState.fontSize = 'text-base';

    const keywords = new Set(['def', 'class', 'if', 'else', 'elif', 'for', 'while', 'return', 'import', 'from', 'as', 'break', 'continue', 'pass', 'and', 'or', 'not', 'in', 'is']);
    const builtins = new Set(['print', 'input', 'len', 'range', 'int', 'str', 'float', 'list', 'dict', 'set', 'tuple', 'deque']);

    pyvizState.lines.forEach((line, idx) => {
        const row = document.createElement('div');
        // Dynamic class for selection
        const isSelected = pyvizState.selectedLineId === line.id;
        const bgClass = isSelected ? 'bg-slate-800 border-l-2 border-yellow-500' : 'hover:bg-slate-900/50';
        row.className = `flex items-center group ${bgClass} py-1 -mx-2 px-2 rounded ${pyvizState.fontSize} cursor-pointer transition-colors`;

        row.onclick = () => {
            if (pyvizState.selectedLineId === line.id) pyvizState.selectedLineId = null; // Toggle off
            else pyvizState.selectedLineId = line.id; // Toggle on
            renderPyViz(); // Re-render to show selection
        };

        const num = document.createElement('span');
        num.className = "text-slate-600 text-xs w-8 select-none text-right mr-4 shrink-0";
        num.textContent = idx + 1;

        const content = document.createElement('div');
        content.className = "flex-1 font-mono whitespace-pre";

        const indentStr = '    '.repeat(line.indent);

        // Robust Highlighting: Split by delimiters but keep them
        // Matches: strings, comments, words, numbers, non-word chars (single char to avoid eating quotes)
        const tokens = line.code.split(/("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*'|#.*$|\b\d+(?:\.\d+)?\b|[a-zA-Z_]\w*|[^\s\w])/g);

        const htmlParts = tokens.map(token => {
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
            // Other (punctuation etc)
            return escapeHtml(token);
        });

        content.innerHTML = `${indentStr}${htmlParts.join('')}`;

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

    area.scrollTop = area.scrollHeight;
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
function renderLogicLibrary(container) {
    container.innerHTML = `
        <div class="space-y-4">
            <!-- New Condition Builder -->
             <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
                <h4 class="text-xs font-bold text-orange-400 uppercase"><i class="fa-solid fa-code-branch mr-1"></i> If / Loop Builder</h4>
                
                <div class="grid grid-cols-1 gap-2">
                     <div class="flex gap-2">
                         <select id="pv-logic-kw" class="w-20 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white mobile-full">
                            <option value="if">if</option>
                            <option value="elif">elif</option>
                            <option value="while">while</option>
                         </select>
                         <input type="text" id="pv-logic-exp1" class="flex-1 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="Exp 1 (e.g. x)">
                     </div>
                     
                     <div class="flex gap-2">
                         <select id="pv-logic-op" class="w-16 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white font-mono mobile-full">
                            <option value="==">==</option>
                            <option value="!=">!=</option>
                            <option value=">">></option>
                            <option value="<"><</option>
                            <option value=">=">>=</option>
                            <option value="<="><=</option>
                            <option value="in">in</option>
                         </select>
                         <input type="text" id="pv-logic-exp2" class="flex-1 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="Exp 2 (e.g. 10)">
                     </div>
                </div>
                
                <div class="flex items-center gap-2">
                     <span class="text-[10px] text-slate-500 font-bold uppercase">Logical Op (Optional)</span>
                     <select id="pv-logic-join" class="w-20 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                        <option value="">None</option>
                        <option value="and">and</option>
                        <option value="or">or</option>
                     </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                     <button onclick="createCondition()" class="py-2 bg-orange-600 hover:bg-orange-500 text-white text-xs font-bold rounded transition-colors">
                        Add Condition
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

function createCondition() {
    const kw = document.getElementById('pv-logic-kw').value;
    const exp1 = document.getElementById('pv-logic-exp1').value.trim();
    const op = document.getElementById('pv-logic-op').value;
    const exp2 = document.getElementById('pv-logic-exp2').value.trim();
    const join = document.getElementById('pv-logic-join').value;

    if (!exp1 || !exp2) { alert("Please complete the condition expressions"); return; }

    let cond = `${exp1} ${op} ${exp2}`;
    if (join) {
        cond += ` ${join} ...`; // Simplified placeholder
    }

    addLine({
        code: `${kw} ${cond}:`,
        type: 'logic'
    });
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
function updateStats() { // Recalc
    const lines = pyvizState.lines.length;

    // Unique Variables (from 'var' and 'ds' types)
    const uniqueVars = new Set(
        pyvizState.lines
            .filter(l => (l.type === 'var' || l.type === 'ds'))
            .map(l => l.meta?.name)
            .filter(n => n)
    ).size;

    // Functions: Split user-defined vs built-ins is hard without 'def'. 
    // Current 'func' type is just print/input.
    // Let's count 'def' keywords as user functions, and 'func' items as Built-in Calls + 'def' lines.

    const defs = pyvizState.lines.filter(l => l.code.startsWith('def ')).length;
    const calls = pyvizState.lines.filter(l => l.type === 'func').length; // print/input mainly

    const loops = pyvizState.lines.filter(l => l.code.match(/\b(for|while)\b/)).length;
    const conds = pyvizState.lines.filter(l => l.code.match(/\b(if|elif|else)\b/)).length;
    const imports = pyvizState.lines.filter(l => l.type === 'import').length;

    if (pyvizDom.statLines) pyvizDom.statLines.textContent = lines;
    if (pyvizDom.statVars) pyvizDom.statVars.textContent = uniqueVars; // Fixed
    if (pyvizDom.statFuncs) pyvizDom.statFuncs.textContent = `${defs} Def / ${calls} Calls`; // Split

    if (pyvizDom.statLoops) pyvizDom.statLoops.textContent = loops;
    if (pyvizDom.statConds) pyvizDom.statConds.textContent = conds;
    if (pyvizDom.statImports) pyvizDom.statImports.textContent = imports;
}

function changeFontSize(delta) {
    const sizes = ['text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl', 'text-2xl', 'text-3xl', 'text-4xl'];
    let currentIdx = sizes.indexOf(pyvizState.fontSize || 'text-base');
    let newIdx = Math.max(0, Math.min(sizes.length - 1, currentIdx + delta));
    pyvizState.fontSize = sizes[newIdx];
    renderPyViz();
}

function logAction(msg) {
    if (!pyvizDom.logList) return;
    const li = document.createElement('li');
    li.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
    if (pyvizDom.logList.firstChild) {
        pyvizDom.logList.insertBefore(li, pyvizDom.logList.firstChild);
    } else {
        pyvizDom.logList.appendChild(li);
    }
}


// Logic Check (Replaces AI Mock)
function runAICheck() {
    if (pyvizDom.aiMsg) {
        pyvizDom.aiMsg.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-blue-500 mr-2"></i> Checking logic...';
    }

    setTimeout(() => {
        const issues = [];

        // 1. Check Colon endings
        pyvizState.lines.forEach((l, i) => {
            const stripped = l.code.trim();
            if (/^(if|else|elif|for|while|def|class)\b/.test(stripped)) {
                if (!stripped.endsWith(':')) {
                    issues.push(`Line ${i + 1}: Missing colon ':' at the end of statement.`);
                }
            }
        });

        // 2. Parentheses match (Basic)
        pyvizState.lines.forEach((l, i) => {
            const open = (l.code.match(/\(/g) || []).length;
            const close = (l.code.match(/\)/g) || []).length;
            if (open !== close) {
                issues.push(`Line ${i + 1}: Mismatched parentheses (${open} open, ${close} closed).`);
            }
        });

        // 3. Indentation Check
        for (let i = 0; i < pyvizState.lines.length; i++) {
            const line = pyvizState.lines[i];
            const clean = line.code.split('#')[0].trim();

            if (clean.endsWith(':')) {
                // Next non-empty/non-comment line MUST have deeper indent
                let j = i + 1;
                let foundNext = false;

                while (j < pyvizState.lines.length) {
                    const nextL = pyvizState.lines[j];
                    const nextClean = nextL.code.split('#')[0].trim();
                    if (nextClean) { // Found code line
                        if (nextL.indent <= line.indent) {
                            issues.push(`Line ${j + 1}: Expected an indented block after statement on Line ${i + 1}.`);
                        }
                        foundNext = true;
                        break;
                    }
                    j++;
                }

                if (!foundNext && i < pyvizState.lines.length - 1) {
                    // issues.push(`Line ${i + 1}: Block ends without content.`); // Optional warning
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

function downloadPyFile() {
    const content = pyvizState.lines.map(l => '    '.repeat(l.indent) + l.code).join('\n');
    const blob = new Blob([content], { type: 'text/x-python' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'main.py';
    a.click();
    URL.revokeObjectURL(url);
    logAction("Downloaded .py file");
}
