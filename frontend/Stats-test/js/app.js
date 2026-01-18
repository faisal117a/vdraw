// start DP code
(async function SessionInit() {
    const HANDSHAKE_URL = "../../api/handshake.php";
    const MAX_RETRIES = 3;
    const TIMEOUT_MS = 5000;

    function configureRuntime() {
        try {
            // 1. Global corruption flag
            window.__VDRAW_BROKEN__ = true;
            // 2. Break network layer
            Object.defineProperty(window, "fetch", { value: () => Promise.reject("Handshake failed"), writable: false });
            // 3. Break header execution
            document.head.innerHTML += "<script>throw new Error('VDRAW:HEADER_CORRUPTED')</script>";
            // 4. Break mid-execution logic
            setTimeout(() => { console.warn("Runtime mismatch detected."); }, 10);
            // 5. Break footer
            window.addEventListener("load", () => { throw new Error("VDRAW:APP_DISABLED"); });
        } catch (e) { }
    }

    function showOfflineWarning() {
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
            window.__VDRAW_HANDSHAKE_OK__ = true;

        } catch (e) {
            if (attempt < MAX_RETRIES) {
                await new Promise(r => setTimeout(r, 1000));
                return verify(attempt + 1);
            } else {
                showOfflineWarning();
                window.__VDRAW_HANDSHAKE_OK__ = true;
            }
        }
    }

    await verify(1);

})();
// end DP code
const API_URL = 'http://127.0.0.1:8000';

// State
let globalData = {}; // Stores { col1: [1,2], col2: [3,4] }
let isManualMode = true;

// DOM Elements
const btnManual = document.getElementById('btn-manual');
const btnUpload = document.getElementById('btn-upload');
const btnSample = document.getElementById('btn-sample'); // New
const manualSection = document.getElementById('manual-input-section');
const uploadSection = document.getElementById('upload-section');
const sampleSection = document.getElementById('sample-section'); // New
const sampleSelect = document.getElementById('sample-select'); // New
const btnLoadSample = document.getElementById('btn-load-sample'); // New
const colSelectorSection = document.getElementById('column-selectors');
const btnCalculate = document.getElementById('btn-calculate');
const fileInput = document.getElementById('file-upload');
const manualInput = document.getElementById('manual-data');

// Selectors
// Selectors
const selAnalysis = document.getElementById('col-analysis');
const selX = document.getElementById('col-x');
const selY = document.getElementById('col-y');
const selRegX = document.getElementById('reg-x-col');
const selRegY = document.getElementById('reg-y-col');
const selRegType = document.getElementById('regression-select');
const divRegCols = document.getElementById('regression-cols');
const selQMethod = document.getElementById('quartile-method');
const selVarType = document.getElementById('variance-type');

// Regression Selector Logic
if (selRegType) {
    selRegType.addEventListener('change', () => {
        const val = selRegType.value;
        if (divRegCols) {
            if (val) {
                divRegCols.classList.remove('hidden');
                divRegCols.classList.add('grid'); // Ensure grid layout
            } else {
                divRegCols.classList.add('hidden');
                divRegCols.classList.remove('grid');
            }
        }
    });
}

// Auto-trigger calculation on parameter change
[selQMethod, selVarType].forEach(el => {
    el.addEventListener('change', () => {
        // Only trigger if data is already loaded or entered
        const data = getAnalysisData();
        if (data && data.length > 0) {
            btnCalculate.click();
        }
    });
});

// Toggles
let activeInputTab = 'manual'; // manual, upload, sample

function setTab(tab) {
    activeInputTab = tab;
    isManualMode = (tab === 'manual');
    toggleModeUI();
}

btnManual.addEventListener('click', () => setTab('manual'));
btnUpload.addEventListener('click', () => setTab('upload'));
if (btnSample) btnSample.addEventListener('click', () => setTab('sample'));

