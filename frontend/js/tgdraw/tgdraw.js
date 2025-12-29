const TG_API_URL = "http://127.0.0.1:8000/api/tg";

const tgState = {
    mode: 'tree',
    tree: { type: 'bst', rootId: null, data: null },
    graph: { type: 'directed', weighted: false, data: null },
    animation: { active: false, steps: [], speed: 1000 }
};

const tgDom = { dashboard: null, sidebarContent: null, canvas: null };

function initTGDraw() {
    tgDom.dashboard = document.getElementById('tgdraw-dashboard');
    tgDom.sidebarContent = tgDom.dashboard.querySelector('.col-span-3');
    tgDom.canvas = document.getElementById('tgdraw-canvas');
    renderSidebar();
}

function renderSidebar() {
    const html = `
        <h3 class="text-base font-bold text-amber-500 uppercase tracking-wider mb-1">TGDraw Lab</h3>
        <p class="text-xs text-slate-500 mb-4">Tree & Graph Visualizer</p>
        <div class="flex space-x-2 mb-6">
            <button onclick="tgSwitchMode('tree')" class="flex-1 py-1.5 text-xs font-bold rounded transition-colors ${tgState.mode === 'tree' ? 'bg-amber-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white border border-slate-700'}"><i class="fa-solid fa-tree mr-2"></i>Trees</button>
            <button onclick="tgSwitchMode('graph')" class="flex-1 py-1.5 text-xs font-bold rounded transition-colors ${tgState.mode === 'graph' ? 'bg-amber-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white border border-slate-700'}"><i class="fa-solid fa-circle-nodes mr-2"></i>Graphs</button>
        </div>
        <div id="tg-controls" class="space-y-4 animate-fade-in custom-scrollbar overflow-y-auto max-h-[70vh]">
            ${tgState.mode === 'tree' ? getTreeControls() : getGraphControls()}
        </div>`;
    tgDom.sidebarContent.innerHTML = html;
}

