let currentChart = null;
let isPlotlyActive = false;

function renderChart(xData, yData, stats) {
    // Get Selected Chart Type
    const chartType = document.getElementById('chart-type').value;

    const canvas = document.getElementById('mainChart');
    const plotlyContainer = document.getElementById('plotlyChart');

    // Reset Containers
    if (chartType === 'boxplot' || chartType === 'histogram' || chartType === 'scatter') {
        // Use Plotly
        canvas.classList.add('hidden');
        plotlyContainer.classList.remove('hidden');

        if (currentChart) {
            currentChart.destroy();
            currentChart = null;
        }

        renderPlotlyChart(xData, yData, chartType);
    } else {
        // Use Chart.js (Bar/Line)
        plotlyContainer.classList.add('hidden');
        canvas.classList.remove('hidden');

        if (isPlotlyActive) {
            Plotly.purge(plotlyContainer);
            isPlotlyActive = false;
        }

        renderChartJS(xData, yData, stats, chartType);
    }
}

function renderChartJS(xData, yData, stats, type) {
    const ctx = document.getElementById('mainChart').getContext('2d');

    if (currentChart) {
        currentChart.destroy();
    }

    // Sort for line chart? Bar chart?
    // If it's a timeseries or category X-axis, we might NOT want to sort.
    // But for statistics usually we sort to show distribution.
    // However, user now provides X & Y. We should trust the order or allow sort.
    // For now, let's trust the order if it came from columns.
    // But if it was auto-generated (1,2,3), we might want to sort values for visualizing distribution?
    // Let's stick to: Visualizing exactly what they selected.

    // BUT stats.mean line is specific to the values (Y).

    const datasets = [{
        type: type === 'line' ? 'line' : 'bar',
        label: 'Values',
        data: yData, // Use explicit Y data
        backgroundColor: 'rgba(99, 102, 241, 0.5)',
        borderColor: '#6366f1',
        borderWidth: 1,
        borderRadius: 4,
        hoverBackgroundColor: '#818cf8',
        tension: 0.3
    }];

    // Add Mean Line Overlay (Only if calculating on the same dataset as Y)
    // There is a risk here: Analysis Column might be diff from Chart Y Column.
    // We should only show mean if we are roughly sure. 
    // For now, removing Mean line overlay to avoid confusion with multi-column.
    // Or we can add it back if we detect we are plotting the analysis variable.

    // Add mean line as a simple horizontal annotation?
    datasets.push({
        type: 'line',
        label: 'Mean (Calculated)',
        data: Array(xData.length).fill(stats.mean),
        borderColor: '#ec4899',
        borderWidth: 2,
        pointRadius: 0,
        borderDash: [5, 5]
    });

    currentChart = new Chart(ctx, {
        type: type === 'line' ? 'line' : 'bar',
        data: {
            labels: xData, // Use explicit X labels
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#94a3b8' } },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1'
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#64748b' }
                },
                x: {
                    // Show X labels now that we have real ones
                    display: true,
                    grid: { display: false },
                    ticks: { color: '#64748b', maxTicksLimit: 20 }
                }
            }
        }
    });
}

// ... (existing functions) ...

// GSAP Animation Logic ...
// Animation Mode Toggle ...

// Regression Logic
const regressionContainer = document.getElementById('regression-area'); // Need to create in HTML
const regressionSelect = document.getElementById('regression-select'); // Need to create in HTML

// PDF Export Logic
document.getElementById('btn-export-pdf')?.addEventListener('click', generatePDF);

async function generatePDF() {
    // Requires html2canvas and jsPDF or window.print approach
    // For now, let's try a simple browser print or fetch backend PDF?
    // User requested "full pdf report".
    // Best interaction: Detailed report including charts.
    // Given we are client-side, using window.print() with specific CSS 
    // is robust and cleaner than client-side jsPDF for complex charts.
    window.print();
}

// Update Render Chart Logic for Scatter
function renderChart(xData, yData, stats) {
    const chartType = document.getElementById('chart-type').value;

    const canvas = document.getElementById('mainChart');
    const plotlyContainer = document.getElementById('plotlyChart');

    if (chartType === 'boxplot' || chartType === 'histogram' || chartType === 'scatter') {
        // Use Plotly
        canvas.classList.add('hidden');
        plotlyContainer.classList.remove('hidden');

        if (currentChart) {
            currentChart.destroy();
            currentChart = null;
        }

        renderPlotlyChart(xData, yData, chartType, stats.regression);
    } else {
        // Use Chart.js (Bar/Line)
        plotlyContainer.classList.add('hidden');
        canvas.classList.remove('hidden');

        if (isPlotlyActive) {
            Plotly.purge(plotlyContainer);
            isPlotlyActive = false;
        }

        renderChartJS(xData, yData, stats, chartType);
    }
}