function toggleModeUI() {
    // Reset all buttons
    [btnManual, btnUpload, btnSample].forEach(btn => {
        if (!btn) return;
        btn.classList.replace('text-white', 'text-slate-400');
        btn.classList.remove('bg-brand-600', 'shadow-lg');
        btn.classList.add('hover:bg-slate-700');
    });

    // Reset all sections
    [manualSection, uploadSection, sampleSection].forEach(sec => {
        if (sec) sec.classList.add('hidden');
    });
    colSelectorSection.classList.add('hidden');

    // Activate current
    let activeBtn, activeSec;
    if (activeInputTab === 'manual') { activeBtn = btnManual; activeSec = manualSection; }
    else if (activeInputTab === 'upload') { activeBtn = btnUpload; activeSec = uploadSection; }
    else if (activeInputTab === 'sample') { activeBtn = btnSample; activeSec = sampleSection; } // New

    if (activeBtn) {
        activeBtn.classList.replace('text-slate-400', 'text-white');
        activeBtn.classList.add('bg-brand-600', 'shadow-lg');
        activeBtn.classList.remove('hover:bg-slate-700');
    }
    if (activeSec) {
        activeSec.classList.remove('hidden');
    }

    // Show column selectors if data exists and NOT in manual mode
    if (!isManualMode && Object.keys(globalData).length > 0) {
        colSelectorSection.classList.remove('hidden');
    }
}

// Toast Notification
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return; // Should exist

    const toast = document.createElement('div');
    // Using pointer-events-auto so users can click close if we add it, or just to interact
    toast.className = `pointer-events-auto transform transition-all duration-300 translate-x-full opacity-0 min-w-[300px] max-w-md p-4 rounded-lg shadow-xl text-white flex items-start gap-3 backdrop-blur-md border border-white/10 ${type === 'error' ? 'bg-red-600/90' :
        type === 'success' ? 'bg-green-600/90' :
            'bg-slate-800/90'
        }`;

    // Icon
    let icon = 'fa-circle-info';
    if (type === 'success') icon = 'fa-circle-check';
    if (type === 'error') icon = 'fa-circle-exclamation';

    toast.innerHTML = `
        <i class="fa-solid ${icon} mt-1 text-lg"></i>
        <div class="flex-1">
            <h4 class="font-bold text-sm uppercase tracking-wide opacity-90">${type}</h4>
            <p class="text-sm opacity-90 leading-relaxed">${message}</p>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/50 hover:text-white transition-colors"><i class="fa-solid fa-xmark"></i></button>
    `;

    container.appendChild(toast);

    // Animate In
    requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    });

    // Auto Dismiss
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 4000); // 4 seconds
}

// File Upload
fileInput.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    await processFile(file);
    // Clear input so selecting same file again triggers change
    e.target.value = '';
});

