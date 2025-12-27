const API_URL = 'http://127.0.0.1:8000';

// State
let globalData = {}; // Stores { col1: [1,2], col2: [3,4] }
let isManualMode = true;

// DOM Elements
const btnManual = document.getElementById('btn-manual');
const btnUpload = document.getElementById('btn-upload');
const manualSection = document.getElementById('manual-input-section');
const uploadSection = document.getElementById('upload-section');
const colSelectorSection = document.getElementById('column-selectors');
const btnCalculate = document.getElementById('btn-calculate');
const fileInput = document.getElementById('file-upload');
const manualInput = document.getElementById('manual-data');

// Selectors
// Selectors
const selAnalysis = document.getElementById('col-analysis');
const selX = document.getElementById('col-x');
const selY = document.getElementById('col-y');
const selQMethod = document.getElementById('quartile-method');
const selVarType = document.getElementById('variance-type');

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
btnManual.addEventListener('click', () => {
    isManualMode = true;
    toggleModeUI();
});

btnUpload.addEventListener('click', () => {
    isManualMode = false;
    toggleModeUI();
});

function toggleModeUI() {
    if (isManualMode) {
        btnManual.classList.replace('text-slate-400', 'text-white');
        btnManual.classList.add('bg-brand-600', 'shadow-lg');
        btnManual.classList.remove('hover:bg-slate-700');

        btnUpload.classList.replace('text-white', 'text-slate-400');
        btnUpload.classList.remove('bg-brand-600', 'shadow-lg');
        btnUpload.classList.add('hover:bg-slate-700');

        manualSection.classList.remove('hidden');
        uploadSection.classList.add('hidden');
        colSelectorSection.classList.add('hidden');
    } else {
        btnUpload.classList.replace('text-slate-400', 'text-white');
        btnUpload.classList.add('bg-brand-600', 'shadow-lg');
        btnUpload.classList.remove('hover:bg-slate-700');

        btnManual.classList.replace('text-white', 'text-slate-400');
        btnManual.classList.remove('bg-brand-600', 'shadow-lg');
        btnManual.classList.add('hover:bg-slate-700');

        manualSection.classList.add('hidden');
        uploadSection.classList.remove('hidden');
        // Only show column selectors if data is loaded
        if (Object.keys(globalData).length > 0) {
            colSelectorSection.classList.remove('hidden');
        }
    }
}

// File Upload
fileInput.addEventListener('change', async (e) => {
    const file = e.target.files[0];
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
        populateSelect(selX, allColumns, true); // X can be strings
        populateSelect(selY, numericColumns, true); // Y usually numeric

        // Auto-select first numeric column for analysis
        if (numericColumns.length > 0) selAnalysis.value = numericColumns[0];

        colSelectorSection.classList.remove('hidden');
        alert(`Loaded ${data.summary.rows} rows.`);

    } catch (err) {
        alert(err.message);
        fileInput.value = ''; // Reset
    } finally {
        btnCalculate.innerHTML = '<i class="fa-solid fa-calculator mr-2"></i> Calculate';
    }
});

function populateSelect(selectEl, options, addAuto = false) {
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

// Data Resolvers
function getAnalysisData() {
    if (isManualMode) {
        // Parse manual text immediately
        const rawText = manualInput.value;
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
        const xCol = selX.value;
        const yCol = selY.value;
        const anaCol = selAnalysis.value;

        let yData = globalData[anaCol] || [];
        // If Y-Axis is explicitly selected, use that for chart values instead of analysis column?
        // Usually "Calculate" works on Analysis Column. Chart visualizes that or X/Y.
        // Let's assume Chart defaults to Analysis Column if Y is Auto.
        if (yCol !== 'auto') {
            yData = globalData[yCol] || [];
        }

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
    const dataToAnalyze = getAnalysisData();

    if (!dataToAnalyze || dataToAnalyze.length === 0) {
        alert("No valid data found to analyze.");
        return;
    }

    try {
        btnCalculate.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Processing...';

        // Parameters
        const isSample = document.getElementById('variance-type').value === 'sample';
        const qMethod = document.getElementById('quartile-method').value;

        // Calculate Stats (Always on Analysis Data)
        const statsRes = await fetch(`${API_URL}/api/stats/calculate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                data: dataToAnalyze,
                is_sample: isSample,
                quartile_method: qMethod
            })
        });

        if (!statsRes.ok) throw new Error("Calculation failed");
        const stats = await statsRes.json();

        // Chart Data
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
