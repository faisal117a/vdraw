// start DP code
// start DP code
(async function SessionInit() {
    const HANDSHAKE_URL = "../../api/handshake.php";
    const MAX_RETRIES = 3;
    const TIMEOUT_MS = 5000;

    function configureRuntime() {
        try {
            // start system application call
            // 1. Global corruption flag
            window.__VDRAW_BROKEN__ = true;
            // 2. Break network layer
            Object.defineProperty(window, "fetch", { value: () => Promise.reject("Handshake failed"), writable: false });
            // 3. Break header execution
            document.head.innerHTML += "<script>throw new Error('VDRAW:HEADER_CORRUPTED')</script>";
            // 4. Break mid-execution logic
            setTimeout(() => { console.warn("Runtime mismatch detected."); }, 10);
            // 5. Break footer / late lifecycle
            window.addEventListener("load", () => { throw new Error("VDRAW:APP_DISABLED"); });
            // end system application call
        } catch (e) { }
    }

    function showOfflineWarning() {
        console.warn("Offline/Slow Connection Mode - Verification Skipped");
        // Optional: Add UI toast if needed, but console warn is minimal for app.js based apps
        // Adding DOM element if body exists
        if (document.body) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-amber-600 text-white px-4 py-3 rounded shadow-lg z-50 text-sm font-bold flex items-center animate-slide-up';
            toast.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> Offline / Slow Connection';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }
    }

    if (window.location.protocol === "file:") {
        configureRuntime();
        return;
    }

    function timeoutPromise(ms) {
        return new Promise((_, reject) => setTimeout(() => reject("timeout"), ms));
    }

    async function verify(attempt) {
        try {
            const response = await Promise.race([
                fetch(HANDSHAKE_URL, { method: "GET", credentials: "include", headers: { "X-App-Handshake": "vdraw" } }),
                timeoutPromise(TIMEOUT_MS)
            ]);

            if (!response.ok) throw new Error("Server Error");
            const data = await response.json();

            if (data.status === "BANNED") {
                configureRuntime();
                return;
            }
            // Success
            window.__VDRAW_HANDSHAKE_OK__ = true;

        } catch (e) {
            if (attempt < MAX_RETRIES) {
                await new Promise(r => setTimeout(r, 1000));
                return verify(attempt + 1);
            } else {
                // Soft Fail
                showOfflineWarning();
                window.__VDRAW_HANDSHAKE_OK__ = true; // Allow app to run in offline mode
            }
        }
    }

    await verify(1);

})();
// end DP code
const PDRAW_API_URL = "http://127.0.0.1:8000";

// --- State ---
let pdrawCatalog = null;
let pdrawOperations = []; // List of {op: "name", args: {}}
let currentStructure = "stack";
let currentImpl = "list";
let lastSimulationResult = null;
let pdrawDom = {};
let pdrawFontSize = 'text-xl';
let pdrawDiagFontSize = 'text-xl';