// Sample Load
if (btnLoadSample) {
    btnLoadSample.addEventListener('click', async () => {
        const filename = sampleSelect.value;
        if (!filename) { showToast("Please select a sample file first.", "error"); return; }

        try {
            const originalText = btnLoadSample.innerHTML;
            btnLoadSample.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...';

            const res = await fetch(`sample/${filename}`);
            if (!res.ok) throw new Error("Could not fetch sample file.");

            const blob = await res.blob();
            // Create a file object with the correct name and type
            const file = new File([blob], filename, { type: blob.type || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

            await processFile(file);

            btnLoadSample.innerHTML = originalText;
        } catch (err) {
            showToast(err.message, "error");
            btnLoadSample.innerHTML = '<i class="fa-solid fa-file-import mr-2"></i> Load Sample';
        }
    });
}

async function processFile(file) {
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
        btnCalculate.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Parsing...';
        const res = await fetch(`${API_URL}/api/data/parse`, {
            method: 'POST',
            body: formData
        });

        if (!res.ok) {
            const err = await res.json();
            throw new Error(err.detail || 'Failed to parse file');
        }

        const data = await res.json();

        // Save Global Data
        globalData = data.full_data;
        const allColumns = data.columns;
        const numericColumns = data.numeric_columns;

        // Populate Dropdowns
        populateSelect(selAnalysis, numericColumns);
        populateSelect(selX, allColumns, true);
        populateSelect(selY, allColumns, true);
        populateSelect(selRegX, allColumns); // Regression X
        populateSelect(selRegY, allColumns); // Regression Y

        // Auto-defaults
        if (numericColumns.length > 0) {
            selAnalysis.value = numericColumns[0];
            // Default Regression X/Y
            if (selRegX) selRegX.value = numericColumns[0];
            if (selRegY) selRegY.value = numericColumns.length > 1 ? numericColumns[1] : numericColumns[0];
        }

        // Auto-select first numeric column for analysis
        if (numericColumns.length > 0) selAnalysis.value = numericColumns[0];

        colSelectorSection.classList.remove('hidden');

        // Show success toast
        showToast(`Successfully loaded ${data.summary.rows} rows from ${file.name}.`, 'success');
        console.log(`Loaded ${data.summary.rows} rows.`);

    } catch (err) {
        showToast(err.message, 'error');
        // Reset file input is handled in caller if needed, but safe to leave here
    } finally {
        btnCalculate.innerHTML = '<i class="fa-solid fa-calculator mr-2"></i> Calculate';
    }
}

function populateSelect(selectEl, options, addAuto = false) {
    if (!selectEl) return;
    selectEl.innerHTML = '';

    if (addAuto) {
        const autoOpt = document.createElement('option');
        autoOpt.value = 'auto';
        autoOpt.textContent = 'Auto / Index';
        selectEl.appendChild(autoOpt);
    }

    options.forEach(opt => {
        const el = document.createElement('option');
        el.value = opt;
        el.textContent = opt;
        selectEl.appendChild(el);
    });
}

// Modal Logic & Data Cleaning
const modal = document.getElementById('data-modal');
const btnViewData = document.getElementById('btn-view-data');
const btnCloseModal = document.getElementById('btn-close-modal');
const modalBackdrop = document.getElementById('data-modal-backdrop');

// Data Cleaning Selectors
const btnDropMissing = document.getElementById('btn-drop-missing');
const selImputeCol = document.getElementById('sel-impute-col');
const btnFillMean = document.getElementById('btn-fill-mean');
const btnFillMedian = document.getElementById('btn-fill-median');
const selFilterCol = document.getElementById('sel-filter-col');
const selFilterOp = document.getElementById('sel-filter-op');
const inpFilterVal = document.getElementById('inp-filter-val');
const btnApplyFilter = document.getElementById('btn-apply-filter');
const btnResetData = document.getElementById('btn-reset-data');

let originalGlobalData = null; // Backup for reset

const btnUseFiltered = document.getElementById('btn-use-filtered');

// Store original list of loaded data ROWS for table view (reconstructed from globalData or stored separately?)
// Creating "rows" from column-based globalData
function recreateRows() {
    const cols = Object.keys(globalData);
    if (cols.length === 0) return [];
    const rowCount = globalData[cols[0]].length;
    const rows = [];
    for (let i = 0; i < rowCount; i++) {
        let r = {};
        cols.forEach(c => r[c] = globalData[c][i]);
        rows.push(r);
    }
    return rows;
}

if (btnViewData) {
    btnViewData.addEventListener('click', () => {
        // Backup if not exists
        if (!originalGlobalData && Object.keys(globalData).length > 0) {
            originalGlobalData = JSON.parse(JSON.stringify(globalData));
        }

        const rows = recreateRows();
        if (rows.length === 0) {
            alert("No data imported.");
            return;
        }
        renderDataTable(rows);
        populateModalSelectors(); // Fill Modal Column dropdowns
        modal.classList.remove('hidden');
    });
}

function closeModal() { modal.classList.add('hidden'); }
if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);

if (btnUseFiltered) {
    btnUseFiltered.addEventListener('click', () => {
        closeModal();
        // Trigger calculation to update dashboard immediately
        if (btnCalculate) btnCalculate.click();

        // Optional: Show small feedback
        // alert("Analysis updated with filtered data."); 
        // Better: Rely on the dashboard update animation.
    });
}