function getTreeControls() {
    const currentType = tgState.tree.type || 'bst';
    return `<div>
            <label class="text-xs text-slate-400 block mb-1 font-semibold">Tree Type</label>
            <select id="tree-type" onchange="tgUpdateTreeType(this.value)" ${tgState.tree.rootId ? 'disabled' : ''} class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded p-2 focus:border-amber-500 focus:outline-none">
                <option value="generic" ${currentType === 'generic' ? 'selected' : ''}>Generic Tree</option>
                <option value="binary" ${currentType === 'binary' ? 'selected' : ''}>Binary Tree</option>
                <option value="bst" ${currentType === 'bst' ? 'selected' : ''}>Binary Search Tree (BST)</option>
                <option value="avl" ${currentType === 'avl' ? 'selected' : ''}>AVL Tree</option>
            </select>
            ${tgState.tree.rootId ? '<p class="text-[10px] text-amber-500/80 mt-1">Reset tree to change type</p>' : ''}
        </div>
        <div id="tree-actions" class="p-4 bg-slate-800/50 rounded border border-slate-700/50 mt-4">
             ${!tgState.tree.rootId ? `
                <h4 class="text-xs font-bold text-slate-300 mb-2 uppercase">Start New Tree</h4>
                <div class="space-y-2">
                    <input type="text" id="root-val" placeholder="Root Value" class="w-full bg-slate-900 border border-slate-700 text-white text-xs rounded p-2">
                    <button onclick="tgCreateRoot()" class="w-full py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded">Create Root</button>
                </div>` : `
                <h4 class="text-xs font-bold text-slate-300 mb-2 uppercase">Add Node</h4>
                <div class="space-y-3">
                    <input type="text" id="node-val" placeholder="Value" class="w-full bg-slate-900 border border-slate-700 text-white text-xs rounded p-2">
                    ${tgState.tree.type !== 'generic' ? `<div class="grid grid-cols-2 gap-2 bg-slate-900/50 p-2 rounded border border-slate-700/50"><label class="flex items-center space-x-2"><input type="radio" name="node-dir" value="left" checked class="text-amber-500"><span class="text-xs text-slate-400">Left</span></label><label class="flex items-center space-x-2"><input type="radio" name="node-dir" value="right" class="text-amber-500"><span class="text-xs text-slate-400">Right</span></label></div>` : ''}
                    <input type="text" id="parent-id" placeholder="Parent ID" value="${tgState.tree.rootId}" class="w-full bg-slate-900 border border-slate-700 text-slate-400 text-xs rounded p-2 font-mono" readonly>
                    <button onclick="tgAddNode()" class="w-full py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded"><i class="fa-solid fa-plus mr-1"></i> Add Node</button>
                </div>
                <hr class="border-slate-700/50 my-4">
                <h4 class="text-xs font-bold text-slate-300 mb-2 uppercase">Traversals</h4>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="tgRunTraversal('bfs')" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded">BFS</button>
                    ${tgState.tree.type !== 'generic' ? `<button onclick="tgRunTraversal('inorder')" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded">Inorder</button><button onclick="tgRunTraversal('preorder')" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded">Preorder</button><button onclick="tgRunTraversal('postorder')" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded">Postorder</button>` : ''}
                </div>
                 <div id="traversal-output" class="mt-4 hidden animate-fade-in">
                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Output</p>
                    <div id="t-result-text" class="font-mono text-xs text-amber-400 mt-1 break-words"></div>
                    <div id="t-explanation" class="text-[10px] text-slate-400 mt-2 bg-slate-900/50 p-2 rounded"></div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-700/50"><button onclick="resetTreeState()" class="w-full py-1 text-xs text-red-400 hover:text-red-300 border border-red-500/30 hover:bg-red-900/20 rounded">Reset Tree</button></div>`
        }</div>`;
}

function getGraphControls() {
    const initialized = tgState.graph.data;
    if (!initialized) {
        return `<h4 class="text-xs font-bold text-slate-300 mb-2 uppercase">Graph Setup</h4>
            <div class="space-y-4 p-4 bg-slate-800/50 rounded border border-slate-700/50">
                <div class="flex items-center justify-between"><span class="text-xs text-slate-400">Type</span><div class="space-x-2"><label class="inline-flex items-center"><input type="radio" name="g-type" value="directed" checked class="text-amber-600"><span class="ml-1 text-xs text-slate-300">Directed</span></label><label class="inline-flex items-center"><input type="radio" name="g-type" value="undirected" class="text-amber-600"><span class="ml-1 text-xs text-slate-300">Undirected</span></label></div></div>
                 <div class="flex items-center justify-between"><span class="text-xs text-slate-400">Weighted</span><label class="inline-flex items-center cursor-pointer"><input type="checkbox" id="g-weighted" class="text-amber-600 rounded"><span class="ml-2 text-xs text-slate-300">Yes</span></label></div>
                <button onclick="tgInitGraph()" class="w-full py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded shadow-lg">Initialize Graph</button>
            </div>`;
    }
    return `<div class="p-4 bg-slate-800/50 rounded border border-slate-700/50 space-y-4">
             <div class="flex justify-between items-center border-b border-slate-700/50 pb-2"><span class="text-[10px] text-amber-500 uppercase tracking-wider font-bold">${tgState.graph.type} / ${tgState.graph.weighted ? 'Weighted' : 'Unweighted'}</span><button onclick="tgResetGraph()" class="text-[10px] text-red-400 hover:text-red-300">Reset</button></div>
             <div><h4 class="text-xs font-bold text-slate-300 mb-2 uppercase">Add Vertex</h4><div class="flex space-x-2"><input type="text" id="g-vertex-id" placeholder="ID (e.g. A)" class="w-1/3 bg-slate-900 border border-slate-700 text-white text-xs rounded p-2 uppercase"><input type="text" id="g-vertex-val" placeholder="Value (opt)" class="flex-1 bg-slate-900 border border-slate-700 text-white text-xs rounded p-2"></div><button onclick="tgAddVertex()" class="w-full mt-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-bold rounded border border-slate-600"><i class="fa-solid fa-plus mr-1"></i> Add Vertex</button></div>
             <div class="pt-4 border-t border-slate-700/50"><h4 class="text-xs font-bold text-slate-300 mb-2 uppercase">Add Edge</h4><div class="space-y-2"><div class="flex space-x-2"><input type="text" id="g-edge-src" placeholder="Source" class="w-1/2 bg-slate-900 border border-slate-700 text-white text-xs rounded p-2 uppercase"><input type="text" id="g-edge-tgt" placeholder="Target" class="w-1/2 bg-slate-900 border border-slate-700 text-white text-xs rounded p-2 uppercase"></div>${tgState.graph.weighted ? `<input type="number" id="g-edge-w" placeholder="Weight" class="w-full bg-slate-900 border border-slate-700 text-white text-xs rounded p-2">` : ''}<button onclick="tgAddEdge()" class="w-full py-1 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-bold rounded border border-slate-600"><i class="fa-solid fa-link mr-1"></i> Add Edge</button></div></div>
             <div class="pt-4 border-t border-slate-700/50">
                <h4 class="text-xs font-bold text-slate-300 mb-2 uppercase">Algorithms</h4>
                <div class="flex space-x-2"><input type="text" id="g-algo-start" placeholder="Start Node ID" class="w-full bg-slate-900 border border-slate-700 text-white text-xs rounded p-2 uppercase"></div>
                <div class="grid grid-cols-3 gap-2 mt-2">
                    <button onclick="tgRunGraphAlgo('bfs')" class="px-1 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-[10px] rounded">BFS</button>
                    <button onclick="tgRunGraphAlgo('dfs')" class="px-1 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-[10px] rounded">DFS</button>
                    <button onclick="tgRunGraphAlgo('dijkstra')" class="px-1 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-[10px] rounded">Dijkstra</button>
                </div>
                 <div id="g-traversal-output" class="mt-4 hidden animate-fade-in">
                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">Output</p>
                    <div id="g-result-text" class="font-mono text-xs text-amber-400 mt-1 break-words"></div>
                    <div id="g-explanation" class="text-[10px] text-slate-400 mt-2 bg-slate-900/50 p-2 rounded"></div>
                </div>
             </div>
        </div>`;
}

// --- ACTIONS ---
window.tgSwitchMode = function (mode) { tgState.mode = mode; renderSidebar(); tgDom.canvas.innerHTML = ''; if (mode === 'tree' && tgState.tree.data) renderTreeVisual(); if (mode === 'graph' && tgState.graph.data) renderGraphVisual(); }
window.tgUpdateTreeType = function (val) { tgState.tree.type = val; }
window.resetTreeState = function () { if (confirm("Clear tree?")) { tgState.tree.rootId = null; tgState.tree.data = null; renderSidebar(); tgDom.canvas.innerHTML = ''; } }
window.tgSetParent = function (id) {
    if (tgState.mode === 'tree') { const f = document.getElementById('parent-id'); if (f) { f.value = id; f.classList.add('bg-green-900/20', 'text-green-400'); setTimeout(() => f.classList.remove('bg-green-900/20', 'text-green-400'), 500); } }
    if (tgState.mode === 'graph') {
        // Auto fill edge source then target
        const s = document.getElementById('g-edge-src'); const t = document.getElementById('g-edge-tgt');
        if (s && !s.value) s.value = id; else if (t && !t.value) t.value = id;
    }
}
window.tgCreateRoot = async function () {
    const type = document.getElementById('tree-type').value; const val = document.getElementById('root-val').value;
    if (!val) return;
    try {
        const res = await fetch(`${TG_API_URL}/tree/create-root`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ type, value: val }) });
        const json = await res.json(); if (json.success) { tgState.tree.type = type; tgState.tree.data = json.data; tgState.tree.rootId = json.data.root_id; renderSidebar(); renderTreeVisual(); } else alert(json.error);
    } catch (e) { console.error(e); }
}
window.tgAddNode = async function () {
    const val = document.getElementById('node-val').value; const parentId = document.getElementById('parent-id').value; let direction = null;
    document.getElementsByName('node-dir').forEach(r => { if (r.checked) direction = r.value; });
    if (!val) return;
    try {
        const res = await fetch(`${TG_API_URL}/tree/add-node`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ parent_id: parentId, value: val, direction }) });
        const json = await res.json(); if (json.success) { tgState.tree.data = json.data; document.getElementById('node-val').value = ''; document.getElementById('node-val').focus(); renderTreeVisual(); } else alert(json.error);
    } catch (e) { console.error(e); }
}
window.tgRunTraversal = async function (type) {
    if (!tgState.tree.rootId) return;
    document.getElementById('traversal-output').classList.remove('hidden'); document.getElementById('t-result-text').textContent = 'Thinking...';
    try {
        const res = await fetch(`${TG_API_URL}/tree/traverse`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ type }) });
        const json = await res.json(); if (json.success) animateTraversal(json.data, 't-result-text', 't-explanation', 'tree'); else alert(json.error);
    } catch (e) { console.error(e); }
}

// Graph Actions
window.tgInitGraph = async function () {
    let type = 'directed'; document.getElementsByName('g-type').forEach(r => { if (r.checked) type = r.value; });
    const weighted = document.getElementById('g-weighted').checked;
    try {
        const res = await fetch(`${TG_API_URL}/graph/create`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ directed: (type === 'directed'), weighted }) });
        const json = await res.json(); if (json.success) { tgState.graph.type = type; tgState.graph.weighted = weighted; tgState.graph.data = json.data; renderSidebar(); renderGraphVisual(); } else alert(json.error);
    } catch (e) { console.error(e); }
}
window.tgAddVertex = async function () {
    const id = document.getElementById('g-vertex-id').value.toUpperCase(); const val = document.getElementById('g-vertex-val').value || id;
    if (!id) return;
    try {
        const res = await fetch(`${TG_API_URL}/graph/add-node`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, value: val }) });
        const json = await res.json(); if (json.success) { tgState.graph.data = json.data; document.getElementById('g-vertex-id').value = ''; document.getElementById('g-vertex-id').focus(); renderGraphVisual(); } else alert(json.error);
    } catch (e) { console.error(e); }
}
window.tgAddEdge = async function () {
    const src = document.getElementById('g-edge-src').value.toUpperCase(); const tgt = document.getElementById('g-edge-tgt').value.toUpperCase();
    const wInput = document.getElementById('g-edge-w'); const weight = wInput ? parseFloat(wInput.value) : null;
    if (!src || !tgt) return;
    try {
        const res = await fetch(`${TG_API_URL}/graph/add-edge`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ source: src, target: tgt, weight }) });
        const json = await res.json(); if (json.success) { tgState.graph.data = json.data; renderGraphVisual(); } else alert(json.error);
    } catch (e) { console.error(e); }
}
window.tgResetGraph = function () { if (confirm("Reset graph?")) { tgState.graph.data = null; renderSidebar(); tgDom.canvas.innerHTML = ''; } }
window.tgRunGraphAlgo = async function (algo) {
    const start = document.getElementById('g-algo-start').value.toUpperCase(); if (!start) { alert("Enter Start Node ID"); return; }
    document.getElementById('g-traversal-output').classList.remove('hidden'); document.getElementById('g-result-text').textContent = 'Computing...';
    try {
        const res = await fetch(`${TG_API_URL}/graph/run-algorithm`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ algorithm: algo, start_node: start }) });
        const json = await res.json(); if (json.success) animateTraversal(json.data, 'g-result-text', 'g-explanation', 'graph'); else alert(json.error);
    } catch (e) { console.error(e); }
}

// --- VISUALIZATION ---
function renderTreeVisual() {
    const data = tgState.tree.data; if (!data) return;
    const w = tgDom.canvas.clientWidth || 800; const h = tgDom.canvas.clientHeight || 500;
    const nodes = data.nodes; const rootId = data.root_id;
    const layout = calculateTreeLayout(nodes, rootId, w, h);
    tgState.tree.layout = layout;
    let svg = `<svg id="tg-svg" width="100%" height="100%" viewBox="0 0 ${w} ${h}" xmlns="http://www.w3.org/2000/svg">`;
    svg += renderEdges(layout, nodes); svg += renderNodes(layout, nodes, 'tree'); svg += '</svg>';
    tgDom.canvas.innerHTML = svg;
}
function renderGraphVisual() {
    const data = tgState.graph.data; if (!data) return;
    const w = tgDom.canvas.clientWidth || 800; const h = tgDom.canvas.clientHeight || 500;
    const nodes = data.nodes; const nodeIds = Object.keys(nodes); const count = nodeIds.length;
    const radius = Math.min(w, h) / 3; const cx = w / 2; const cy = h / 2;
    const layout = {};
    nodeIds.forEach((id, i) => { const angle = (2 * Math.PI * i) / count; layout[id] = { x: cx + radius * Math.cos(angle), y: cy + radius * Math.sin(angle) }; });
    let svg = `<svg id="tg-g-svg" width="100%" height="100%" viewBox="0 0 ${w} ${h}" xmlns="http://www.w3.org/2000/svg"><defs><marker id="arrowhead" markerWidth="10" markerHeight="7" refX="28" refY="3.5" orient="auto"><polygon points="0 0, 10 3.5, 0 7" fill="#64748b" /></marker></defs>`;
    svg += renderGraphEdges(layout, data.adjacency_list, tgState.graph.type === 'directed'); svg += renderNodes(layout, nodes, 'graph'); svg += '</svg>';
    tgDom.canvas.innerHTML = svg;
}

function calculateTreeLayout(nodes, rootId, w, h) {
    const positions = {}; function traverse(nodeId, x, y, offsetX) { if (!nodeId) return; positions[nodeId] = { x, y }; const node = nodes[nodeId]; if (node.left) traverse(node.left, x - offsetX, y + 80, offsetX / 1.8); if (node.right) traverse(node.right, x + offsetX, y + 80, offsetX / 1.8); }
    traverse(rootId, w / 2, 50, w / 4); return positions;
}
function renderEdges(layout, nodes) { let html = ''; Object.keys(layout).forEach(id => { const node = nodes[id]; const { x, y } = layout[id];[node.left, node.right].forEach(childId => { if (childId && layout[childId]) { const childPos = layout[childId]; html += `<line x1="${x}" y1="${y}" x2="${childPos.x}" y2="${childPos.y}" stroke="#64748b" stroke-width="2" />`; } }); }); return html; }
function renderGraphEdges(layout, adjList, isDirected) { let html = ''; Object.keys(adjList).forEach(srcId => { if (!layout[srcId]) return; const { x: x1, y: y1 } = layout[srcId]; adjList[srcId].forEach(edge => { const tgtId = edge.target; if (!layout[tgtId]) return; const { x: x2, y: y2 } = layout[tgtId]; const marker = isDirected ? 'marker-end="url(#arrowhead)"' : ''; html += `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="#64748b" stroke-width="2" ${marker} />`; if (edge.weight !== null) { const midX = (x1 + x2) / 2; const midY = (y1 + y2) / 2; html += `<text x="${midX}" y="${midY}" fill="#94a3b8" font-size="10" font-weight="bold" dy="-5">${edge.weight}</text>`; } }); }); return html; }
function renderNodes(layout, nodes, type) { let html = ''; Object.keys(layout).forEach(id => { const node = nodes[id]; const { x, y } = layout[id]; html += `<g id="node-g-${id}" class="cursor-pointer hover:opacity-80 transition-opacity" onclick="tgSetParent('${id}')"><circle id="node-c-${id}" cx="${x}" cy="${y}" r="20" fill="#1e293b" stroke="#d97706" stroke-width="2" class="transition-colors duration-300" /><text x="${x}" y="${y}" dy="5" text-anchor="middle" fill="#d97706" font-size="12" font-family="monospace" font-weight="bold" pointer-events="none">${node.value}</text><title>ID: ${id}</title></g>`; }); return html; }

async function animateTraversal(steps, outTextId, outExplId, mode) {
    if (mode === 'tree') renderTreeVisual(); else renderGraphVisual();
    const outText = document.getElementById(outTextId); const outExpl = document.getElementById(outExplId);
    let visited = []; outText.textContent = '';
    for (const step of steps) {
        const { node_id, action, description } = step; const circle = document.getElementById(`node-c-${node_id}`);
        outExpl.textContent = description;
        if (action === 'visit') {
            if (circle) { circle.setAttribute('fill', '#d97706'); circle.setAttribute('stroke', '#fff'); }
            // Get value from appropriate state
            const val = mode === 'tree' ? tgState.tree.data.nodes[node_id].value : tgState.graph.data.nodes[node_id].value;
            visited.push(val); outText.textContent = visited.join(' -> ');
        } else if (action === 'enqueue' || action === 'explore') { if (circle) circle.setAttribute('stroke', '#3b82f6'); }
        await new Promise(r => setTimeout(r, 800));
        if ((action === 'enqueue' || action === 'explore') && circle) { circle.setAttribute('stroke', '#d97706'); }
    }
    outExpl.textContent = "Traversal Complete.";
}

window.initTGDraw = initTGDraw;
