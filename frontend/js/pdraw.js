const PDRAW_API_URL = "http://127.0.0.1:8000";

// --- State ---
let pdrawCatalog = null;
let pdrawOperations = []; // List of {op: "name", args: {}}
let currentStructure = "stack";
let currentImpl = "list";
let lastSimulationResult = null;
let pdrawDom = {};

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

function initPDraw() {
    // Event Listeners
    pdrawDom.selStructure.addEventListener('change', (e) => {
        currentStructure = e.target.value;
        updateUIForStructure();
    });

    pdrawDom.selImpl.addEventListener('change', (e) => {
        currentImpl = e.target.value;
    });

    pdrawDom.selOp.addEventListener('change', renderOpParams);

    pdrawDom.btnAddOp.addEventListener('click', addOperation);

    pdrawDom.btnSimulate.addEventListener('click', runSimulation);
}

function updateUIForStructure() {
    if (!pdrawCatalog) return;

    const structData = pdrawCatalog.find(s => s.id === currentStructure);
    if (!structData) return;

    // Update Implementations
    pdrawDom.selImpl.innerHTML = structData.implementations.map(impl =>
        `<option value="${impl}">${impl}</option>`
    ).join('');
    currentImpl = structData.implementations[0]; // Select first default

    // Update Operations
    pdrawDom.selOp.innerHTML = structData.operations.map(op =>
        `<option value="${op.id}">${op.label}</option>`
    ).join('');

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

function addOperation() {
    const structData = pdrawCatalog.find(s => s.id === currentStructure);
    const opId = pdrawDom.selOp.value;
    const opData = structData.operations.find(o => o.id === opId);

    const args = {};
    opData.params.forEach(p => {
        const input = document.getElementById(`param-${p.name}`);
        if (input) {
            let val = input.value;
            if (p.type === 'int') val = parseInt(val);
            // Handle quotes for string if needed, currently assuming raw value
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

    pdrawDom.listOps.innerHTML = pdrawOperations.map((op, idx) => `
        <li class="flex justify-between items-center bg-slate-800 p-2 rounded text-xs border border-slate-700 animate-fade-in-up">
            <div>
                <span class="font-bold text-green-400">${op.label}</span>
                <span class="text-slate-400 ml-1">(${JSON.stringify(op.args).replace(/["{}]/g, '').replace(/:/g, '=')})</span>
            </div>
            <button onclick="removeOperation(${idx})" class="text-slate-500 hover:text-red-400">
                <i class="fa-solid fa-trash"></i>
            </button>
        </li>
    `).join('');

    window.removeOperation = removeOperation;
}

async function runSimulation() {
    const rawInit = pdrawDom.txtInit.value;
    // Simple CSV parser
    const initialValues = rawInit.split(',').map(s => {
        const val = s.trim();
        if (!isNaN(val) && val !== '') return Number(val);
        return val;
    }).filter(v => v !== '');

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
            <div class="font-mono text-sm text-slate-300">
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
                    <span class="font-bold text-green-400 text-sm font-mono">${step.index}. ${step.operation}</span>
                    <span class="text-xs bg-slate-900 px-2 py-1 rounded text-slate-400 border border-slate-700">
                        ${step.complexity}
                    </span>
                </div>
                
                <div class="font-mono text-sm text-white mb-2 p-2 bg-slate-900 rounded border border-slate-700/50">
                    ${step.print_output}
                </div>
                
                <div class="text-xs text-slate-400">
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
    container.className = 'w-full h-full p-2';

    if (type === 'stack') {
        // Vertical Stack
        container.className += ' flex flex-col-reverse justify-start items-center gap-1 overflow-y-auto';

        if (items.length === 0) {
            container.innerHTML = '<span class="text-slate-500 text-xs mt-4">Empty Stack</span>';
        } else {
            items.forEach((item, i) => {
                const box = document.createElement('div');
                box.className = 'w-24 h-10 bg-green-900/40 border border-green-500 text-green-100 flex items-center justify-center rounded shadow font-mono text-sm relative shrink-0 transition-all';
                box.innerText = item;

                if (i === items.length - 1) {
                    const lbl = document.createElement('span');
                    lbl.className = 'absolute -right-8 text-[10px] text-green-400 font-bold';
                    lbl.innerText = 'TOP';
                    box.appendChild(lbl);
                }
                container.appendChild(box);
            });
        }
    } else if (type === 'queue') {
        // Horizontal Queue
        container.className += ' flex flex-row justify-start items-center gap-1 overflow-x-auto';

        if (items.length === 0) {
            container.innerHTML = '<span class="text-slate-500 text-xs ml-4">Empty Queue</span>';
        } else {
            items.forEach((item, i) => {
                const box = document.createElement('div');
                box.className = 'min-w-[3rem] h-12 bg-blue-900/40 border border-blue-500 text-blue-100 flex items-center justify-center rounded shadow font-mono text-sm relative shrink-0 transition-all';
                box.innerText = item;

                if (i === 0) {
                    const lbl = document.createElement('span');
                    lbl.className = 'absolute -top-3 text-[9px] text-blue-400 font-bold';
                    lbl.innerText = 'FRONT';
                    box.appendChild(lbl);
                }
                if (i === items.length - 1) {
                    const lbl = document.createElement('span');
                    lbl.className = 'absolute -bottom-3 text-[9px] text-blue-400 font-bold';
                    lbl.innerText = 'REAR';
                    box.appendChild(lbl);
                }
                container.appendChild(box);
            });
        }
    } else {
        // List generic
        container.className += ' flex flex-wrap justify-center content-start gap-2 overflow-auto';
        if (items.length === 0) {
            container.innerHTML = '<span class="text-slate-500 text-xs mt-4">Empty List</span>';
        } else {
            items.forEach((item, i) => {
                const box = document.createElement('div');
                box.className = 'w-12 h-12 bg-slate-800 border border-slate-600 text-slate-200 flex flex-col items-center justify-center rounded font-mono text-sm shadow relative';

                const idxLbl = document.createElement('span');
                idxLbl.className = 'absolute top-0 left-1 text-[8px] text-slate-500';
                idxLbl.innerText = i;

                const valSpan = document.createElement('span');
                valSpan.innerText = item;

                box.appendChild(idxLbl);
                box.appendChild(valSpan);
                container.appendChild(box);
            });
        }
    }

    pdrawDom.divDiagram.appendChild(container);
}