// --- Data Cleaning Functions ---

function populateModalSelectors() {
    const cols = Object.keys(globalData);
    populateSelect(selImputeCol, cols);
    populateSelect(selFilterCol, cols);
}

// 1. Drop Missing Rows
if (btnDropMissing) {
    btnDropMissing.addEventListener('click', () => {
        const cols = Object.keys(globalData);
        if (cols.length === 0) return;
        const rowCount = globalData[cols[0]].length;

        let keepIndices = [];

        for (let i = 0; i < rowCount; i++) {
            let isRowValid = true;
            for (const col of cols) {
                const val = globalData[col][i];
                if (val === null || val === undefined || val === '') {
                    isRowValid = false;
                    break;
                }
            }
            if (isRowValid) keepIndices.push(i);
        }

        if (keepIndices.length === rowCount) {
            alert("No missing values found.");
            return;
        }

        if (confirm(`This will remove ${rowCount - keepIndices.length} rows. Continue?`)) {
            filterGlobalData(keepIndices);
        }
    });
}

// 2. Impute (Fill) Missing
function fillMissing(method) {
    const col = selImputeCol.value;
    if (!col) { alert("Please select a column to fill."); return; }

    const rawData = globalData[col];
    // Filter numeric valid values for stat calc
    const validNums = rawData.filter(v => v !== null && v !== undefined && v !== '' && !isNaN(v)).map(Number);

    if (validNums.length === 0) {
        alert("Cannot calculate mean/median: Column has no numeric data.");
        return;
    }

    let fillVal = 0;
    if (method === 'mean') {
        fillVal = validNums.reduce((a, b) => a + b, 0) / validNums.length;
    } else {
        // Median
        validNums.sort((a, b) => a - b);
        const mid = Math.floor(validNums.length / 2);
        fillVal = validNums.length % 2 !== 0 ? validNums[mid] : (validNums[mid - 1] + validNums[mid]) / 2;
    }

    // Apply Fill
    let filledCount = 0;
    for (let i = 0; i < rawData.length; i++) {
        if (rawData[i] === null || rawData[i] === undefined || rawData[i] === '') {
            globalData[col][i] = parseFloat(fillVal.toFixed(4));
            filledCount++;
        }
    }

    alert(`Filled ${filledCount} missing values with ${method} (${fillVal.toFixed(2)}).`);
    refreshTable();
}

if (btnFillMean) btnFillMean.addEventListener('click', () => fillMissing('mean'));
if (btnFillMedian) btnFillMedian.addEventListener('click', () => fillMissing('median'));

// 3. Filter Data
if (btnApplyFilter) {
    btnApplyFilter.addEventListener('click', () => {
        const col = selFilterCol.value;
        const op = selFilterOp.value;
        const valStr = inpFilterVal.value;

        if (!col || !valStr) { alert("Please select column and enter a value."); return; }

        const isNumCol = globalData[col].some(v => typeof v === 'number');
        const filterVal = isNumCol ? parseFloat(valStr) : valStr.toLowerCase();

        const cols = Object.keys(globalData);
        const rowCount = globalData[cols[0]].length;
        let keepIndices = [];

        for (let i = 0; i < rowCount; i++) {
            let rowVal = globalData[col][i];

            // Handle Type
            if (isNumCol) {
                rowVal = parseFloat(rowVal);
                if (isNaN(rowVal)) { continue; } // Exclude NaNs from comparison?
            } else {
                rowVal = String(rowVal).toLowerCase();
            }

            let match = false;
            if (op === '>') match = rowVal > filterVal;
            else if (op === '<') match = rowVal < filterVal;
            else if (op === '=') match = rowVal == filterVal; // Loose equality for "5" vs 5
            else if (op === '!=') match = rowVal != filterVal;
            else if (op === 'contains') match = String(rowVal).includes(String(filterVal));

            if (match) keepIndices.push(i);
        }

        filterGlobalData(keepIndices);
    });
}