function renderPlotlyChart(xData, yData, type, regression) {
    const plotlyContainer = document.getElementById('plotlyChart');
    isPlotlyActive = true;
    let trace;
    let layout = {
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
        font: { family: 'Outfit, sans-serif', color: '#94a3b8' },
        margin: { t: 30, r: 20, l: 40, b: 40 },
        xaxis: { gridcolor: 'rgba(255,255,255,0.05)' },
        yaxis: { gridcolor: 'rgba(255,255,255,0.05)' }
    };

    let traces = [];

    if (type === 'histogram') {
        trace = {
            x: yData,
            type: 'histogram',
            marker: { color: 'rgba(99, 102, 241, 0.6)', line: { color: '#6366f1', width: 1 } },
            autobinx: true
        };
        traces.push(trace);
    } else if (type === 'boxplot') {
        trace = {
            y: yData,
            type: 'box',
            boxpoints: 'all',
            jitter: 0.3,
            pointpos: -1.8,
            marker: { color: '#ec4899', size: 4 },
            name: 'Dataset'
        };
        traces.push(trace);
    } else if (type === 'scatter') {
        trace = {
            x: xData,
            y: yData,
            mode: 'markers',
            type: 'scatter',
            marker: { size: 10, color: '#6366f1' },
            name: 'Data'
        };
        traces.push(trace);

        // Add Regression Line if present
        if (regression) {
            // Calculate regression line points
            // y = mx + b
            if (regression.slope !== undefined) {
                const xMin = Math.min(...xData);
                const xMax = Math.max(...xData);
                const yMin = regression.slope * xMin + regression.intercept;
                const yMax = regression.slope * xMax + regression.intercept;

                let regTrace = {
                    x: [xMin, xMax],
                    y: [yMin, yMax],
                    mode: 'lines',
                    type: 'scatter',
                    name: 'Linear Fit',
                    line: { color: '#10b981', width: 3 }
                };
                traces.push(regTrace);
            }
        }
    }

    Plotly.newPlot(plotlyContainer, traces, layout, { displayModeBar: false, responsive: true });
}

// ... existing code ...

// ... (existing render functions) ...

// Theme Logic
const btnTheme = document.getElementById('theme-toggle');
let isDark = true;

if (btnTheme) {
    btnTheme.addEventListener('click', () => {
        isDark = !isDark;
        document.body.classList.toggle('light-mode');

        // Update Icon
        btnTheme.innerHTML = isDark ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';

        // Fix for specific elements in light mode
        if (!isDark) {
            // Light Mode Colors
            // We relying on CSS variables but some Chart.js defaults need update
            if (currentChart) {
                currentChart.options.scales.x.ticks.color = '#334155'; // Slate 700
                currentChart.options.scales.y.ticks.color = '#334155';
                currentChart.options.plugins.legend.labels.color = '#334155';
                currentChart.update();
            }
        } else {
            // Dark Mode
            if (currentChart) {
                currentChart.options.scales.x.ticks.color = '#94a3b8'; // Slate 400
                currentChart.options.scales.y.ticks.color = '#94a3b8';
                currentChart.options.plugins.legend.labels.color = '#94a3b8';
                currentChart.update();
            }
        }
    });
}

// Export Logic
document.getElementById('btn-export-png')?.addEventListener('click', () => exportChart('png'));
document.getElementById('btn-export-svg')?.addEventListener('click', () => exportChart('svg'));

function exportChart(format) {
    const chartType = document.getElementById('chart-type').value;

    if (chartType === 'boxplot' || chartType === 'histogram' || chartType === 'scatter') {
        const gd = document.getElementById('plotlyChart');
        // Plotly handles extensions well usually, but let's be explicit
        Plotly.downloadImage(gd, { format: format, filename: 'vdraw_chart' });
    } else {
        // Chart.js (Canvas)
        const canvas = document.getElementById('mainChart');
        const link = document.createElement('a');

        // Ensure robust download by appending to body
        document.body.appendChild(link);

        if (format === 'png') {
            link.download = 'vdraw_chart.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } else {
            // Native Canvas cannot export SVG. 
            alert("Standard Charts (Bar/Line) cannot be exported as SVG natively. Downloading as high-res PNG instead.");
            link.download = 'vdraw_chart.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }

        document.body.removeChild(link);
    }
}

// GSAP Animation Logic (Placeholder for Advanced Mode)
function animateElements() {
    const isAdvanced = document.getElementById('mode-adv')?.classList.contains('bg-brand-600'); // Check if advanced active
    if (!isAdvanced || typeof gsap === 'undefined') return;

    // Example GSAP Animation
    gsap.from("#stats-grid > div", {
        duration: 0.6,
        y: 30,
        opacity: 0,
        stagger: 0.1,
        ease: "back.out(1.7)"
    });

    gsap.from("#results-area", {
        duration: 0.8,
        y: 50,
        opacity: 0,
        delay: 0.3,
        ease: "power3.out"
    });
}

// GSAP Animation Logic ... (rest of function) ...

// Animation Mode Toggle in Header
const btnStd = document.getElementById('mode-std');
const btnAdv = document.getElementById('mode-adv');

if (btnStd && btnAdv) {
    btnStd.addEventListener('click', () => {
        btnStd.className = "px-3 py-1 text-xs font-medium rounded text-white bg-slate-600 transition-all";
        btnAdv.className = "px-3 py-1 text-xs font-medium rounded text-slate-400 hover:text-white transition-all";
        // Reset or disable complex animations
    });

    btnAdv.addEventListener('click', () => {
        btnAdv.className = "px-3 py-1 text-xs font-medium rounded text-white bg-brand-600 transition-all";
        btnStd.className = "px-3 py-1 text-xs font-medium rounded text-slate-400 hover:text-white transition-all";
    });
}