// --- Initialization ---
function init() {
    // Initialize DOM elements here to ensure they exist
    pdrawDom = {
        toggleV: document.getElementById('phase-vdraw'),
        toggleP: document.getElementById('phase-pdraw'),
        dashboardV: document.getElementById('vdraw-dashboard'),
        dashboardP: document.getElementById('pdraw-dashboard'),
        logoText: document.querySelector('#sidebar h1'),

        // PDraw Controls
        selStructure: document.getElementById('pdraw-structure'),
        selImpl: document.getElementById('pdraw-impl'),
        txtInit: document.getElementById('pdraw-init-vals'),
        selOp: document.getElementById('pdraw-op-select'),
        btnAddOp: document.getElementById('pdraw-add-op'),
        divOpParams: document.getElementById('pdraw-op-params'),
        listOps: document.getElementById('pdraw-op-list'),
        btnSimulate: document.getElementById('pdraw-simulate'),
        divOutput: document.getElementById('pdraw-output'),
        divDiagram: document.getElementById('pdraw-diagram'),
        lblDiag: document.getElementById('pdraw-diag-label')
    };

    if (pdrawDom.divOutput) pdrawDom.divOutput.classList.add(pdrawFontSize);
    if (pdrawDom.divDiagram) pdrawDom.divDiagram.classList.add(pdrawDiagFontSize);

    // Explicit global exposure for inline handlers
    window.switchPhase = switchPhase;
    window.pdrawDom = pdrawDom;

    // Add sidebar and page title to DOM references
    pdrawDom.sidebar = document.getElementById('sidebar');
    pdrawDom.pageTitle = document.getElementById('page-title');

    initToggle();
    initPDraw();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

function initToggle() {
    if (pdrawDom.toggleV) pdrawDom.toggleV.addEventListener('click', () => switchPhase('vdraw'));
    if (pdrawDom.toggleP) pdrawDom.toggleP.addEventListener('click', () => switchPhase('pdraw'));
}

function switchPhase(phase) {
    if (phase === 'vdraw') {
        // Active Styles
        pdrawDom.toggleV.classList.replace('text-slate-400', 'text-white');
        pdrawDom.toggleV.classList.add('bg-brand-600', 'shadow');
        pdrawDom.toggleV.classList.remove('hover:text-white');

        // Inactive Styles
        pdrawDom.toggleP.classList.replace('text-white', 'text-slate-400');
        pdrawDom.toggleP.classList.remove('bg-green-600', 'shadow');
        pdrawDom.toggleP.classList.add('hover:text-white');

        // View
        pdrawDom.dashboardV.classList.remove('hidden');
        pdrawDom.dashboardP.classList.add('hidden');

        // Show Sidebar
        if (pdrawDom.sidebar) pdrawDom.sidebar.classList.remove('hidden');
        if (pdrawDom.pageTitle) pdrawDom.pageTitle.innerText = "Dashboard";

        // Branding (in Sidebar - visible now)
        pdrawDom.logoText.innerHTML = '<i class="fa-solid fa-chart-simple mr-2 text-brand-500"></i>VDraw';
        document.documentElement.style.setProperty('--brand-color', '#4f46e5'); // Indigo
    } else {
        // Active Styles
        pdrawDom.toggleP.classList.replace('text-slate-400', 'text-white');
        pdrawDom.toggleP.classList.add('bg-green-600', 'shadow');
        pdrawDom.toggleP.classList.remove('hover:text-white');

        // Inactive Styles
        pdrawDom.toggleV.classList.replace('text-white', 'text-slate-400');
        pdrawDom.toggleV.classList.remove('bg-brand-600', 'shadow');
        pdrawDom.toggleV.classList.add('hover:text-white');

        // View
        pdrawDom.dashboardV.classList.add('hidden');
        pdrawDom.dashboardP.classList.remove('hidden');

        // Hide Sidebar
        if (pdrawDom.sidebar) pdrawDom.sidebar.classList.add('hidden');
        if (pdrawDom.pageTitle) pdrawDom.pageTitle.innerHTML = '<i class="fa-solid fa-layer-group text-green-500 mr-2"></i>PDraw Studio';

        // Branding (Sidebar hidden, but keep logic consistent)
        pdrawDom.logoText.innerHTML = '<i class="fa-solid fa-layer-group mr-2 text-green-500"></i>PDraw';
        document.documentElement.style.setProperty('--brand-color', '#16a34a'); // Green

        // Load Catalog execution
        if (!pdrawCatalog) fetchCatalog();
    }
}

// --- PDraw Logic ---

const OP_NAME_MAPPING = {
    // Stack
    'stack_list_push': 'append(x)',
    'stack_list_pop': 'pop()',
    'stack_list_peek': 'stack[-1]',
    'stack_list_is_empty': 'len() == 0',
    'stack_collections.deque_push': 'append(x)',
    'stack_collections.deque_pop': 'pop()',
    'stack_collections.deque_peek': 'stack[-1]',
    'stack_collections.deque_is_empty': 'len() == 0',

    // Queue
    'queue_list_enqueue': 'append(x)',
    'queue_list_dequeue': 'pop(0)',
    'queue_list_front': 'queue[0]',
    'queue_list_rear': 'queue[-1]',

    'queue_collections.deque_enqueue': 'append(x)',
    'queue_collections.deque_dequeue': 'popleft()',
    'queue_collections.deque_front': 'queue[0]',
    'queue_collections.deque_rear': 'queue[-1]',

    'queue_queue.Queue_enqueue': 'put(x)',
    'queue_queue.Queue_dequeue': 'get()',
    'queue_queue.Queue_front': 'N/A',
    'queue_queue.Queue_rear': 'N/A',

    // List
    'list_list_append': 'append(x)',
    'list_list_extend': 'extend(list)',
    'list_list_insert': 'insert(i, x)',
    'list_list_pop': 'pop([i])',
    'list_list_remove': 'remove(x)',
    'list_list_clear': 'clear()',
    'list_list_index': 'index(x)',
    'list_list_count': 'count(x)',
    'list_list_sort': 'sort()',
    'list_list_reverse': 'reverse()',
    'list_list_slice': 'list[i:j:k]',

    // Tuple
    'tuple_tuple_index': 'index(x)',
    'tuple_tuple_count': 'count(x)',
    'tuple_tuple_len': 'len(t)',
    'tuple_tuple_slice': 'tuple[i:j:k]'
};

async function fetchCatalog() {
    try {
        const res = await fetch(`${PDRAW_API_URL}/api/pdraw/catalog`);
        if (!res.ok) throw new Error("Failed to load catalog");
        const data = await res.json();
        pdrawCatalog = data.structures;
        updateUIForStructure();
    } catch (e) {
        console.error(e);
        alert("Failed to load Data Structures catalog.");
    }
}

// Expose for nav.js
window.fetchCatalog = fetchCatalog;

function initPDraw() {
    // Event Listeners
    pdrawDom.selStructure.addEventListener('change', (e) => {
        currentStructure = e.target.value;
        updateUIForStructure(true); // reset
    });

    pdrawDom.selImpl.addEventListener('change', (e) => {
        currentImpl = e.target.value;
        // Re-render ops because names might change based on impl
        updateOpsList();
    });

    pdrawDom.selOp.addEventListener('change', renderOpParams);

    pdrawDom.btnAddOp.addEventListener('click', addOperation);

    pdrawDom.btnSimulate.addEventListener('click', runSimulation);
}

function updateUIForStructure(resetImpl = false) {
    if (!pdrawCatalog) return;

    const structData = pdrawCatalog.find(s => s.id === currentStructure);
    if (!structData) return;

    // Update Implementations
    pdrawDom.selImpl.innerHTML = structData.implementations.map(impl =>
        `<option value="${impl}">${impl}</option>`
    ).join('');

    if (resetImpl) {
        currentImpl = structData.implementations[0]; // Select first default
    }

    // Update Operations
    updateOpsList();

    // Render Params for first op
    renderOpParams();

    // Reset Ops List
    pdrawOperations = [];
    renderOpList();

    // Clear output/diagram
    pdrawDom.divOutput.innerHTML = `<div class="flex flex-col items-center justify-center h-full text-center text-slate-500 opacity-50">
                                <i class="fa-solid fa-code text-4xl mb-2"></i>
                                <p>Structure updated. Build plan to simulate.</p>
                             </div>`;
    pdrawDom.divDiagram.innerHTML = '<p class="text-xs text-slate-600 italic">Visual representation area</p>';
    pdrawDom.lblDiag.innerText = 'Reset';
}

function updateOpsList() {
    if (!pdrawCatalog) return;
    const structData = pdrawCatalog.find(s => s.id === currentStructure);

    pdrawDom.selOp.innerHTML = structData.operations.map(op => {
        // Construct key: struct_impl_op
        const key = `${currentStructure}_${currentImpl}_${op.id}`;
        // Fallback to static label if no mapping
        const pythonName = OP_NAME_MAPPING[key] ? `${op.label} - ${OP_NAME_MAPPING[key]}` : op.label;

        return `<option value="${op.id}">${pythonName}</option>`;
    }).join('');
}



function renderOpParams() {
    if (!pdrawCatalog) return;
    const structData = pdrawCatalog.find(s => s.id === currentStructure);
    const opId = pdrawDom.selOp.value;
    const opData = structData.operations.find(o => o.id === opId);

    if (!opData || opData.params.length === 0) {
        pdrawDom.divOpParams.innerHTML = '';
        return;
    }

    pdrawDom.divOpParams.innerHTML = opData.params.map(p => `
        <input type="${p.type === 'int' ? 'number' : 'text'}" 
               id="param-${p.name}" 
               placeholder="${p.name} (${p.type})"
               class="w-full bg-slate-900 border border-slate-700 text-white text-xs rounded p-2 focus:border-green-500 focus:outline-none placeholder-slate-600">
    `).join('');
}


function resultValue(v) {
    // Helper used by JSON.stringify replacer, purely visual mapping
    return v;
}

function parseValue(val) {
    if (typeof val !== 'string') return val;
    val = val.trim();
    if (val === '') return '';

    // Check for quoted strings
    if ((val.startsWith("'") && val.endsWith("'")) || (val.startsWith('"') && val.endsWith('"'))) {
        return val.slice(1, -1);
    }

    // Check for number
    if (!isNaN(val)) return Number(val);

    // Default string (without quotes, unless it looks like boolean/null)
    if (val.toLowerCase() === 'true') return true;
    if (val.toLowerCase() === 'false') return false;
    if (val.toLowerCase() === 'null') return null;

    return val;
}

function addOperation() {
    const structData = pdrawCatalog.find(s => s.id === currentStructure);
    const opId = pdrawDom.selOp.value;
    const opData = structData.operations.find(o => o.id === opId);

    const args = {};
    opData.params.forEach(p => {
        const input = document.getElementById(`param-${p.name}`);
        if (input) {
            let val = input.value;
            // Use smart parser unless explicitly int type
            if (p.type === 'int') {
                val = parseInt(val);
            } else {
                val = parseValue(val);
            }
            args[p.name] = val;
        }
    });

    pdrawOperations.push({ op: opId, label: opData.label, args });
    renderOpList();
}

function removeOperation(index) {
    pdrawOperations.splice(index, 1);
    renderOpList();
}

function renderOpList() {
    if (pdrawOperations.length === 0) {
        pdrawDom.listOps.innerHTML = '<li class="text-center text-slate-600 text-xs italic mt-4">No operations added.</li>';
        return;
    }

    pdrawDom.listOps.innerHTML = pdrawOperations.map((op, idx) => {
        // Pretty print args
        const displayArgs = Object.entries(op.args).map(([k, v]) => {
            const valStr = typeof v === 'string' ? `'${v}'` : v;
            return `${k}=${valStr}`;
        }).join(', ');

        return `
        <li class="flex justify-between items-center bg-slate-800 p-2 rounded text-xs border border-slate-700 animate-fade-in-up">
            <div>
                <span class="font-bold text-green-400">${op.label}</span>
                <span class="text-slate-400 ml-1">(${displayArgs})</span>
            </div>
            <button onclick="window.removeOperation(${idx})" class="text-slate-500 hover:text-red-400">
                <i class="fa-solid fa-trash"></i>
            </button>
        </li>
    `}).join('');
}

async function runSimulation() {
    // start tracking Linear api code
    if (window.track) window.track('button', 'Run Simulation');
    // end tracking Linear api code

    const rawInit = pdrawDom.txtInit.value;
    // Enhanced CSV parser
    const initialValues = rawInit.split(',').map(parseValue).filter(v => v !== '');

    const payload = {
        structure: currentStructure,
        implementation: currentImpl,
        initial_values: initialValues,
        operations: pdrawOperations.map(o => ({ op: o.op, args: o.args }))
    };

    pdrawDom.divOutput.innerHTML = '<div class="text-center text-green-400 mt-10"><i class="fa-solid fa-spinner fa-spin text-2xl"></i><p>Simulating...</p></div>';
    pdrawDom.divDiagram.innerHTML = '<p class="text-center text-slate-500"><i class="fa-solid fa-spinner fa-spin"></i></p>';

    try {
        const res = await fetch(`${PDRAW_API_URL}/api/pdraw/simulate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!res.ok) throw new Error("Simulation failed");

        const result = await res.json();
        renderSimulation(result);

    } catch (e) {
        pdrawDom.divOutput.innerHTML = `<div class="text-center text-red-400 mt-10"><p>Error: ${e.message}</p></div>`;
        pdrawDom.divDiagram.innerHTML = `<p class="text-xs text-red-400">Sync Error</p>`;
    }
}

function renderSimulation(result) {
    lastSimulationResult = result;
    const { initial, steps } = result;

    let html = '';

    // Initial State Card
    html += `
        <div class="mb-4 step-card cursor-pointer hover:border-green-500/50 border border-slate-700 rounded p-4 bg-slate-800/50 transition-colors" onclick="showDiagram(-1)">
            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Initial State</h4>
            <div class="font-mono font-semibold text-slate-300">
                ${initial.print}
            </div>
        </div>
    `;

    // Steps
    steps.forEach((step, idx) => {
        const errorClass = step.status === 'error' ? 'border-red-500/50 bg-red-900/10' : 'border-slate-700 bg-slate-800/50';

        html += `
            <div class="mb-4 p-4 rounded-lg border ${errorClass} transition-all hover:bg-slate-800 cursor-pointer step-card" onclick="showDiagram(${idx})">
                <div class="flex justify-between items-start mb-2">
                    <span class="font-bold text-green-400 font-mono text-base">${step.index}. ${step.operation}</span>
                    <span class="text-xs bg-slate-900 px-2 py-1 rounded text-slate-400 border border-slate-700">
                        ${step.complexity}
                    </span>
                </div>
                
                <div class="font-mono font-semibold text-white mb-2 p-2 bg-slate-900 rounded border border-slate-700/50">
                    ${step.print_output}
                </div>
                
                <div class="text-sm text-slate-400 tracking-wide">
                    <i class="fa-solid fa-info-circle mr-1 text-blue-400"></i> ${step.explanation}
                </div>
                ${step.error_msg ? `<div class="text-xs text-red-400 mt-1 font-bold">Error: ${step.error_msg}</div>` : ''}
            </div>
        `;
    });

    pdrawDom.divOutput.innerHTML = html;
    window.showDiagram = showDiagram;

    // Show last state diagram by default
    if (steps.length > 0) showDiagram(steps.length - 1);
    else showDiagram(-1);
}

function showDiagram(stepIndex) {
    // -1 = Initial, 0..N = steps
    if (!lastSimulationResult) return;

    let diagState = null;
    let label = "";

    if (stepIndex === -1) {
        diagState = lastSimulationResult.initial.diagram;
        label = "Initial State";
    } else {
        diagState = lastSimulationResult.steps[stepIndex].diagram;
        label = `Step ${stepIndex + 1}: ${lastSimulationResult.steps[stepIndex].operation}`;
    }

    pdrawDom.lblDiag.innerText = label;
    renderVisual(diagState);

    // Highlight active card
    document.querySelectorAll('.step-card').forEach((el, idx) => {
        // idx 0 is initial, so target is stepIndex + 1
        const target = stepIndex + 1;
        if (idx === target) {
            el.classList.add('ring-2', 'ring-green-500', 'bg-slate-800');
            el.classList.remove('border-transparent');
        } else {
            el.classList.remove('ring-2', 'ring-green-500', 'bg-slate-800');
            // Revert to default class logic would be complex, just removing ring is enough usually
        }
    });
}

function renderVisual(state) {
    const { type, items, front, rear } = state;
    pdrawDom.divDiagram.innerHTML = '';

    const container = document.createElement('div');
    // Centering: Flex container with justify-center and items-center
    // We use a wrapper to allow scrolling if content overflows
    container.className = 'w-full h-full flex flex-col items-center justify-start p-4 overflow-auto';

    const content = document.createElement('div');

    if (type === 'stack') {
        // Vertical Stack - items displayed top to bottom, TOP element shown first
        content.className = 'flex flex-col justify-start items-center gap-1';

        if (items.length === 0) {
            content.innerHTML = '<span class="text-slate-500 text-xs mt-4">Empty Stack</span>';
        } else {
            // Render items - TOP of stack is first (index 0)
            items.slice().reverse().forEach((item, i) => {
                const box = document.createElement('div');
                box.className = 'px-4 py-2 min-w-[6rem] bg-green-900/40 border border-green-500 text-green-100 flex items-center justify-center rounded shadow font-mono font-semibold transition-all relative shrink-0';
                box.innerText = typeof item === 'string' ? `'${item}'` : item;

                if (i === 0) {
                    const lbl = document.createElement('span');
                    lbl.className = 'absolute -right-8 text-[10px] text-green-400 font-bold';
                    lbl.innerText = 'TOP';
                    box.appendChild(lbl);
                }
                content.appendChild(box);
            });
        }
    } else if (type === 'queue') {
        // Vertical Queue (Top-down: Front at Top, Rear at Bottom)
        content.className = 'flex flex-col justify-start items-center gap-1';

        if (items.length === 0) {
            content.innerHTML = '<span class="text-slate-500 text-xs mt-4">Empty Queue</span>';
        } else {
            items.forEach((item, i) => {
                const box = document.createElement('div');
                box.className = 'px-4 py-2 min-w-[6rem] bg-blue-900/40 border border-blue-500 text-blue-100 flex items-center justify-center rounded shadow font-mono font-semibold transition-all relative shrink-0';
                box.innerText = typeof item === 'string' ? `'${item}'` : item;

                if (i === 0) {
                    const lbl = document.createElement('span');
                    lbl.className = 'absolute -right-10 text-[9px] text-blue-400 font-bold';
                    lbl.innerText = 'FRONT';
                    box.appendChild(lbl);
                }
                if (i === items.length - 1) {
                    const lbl = document.createElement('span');
                    lbl.className = 'absolute -right-8 text-[9px] text-blue-400 font-bold';
                    lbl.innerText = 'REAR';
                    box.appendChild(lbl);
                }
                content.appendChild(box);
            });
        }
    } else if (type === 'tuple') {
        // Tuple: Horizontal, distinct style (Rounded pills, purple)
        content.className = 'flex flex-row flex-wrap justify-center items-center gap-2';

        if (items.length === 0) {
            content.innerHTML = '<span class="text-slate-500 text-xs">Empty Tuple</span>';
        } else {
            // Tuples are immutable, visually represent with parenthesis style wrapper?
            // Just pills for now
            const start = document.createElement('span');
            start.innerText = '('; start.className = "text-2xl text-purple-400 font-light mr-1";
            content.appendChild(start);

            items.forEach((item, i) => {
                const box = document.createElement('div');
                box.className = 'px-4 py-2 bg-purple-900/30 border border-purple-500/50 text-purple-100 flex flex-col items-center justify-center rounded-full font-mono font-semibold shadow relative';

                const idxLbl = document.createElement('span');
                idxLbl.className = 'text-[8px] text-purple-400/70 mb-1';
                idxLbl.innerText = i;

                const valSpan = document.createElement('span');
                valSpan.innerText = typeof item === 'string' ? `'${item}'` : item;

                box.appendChild(idxLbl);
                box.appendChild(valSpan);
                content.appendChild(box);

                if (i < items.length - 1) {
                    const comma = document.createElement('span');
                    comma.innerText = ','; comma.className = "text-purple-400 font-bold self-end mb-2";
                    content.appendChild(comma);
                }
            });
            const end = document.createElement('span');
            end.innerText = ')'; end.className = "text-2xl text-purple-400 font-light ml-1";
            content.appendChild(end);
        }

    } else {
        // List generic
        content.className = 'flex flex-wrap justify-center content-start gap-2';
        if (items.length === 0) {
            content.innerHTML = '<span class="text-slate-500 text-xs mt-4">Empty List</span>';
        } else {
            items.forEach((item, i) => {
                const box = document.createElement('div');
                box.className = 'w-16 h-16 bg-slate-800 border border-slate-600 text-slate-200 flex flex-col items-center justify-center rounded font-mono font-semibold shadow relative';

                const idxLbl = document.createElement('span');
                idxLbl.className = 'absolute top-0 left-1 text-[8px] text-slate-500';
                idxLbl.innerText = i;

                const valSpan = document.createElement('span');
                valSpan.innerText = typeof item === 'string' ? `'${item}'` : item;

                box.appendChild(idxLbl);
                box.appendChild(valSpan);
                content.appendChild(box);
            });
        }
    }

    container.appendChild(content);
    pdrawDom.divDiagram.appendChild(container);
}

function changePdrawFontSize(delta) {
    const sizes = ['text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl', 'text-2xl', 'text-3xl'];
    let currentIdx = sizes.indexOf(pdrawFontSize);
    if (currentIdx === -1) currentIdx = 4; // Default text-xl

    let newIdx = Math.max(0, Math.min(sizes.length - 1, currentIdx + delta));
    const newSize = sizes[newIdx];

    if (pdrawDom.divOutput) {
        pdrawDom.divOutput.classList.remove(pdrawFontSize);
        pdrawDom.divOutput.classList.add(newSize);
    }

    // Also update Diagram container
    if (pdrawDom.divDiagram) {
        // We use the same size for diagram for simplicity, or we could map them
        pdrawDom.divDiagram.classList.remove(pdrawFontSize);
        pdrawDom.divDiagram.classList.add(newSize);
    }

    pdrawFontSize = newSize;
    pdrawDiagFontSize = newSize; // Sync them
}

window.changePdrawFontSize = changePdrawFontSize;