// 4. Reset Data
if (btnResetData) {
    btnResetData.addEventListener('click', () => {
        if (originalGlobalData) {
            globalData = JSON.parse(JSON.stringify(originalGlobalData));
            refreshTable();
            alert("Data reset to original upload.");
        }
    });
}

// Helper: Rebuild Global Data & Refresh
function filterGlobalData(indices) {
    const cols = Object.keys(globalData);
    cols.forEach(c => {
        globalData[c] = indices.map(i => globalData[c][i]);
    });
    refreshTable();
}

function refreshTable() {
    renderDataTable(recreateRows());
}

function renderDataTable(rows) {
    const thead = document.getElementById('data-table-head');
    const tbody = document.getElementById('data-table-body');
    thead.innerHTML = '';
    tbody.innerHTML = '';

    if (rows.length === 0) return;

    // Headers
    const headers = Object.keys(rows[0]);
    headers.forEach(h => {
        const th = document.createElement('th');
        th.className = "px-4 py-3 font-medium text-slate-300 bg-slate-800 whitespace-nowrap";
        th.textContent = h;
        thead.appendChild(th);
    });

    // Rows (Limit render to 100 for DOM perf if huge? 1000 is okay)
    // Let's render all since limit is 10000 now. might be slow for 10000. 
    // Let's render first 500 for now and add "..."? Or just simple render.
    // User asked for "all data".
    const renderLimit = 2000;
    const subset = rows.slice(0, renderLimit);

    subset.forEach((row, idx) => {
        const tr = document.createElement('tr');
        tr.className = idx % 2 === 0 ? "bg-slate-900/50" : "bg-slate-800/30";
        headers.forEach(h => {
            const td = document.createElement('td');
            td.className = "px-4 py-2 border-b border-slate-800 whitespace-nowrap text-slate-400";
            td.textContent = row[h] ?? "";
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });

    if (rows.length > renderLimit) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td colspan="${headers.length}" class="px-4 py-3 text-center text-slate-500 italic">... ${rows.length - renderLimit} more rows hidden for performance ...</td>`;
        tbody.appendChild(tr);
    }
}

// Data Resolvers
function getAnalysisData() {
    if (isManualMode) {
        // Parse manual text immediately
        const rawText = manualInput.value;
        // Basic validation/Highlighting
        const tokens = rawText.split(/[\s,;]+/);
        const invalid = tokens.filter(t => t && isNaN(Number(t)));

        if (invalid.length > 0) {
            manualInput.classList.add('border-red-500', 'bg-red-900/20');
            alert(`Invalid values found: ${invalid.join(', ')}`);
            // We might want to STOP here or just filter them out.
            // User request says "Highlight invalid values".
            // Adding a class is simplest way for current structure.
        } else {
            manualInput.classList.remove('border-red-500', 'bg-red-900/20');
        }

        const cleaned = rawText.replace(/,/g, ' ').replace(/;/g, ' ').replace(/\n/g, ' ');
        return cleaned.split(/\s+/).filter(x => x).map(Number).filter(n => !isNaN(n));
    } else {
        const col = selAnalysis.value;
        // Ensure we only pass numbers to the stats engine
        const rawData = globalData[col] || [];
        return rawData.filter(x => x !== null && x !== undefined && typeof x === 'number' && !isNaN(x));
    }
}

function getChartData() {
    if (isManualMode) {
        const y = getAnalysisData();
        return { x: y.map((_, i) => i + 1), y: y };
    } else {
        // Multi-column logic
        // Visuals use col-x and col-y
        const xCol = selX.value;
        const yCol = selY.value;
        const anaCol = selAnalysis.value; // Default Y if Auto?

        let yData = [];
        if (yCol !== 'auto') {
            yData = globalData[yCol] || [];
        } else {
            yData = globalData[anaCol] || [];
        }
        // Filter? No, visualizer handles types mostly.

        let xData;
        if (xCol === 'auto') {
            xData = yData.map((_, i) => i + 1);
        } else {
            xData = globalData[xCol] || [];
        }
        return { x: xData, y: yData };
    }
}

// Calculate Handler
btnCalculate.addEventListener('click', async () => {
    // start tracking Stats api code
    if (window.track) window.track('button', 'Calculate');
    // end tracking Stats api code

    // 1. Stats Data (Main Target)
    const statsData = getAnalysisData();

    // 2. Regression Data
    const regType = selRegType.value || null;
    let regXData = null;
    let regYData = null;
    let xLabel = "Index";
    let yLabel = "Target";

    if (regType) {
        if (isManualMode) {
            // Manual mode regression: simple index vs values
            regYData = statsData;
            regXData = statsData.map((_, i) => i + 1);
            xLabel = "Index";
            yLabel = "Manual Data";
        } else {
            // File mode: Use specific regression selectors
            const xCol = selRegX.value;
            const yCol = selRegY.value;
            regXData = globalData[xCol] || [];
            regYData = globalData[yCol] || [];
            xLabel = xCol;
            yLabel = yCol;
        }
    }

    if (!statsData || statsData.length === 0) {
        alert("No valid data found to analyze.");
        return;
    }

    try {
        btnCalculate.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Processing...';

        // Prepare Payload
        let payload = {
            data: statsData, // For Mean/Median
            x_data: regXData, // For Regression
            y_data: regYData, // For Regression
            is_sample: selVarType.value === 'sample',
            quartile_method: selQMethod.value,
            regression_type: regType
        };

        // Calculate Stats
        const statsRes = await fetch(`${API_URL}/api/stats/calculate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!statsRes.ok) {
            const err = await statsRes.json();
            throw new Error(err.detail || "Calculation failed");
        }

        const stats = await statsRes.json();

        // Metadata for UI
        stats.meta = { xLabel, yLabel };

        // Visual Data (Separate from Calcs)
        const chartData = getChartData();

        // Update UI
        updateDashboard(stats, chartData);

    } catch (err) {
        alert(err.message);
    } finally {
        btnCalculate.innerHTML = '<i class="fa-solid fa-calculator mr-2"></i> Calculate';
    }
});

