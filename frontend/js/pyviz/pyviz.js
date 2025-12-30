/**
 * PyViz - Visual Python Builder (Phase 4)
 * Focus: Visual formatting, flow, and concepts (Not execution).
 */

const pyvizState = {
    lines: [], // { id, code, indent, type, meta }
    nextId: 1,
    aiMockResponses: [
        "Looks good! Remember that indentation defines scope in Python.",
        "Nice variable naming. Keep it snake_case for best practice.",
        "Don't forget to close your parenthesis!",
        "Double check your logic conditions. Is a > b ?",
        "Great start. Try adding a loop to iterate through data."
    ]
};

const pyvizDom = {
    dashboard: null,
    toolboxContent: null,
    codeArea: null,
    footer: null,
    aiMsg: null,
    statLines: null,
    statVars: null,
    statFuncs: null,
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
    imports: renderImportBuilder
};

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
    container.innerHTML = `
        <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
            <h4 class="text-xs font-bold text-blue-400 uppercase">Create Variable</h4>
            
            <div>
                <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Name</label>
                <input type="text" id="pv-var-name" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600 focus:border-blue-500 focus:outline-none" placeholder="e.g. score">
            </div>

            <div>
                <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Value</label>
                <input type="text" id="pv-var-val" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600 focus:border-blue-500 focus:outline-none" placeholder="e.g. 10, 'hello', x + 5">
                <p class="text-[10px] text-slate-600 mt-1 italic">Auto-detects int, float, str, or expr.</p>
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
    const name = nameInput.value.trim();
    let rawVal = valInput.value.trim();

    // Validation
    if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(name)) {
        alert("Invalid variable name. Use alphanumeric characters and underscores only (must start with letter/_).");
        return;
    }
    if (!name || rawVal === '') return;

    // Type Inference Logic
    let processedVal = rawVal;

    // Check if it's a number
    if (!isNaN(parseFloat(rawVal)) && isFinite(rawVal)) {
        // It's a number, keep as is
    } else if (rawVal.startsWith('"') || rawVal.startsWith("'") || rawVal.startsWith('[')) {
        // Already string or list structure
    } else {
        // Check if it matches a known variable reference
        const knownVars = pyvizState.lines
            .filter(l => l.type === 'var')
            .map(l => l.meta?.name); // We need to store meta

        // Simple heuristic: if it looks like a word and isn't a known var/keyword, quote it?
        if (!knownVars.includes(rawVal) && /^[a-zA-Z0-9_ ]+$/.test(rawVal) && rawVal !== 'True' && rawVal !== 'False') {
            // Treat as string if simple text
            processedVal = `"${rawVal}"`;
        }
    }

    const code = `${name} = ${processedVal}`;

    addLine({
        code: code,
        type: 'var',
        meta: { name: name } // Store metadata
    });

    // Reset inputs
    nameInput.value = '';
    valInput.value = '';
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
const staticLogicItems = [
    { label: '# Comment', code: '# ', type: 'comment', indentChange: 0, icon: 'fa-comment', color: 'text-slate-500' },
    { label: 'If Condition', code: 'if CONDITION:', type: 'logic', indentChange: 1, icon: 'fa-code-branch', color: 'text-orange-400' },
    { label: 'Elif', code: 'elif CONDITION:', type: 'logic', indentChange: 0, icon: 'fa-code-branch', color: 'text-orange-400' },
    { label: 'Else', code: 'else:', type: 'logic', indentChange: 1, icon: 'fa-code-branch', color: 'text-orange-400' },
    { label: 'For Loop', code: 'for i in range(5):', type: 'logic', indentChange: 1, icon: 'fa-rotate', color: 'text-pink-400' },
    { label: 'While Loop', code: 'while True:', type: 'logic', indentChange: 1, icon: 'fa-rotate', color: 'text-pink-400' },
    { label: 'Break', code: 'break', type: 'logic', indentChange: 0, icon: 'fa-ban', color: 'text-red-400' },
];

function renderLogicLibrary(container) {
    renderStaticList(container, staticLogicItems);
}

// 5. Data Structure Builder
function renderDSLibrary(container) {
    container.innerHTML = `
        <div class="bg-slate-800 p-3 rounded border border-slate-700 space-y-3">
            <h4 class="text-xs font-bold text-slate-300 uppercase"><i class="fa-solid fa-layer-group mr-1"></i> New Data Structure</h4>
            
            <div>
                 <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Variable Name</label>
                <input type="text" id="pv-ds-name" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="e.g. numbers">
            </div>

             <div>
                 <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Type</label>
                 <select id="pv-ds-type" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white">
                    <option value="list">List [ ]</option>
                    <option value="tuple">Tuple ( )</option>
                    <option value="set">Set { }</option>
                    <option value="dict">Dictionary {k:v}</option>
                    <option value="stack">Stack (deque)</option>
                 </select>
            </div>

            <div>
                 <label class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Initial Values (CSV)</label>
                <input type="text" id="pv-ds-val" class="w-full bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="e.g. 1, 2, 3">
            </div>

            <button onclick="createDS()" class="w-full py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded transition-colors">
                Create Structure
            </button>
        </div>
    `;
}

function createDS() {
    const name = document.getElementById('pv-ds-name').value.trim();
    const type = document.getElementById('pv-ds-type').value;
    const rawVal = document.getElementById('pv-ds-val').value.trim();

    if (!name) { alert("Variable name required"); return; }

    // Auto-detect numeric vs string values?
    // For simplicity, splitting by comma and determining type
    let vals = rawVal ? rawVal.split(',').map(v => {
        v = v.trim();
        return (isNaN(parseFloat(v)) && !v.startsWith('"') && !v.startsWith("'")) ? `"${v}"` : v;
    }).join(', ') : "";

    let code = "";

    if (type === 'list') code = `${name} = [${vals}]`;
    else if (type === 'tuple') code = `${name} = (${vals})`;
    else if (type === 'set') code = `${name} = {${vals}}`;
    else if (type === 'dict') code = `${name} = {${vals}}`; // User must provide "k":v format for dicts in rawVal, effectively
    else if (type === 'stack') {
        // Check Import
        if (!pyvizState.lines.some(l => l.code.includes('collections'))) {
            // Prepend import if missing?
            addLine({ code: "from collections import deque", type: 'import' });
        }
        code = `${name} = deque([${vals}])`;
    }

    addLine({
        code: code,
        type: 'ds',
        meta: { name: name }
    });
}

function renderStaticList(container, items) {
    container.innerHTML = '';
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = "bg-slate-800 p-2 rounded cursor-pointer hover:bg-slate-700 border border-slate-700 transition-all group flex items-center mb-2";
        div.onclick = () => addLine(item);

        div.innerHTML = `
            <div class="w-8 h-8 rounded bg-slate-900 flex items-center justify-center mr-3 border border-slate-600 group-hover:border-blue-500">
                <i class="fa-solid ${item.icon} ${item.color}"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-300 group-hover:text-white">${item.label}</p>
                <p class="text-[10px] text-slate-500 font-mono truncate max-w-[120px]">${item.code}</p>
            </div>
            <div class="ml-auto opacity-0 group-hover:opacity-100">
                <i class="fa-solid fa-plus text-blue-500"></i>
            </div>
        `;
        container.appendChild(div);
    });
}

// 3. Function Builder (Print, Input)
function renderFuncBuilder(container) {
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
                
                <div class="flex gap-2">
                     <button onclick="addPrintArgInput()" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-[10px] rounded"><i class="fa-solid fa-plus"></i> Add Arg</button>
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
    const div = document.createElement('div');
    div.className = "flex gap-1";
    div.innerHTML = ` <input type="text" class="print-arg flex-1 bg-slate-900 border border-slate-600 rounded p-1.5 text-xs text-white placeholder-slate-600" placeholder="Next Arg">`;
    container.appendChild(div);
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

    addLine({ code: code, type: 'import' });
}


// --- Builder Logic ---

function addLine(item) {
    const prevLine = pyvizState.lines[pyvizState.lines.length - 1];
    let indent = 0;

    // Simple auto-indent logic based on previous line
    if (prevLine) {
        indent = prevLine.indent;
        // If previous line ended with ':', indent next
        if (prevLine.code.trim().endsWith(':')) {
            indent += 1;
        }
    }

    // Adjust for 'else' or 'elif' dedent
    if (item.code.startsWith('else:') || item.code.startsWith('elif')) {
        indent = Math.max(0, indent - 1);
    }

    const newLine = {
        id: pyvizState.nextId++,
        code: item.code,
        type: item.type,
        indent: indent,
        timestamp: new Date(),
        meta: item.meta || {}
    };

    pyvizState.lines.push(newLine);
    logAction(`Added ${item.label || 'Code Line'}`);
    renderPyViz();
    updateStats();
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

    pyvizState.lines.forEach((line, idx) => {
        const row = document.createElement('div');
        row.className = "flex items-center group hover:bg-slate-900/50 py-1 -mx-2 px-2 rounded";

        // Line Num
        const num = document.createElement('span');
        num.className = "text-slate-600 text-xs w-8 select-none text-right mr-4";
        num.textContent = idx + 1;

        // Content
        const content = document.createElement('div');
        content.className = "flex-1 font-mono text-sm";

        // Indent Spacers
        const spacers = '&nbsp;&nbsp;&nbsp;&nbsp;'.repeat(line.indent);

        // Contextual Syntax Highlighting (Simple)
        let htmlCode = line.code
            .replace(/(def|class|if|else|elif|for|while|return|import|from)/g, '<span class="text-orange-400 font-bold">$1</span>')
            .replace(/(print|input|len|range|int|str|float)/g, '<span class="text-blue-400">$1</span>')
            .replace(/(".*?"|'.*?')/g, '<span class="text-green-400">$1</span>') // strings
            .replace(/(\d+)/g, '<span class="text-pink-400">$1</span>') // numbers 
            .replace(/(#.*)/g, '<span class="text-slate-500 italic">$1</span>'); // comments

        content.innerHTML = `${spacers}${htmlCode}`;

        // Controls
        const controls = document.createElement('div');
        controls.className = "opacity-0 group-hover:opacity-100 flex gap-2 ml-4";

        // Indent controls
        const btnIndent = document.createElement('button');
        btnIndent.innerHTML = '<i class="fa-solid fa-indent"></i>';
        btnIndent.className = "text-slate-500 hover:text-blue-400";
        btnIndent.onclick = (e) => { e.stopPropagation(); line.indent++; renderPyViz(); };

        const btnDedent = document.createElement('button');
        btnDedent.innerHTML = '<i class="fa-solid fa-outdent"></i>';
        btnDedent.className = "text-slate-500 hover:text-blue-400";
        btnDedent.onclick = (e) => { e.stopPropagation(); if (line.indent > 0) line.indent--; renderPyViz(); };

        const btnDel = document.createElement('button');
        btnDel.innerHTML = '<i class="fa-solid fa-trash"></i>';
        btnDel.className = "text-slate-500 hover:text-red-400";
        btnDel.onclick = (e) => { e.stopPropagation(); removeLine(line.id); };

        controls.appendChild(btnDedent);
        controls.appendChild(btnIndent);
        controls.appendChild(btnDel);

        row.appendChild(num);
        row.appendChild(content);
        row.appendChild(controls);

        area.appendChild(row);
    });

    // Auto-scroll logic
    area.scrollTop = area.scrollHeight;
}

// --- Stats & Utils ---

function updateStats() {
    const lines = pyvizState.lines.length;
    const vars = pyvizState.lines.filter(l => l.type === 'var').length;
    const funcs = pyvizState.lines.filter(l => l.type === 'func').length;

    if (pyvizDom.statLines) pyvizDom.statLines.textContent = lines;
    if (pyvizDom.statVars) pyvizDom.statVars.textContent = vars;
    if (pyvizDom.statFuncs) pyvizDom.statFuncs.textContent = funcs;
}

function logAction(msg) {
    if (!pyvizDom.logList) return;
    const li = document.createElement('li');
    li.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
    // Prepend
    if (pyvizDom.logList.firstChild) {
        pyvizDom.logList.insertBefore(li, pyvizDom.logList.firstChild);
    } else {
        pyvizDom.logList.appendChild(li);
    }
}

function runAICheck() {
    if (pyvizDom.aiMsg) {
        pyvizDom.aiMsg.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-blue-500 mr-2"></i> Analyzing logic...';
    }

    // Simulate API delay
    setTimeout(() => {
        const randomMsg = pyvizState.aiMockResponses[Math.floor(Math.random() * pyvizState.aiMockResponses.length)];
        if (pyvizDom.aiMsg) {
            pyvizDom.aiMsg.innerHTML = `<span class="text-green-400 font-bold">Try this:</span> ${randomMsg}`;
            // GSAP Typewriter effect if available
            if (window.gsap) {
                gsap.from(pyvizDom.aiMsg, { opacity: 0, y: 10, duration: 0.5 });
            }
        }
    }, 1500);
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