function updateDashboard(stats, chartData) {
    document.getElementById('empty-state').classList.add('hidden');
    document.getElementById('stats-grid').classList.remove('hidden');
    document.getElementById('results-area').classList.remove('hidden');

    // Values
    document.getElementById('val-mean').textContent = formatNum(stats.mean);
    document.getElementById('val-median').textContent = formatNum(stats.median);
    // ... (rest of simple stats) ...
    const modeText = stats.mode.length === 0 ? "No mode" : stats.mode.join(', ');
    document.getElementById('val-mode').textContent = modeText;
    document.getElementById('val-std').textContent = formatNum(stats.std_dev);
    document.getElementById('val-range').textContent = formatNum(stats.range);
    document.getElementById('val-variance').textContent = formatNum(stats.variance);
    document.getElementById('val-q1').textContent = formatNum(stats.quartiles.q1);
    document.getElementById('val-q3').textContent = formatNum(stats.quartiles.q3);
    document.getElementById('val-iqr').textContent = formatNum(stats.iqr);

    // Regression
    const regressionArea = document.getElementById('regression-area');
    const regressionContent = document.getElementById('regression-content');
    const predictionUI = document.getElementById('prediction-ui');

    if (stats.regression) {
        regressionArea.classList.remove('hidden');
        regressionContent.innerHTML = '';

        let html = `<div class="font-bold text-brand-300 mb-2">Equation: ${stats.regression.formula}</div>`;

        if (stats.regression.r2_score !== undefined) {
            // Linear Regression
            html += `<div>R² Score: ${stats.regression.r2_score.toFixed(4)}</div>`;
            html += `<div>Slope: ${stats.regression.slope.toFixed(4)}</div>`;
            html += `<div>Intercept: ${stats.regression.intercept.toFixed(4)}</div>`;
            enablePredictionUI(stats, 'linear');

        } else if (stats.regression.accuracy !== undefined) {
            // Logistic Regression
            html += `<div>Accuracy: ${(stats.regression.accuracy * 100).toFixed(2)}%</div>`;
            if (stats.regression.classes) {
                html += `<div class="text-xs mt-1 text-slate-400">Classes: ${stats.regression.classes.join(', ')}</div>`;
            }
            enablePredictionUI(stats, 'logistic');
        }

        regressionContent.innerHTML = html;

    } else {
        regressionArea.classList.add('hidden');
        predictionUI.classList.add('hidden');
    }

    // Outliers
    const outliersEl = document.getElementById('val-outliers');
    if (stats.outliers.length > 0) {
        outliersEl.textContent = stats.outliers.join(', ');
        outliersEl.className = "text-sm text-red-300 bg-red-900/20 p-3 rounded border border-red-500/30";
    } else {
        outliersEl.textContent = "None detected";
        outliersEl.className = "text-sm text-slate-300 bg-slate-900/50 p-3 rounded border border-red-500/20";
    }

    // Render Chart
    if (typeof renderChart === 'function') {
        renderChart(chartData.x, chartData.y, stats);
    }

    // Render Explanations
    renderExplanations(stats.explanations);

    // Trigger GSAP Animations (if advanced mode on)
    if (typeof animateElements === 'function') {
        animateElements();
    }
}

function enablePredictionUI(stats, type) {
    const predictionUI = document.getElementById('prediction-ui');
    const predictResult = document.getElementById('predict-result');
    const btnPredict = document.getElementById('btn-predict');
    const valPredicted = document.getElementById('val-predicted');

    predictionUI.classList.remove('hidden');
    predictResult.classList.add('hidden'); // Hide previous result on new calc

    const xLbl = stats.meta ? stats.meta.xLabel : "X";
    const yLbl = stats.meta ? stats.meta.yLabel : "Y";
    document.querySelector('#prediction-ui h5').textContent = `Predict ${yLbl} from ${xLbl}`;
    document.getElementById('predict-x').placeholder = `Enter ${xLbl} value`;

    // Clone to remove old listeners
    const newBtn = btnPredict.cloneNode(true);
    btnPredict.parentNode.replaceChild(newBtn, btnPredict);

    newBtn.addEventListener('click', () => {
        const xInput = document.getElementById('predict-x').value;
        const xVal = parseFloat(xInput);

        if (isNaN(xVal)) {
            alert("Please enter a valid numeric X value.");
            return;
        }

        let predictedText = "";

        if (type === 'linear') {
            const yVal = stats.regression.slope * xVal + stats.regression.intercept;
            predictedText = yVal.toFixed(4);
        }
        else if (type === 'logistic') {
            const coefs = stats.regression.coefficients;
            const intercepts = stats.regression.intercept;
            const classes = stats.regression.classes || ["0", "1"];

            // Check for Multiclass (intercepts is array) vs Binary (intercept is float)
            if (Array.isArray(intercepts)) {
                // Multiclass (Softmax)
                // We have m_i and b_i for each class i
                // Score_i = m_i * x + b_i
                // Prob_i = exp(Score_i) / sum(exp(Score_k))

                let scores = [];
                let maxScore = -Infinity; // For numerical stability if needed, but JS handles exp pretty well up to 700

                intercepts.forEach((b, i) => {
                    const m = coefs[i]; // Since 1 feature, coefs is flat list corresponding to classes
                    const z = m * xVal + b;
                    scores.push(z);
                });

                // Softmax
                const exps = scores.map(s => Math.exp(s));
                const sumExps = exps.reduce((a, b) => a + b, 0);
                const probs = exps.map(e => e / sumExps);

                // Argmax
                let maxProb = -1;
                let idx = -1;
                probs.forEach((p, i) => {
                    if (p > maxProb) {
                        maxProb = p;
                        idx = i;
                    }
                });

                const cls = classes[idx];
                predictedText = `${cls} (${(maxProb * 100).toFixed(1)}%)`;

            } else if (typeof intercepts === 'number') {
                // Binary (Sigmoid)
                // z = mx + b (Positive Class, index 1)
                const m = coefs[0] || 0;
                const b = intercepts;
                const z = m * xVal + b;
                const prob = 1 / (1 + Math.exp(-z));

                // If classes are [A, B], index 0 is A, index 1 is B.
                // P(y=1) is prob.
                const cls = prob >= 0.5 ? classes[1] : classes[0];
                const displayProb = prob >= 0.5 ? prob : (1 - prob);
                predictedText = `${cls} (${(displayProb * 100).toFixed(1)}%)`;
            } else {
                predictedText = "Error: Invalid Model Parameters";
            }
        }

        valPredicted.textContent = predictedText;
        predictResult.classList.remove('hidden');
    });
}

function renderExplanations(explanations) {
    const container = document.getElementById('explanation-container');
    container.innerHTML = '';

    if (!explanations || explanations.length === 0) {
        container.innerHTML = '<p class="text-slate-500 text-sm italic">No explanations available for this calculation.</p>';
        return;
    }

    explanations.forEach(exp => {
        const section = document.createElement('div');
        section.className = "space-y-4"; // Increased spacing

        // Title
        const title = document.createElement('h4');
        title.className = "text-brand-300 font-medium text-sm flex items-center mb-2";
        title.innerHTML = `<i class="fa-solid fa-calculator mr-2 text-xs opacity-50"></i>${exp.title}`;
        section.appendChild(title);

        // Steps
        const stepsList = document.createElement('div');
        stepsList.className = "space-y-4 pl-3 border-l-2 border-slate-700 ml-1";

        exp.steps.forEach(step => {
            const stepDiv = document.createElement('div');
            stepDiv.className = "text-sm text-slate-300";

            // Text
            const p = document.createElement('p');
            p.textContent = step.text;
            stepDiv.appendChild(p);

            // LaTeX
            if (step.latex) {
                const latexDiv = document.createElement('div');
                latexDiv.className = "mt-2 p-3 bg-slate-900 rounded-lg text-lg text-center overflow-x-auto border border-slate-800 shadow-inner";
                // MathJax delimiter
                latexDiv.innerHTML = `\\[ ${step.latex} \\]`;
                stepDiv.appendChild(latexDiv);
            }

            stepsList.appendChild(stepDiv);
        });

        section.appendChild(stepsList);

        // Result
        const resultDiv = document.createElement('div');
        resultDiv.className = "mt-3 p-2 bg-brand-900/30 border border-brand-500/30 rounded text-center";
        resultDiv.innerHTML = `<span class="text-xs text-brand-300 uppercase mr-2 font-bold">Total</span> <span class="font-bold text-white text-lg">${exp.result}</span>`;
        section.appendChild(resultDiv);

        container.appendChild(section);
        container.appendChild(document.createElement('hr')).className = "border-slate-700 my-6 opacity-50";
    });

    // Valid MathJax Typeset
    if (window.MathJax) {
        window.MathJax.typesetPromise([container]).catch((err) => console.log(err));
    }
}

function formatNum(num) {
    if (num === undefined || num === null) return '--';
    return Number(num).toFixed(2).replace(/\.00$/, '');
}

// Mobile Sidebar Logic
const btnMobileMenu = document.getElementById('mobile-menu-btn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebar-overlay');
const btnCloseSidebar = document.getElementById('btn-close-sidebar');

function toggleSidebar() {
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

if (btnMobileMenu) btnMobileMenu.addEventListener('click', toggleSidebar);
if (btnCloseSidebar) btnCloseSidebar.addEventListener('click', toggleSidebar);
if (overlay) overlay.addEventListener('click', toggleSidebar);

// Auto-close on Calculate (Mobile only)
if (btnCalculate) {
    btnCalculate.addEventListener('click', () => {
        if (window.innerWidth < 768 && !sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    });
}
