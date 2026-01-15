<?php
// admin/ads/reports_view.php - Included in dashboard index.php
?>
<div id="tab-ads-reports" class="tab-content hidden">
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-4">
            <button onclick="switchTab('ads')" class="text-slate-400 hover:text-white transition"><i class="fa-solid fa-arrow-left"></i></button>
            <h2 class="text-2xl font-bold text-white"><i class="fa-solid fa-chart-line mr-2 text-brand-400"></i> Ads Reporting</h2>
        </div>
        
        <div class="flex gap-2 bg-slate-800 p-1 rounded-lg border border-slate-700 items-center">
            <label class="flex items-center gap-2 px-2 text-xs text-slate-400 cursor-pointer border-r border-slate-700 mr-2">
                <input type="checkbox" id="chk-show-completed" onchange="loadAdsReport()" class="form-checkbox bg-slate-900 border-slate-600 rounded text-brand-600 focus:ring-0">
                Show Completed
            </label>
            <input type="date" id="ads-rep-start" class="bg-slate-900 border-none text-xs text-white rounded p-2 focus:ring-1 focus:ring-brand-500 outline-none" onchange="loadAdsReport()">
            <span class="text-slate-500 self-center">-</span>
            <input type="date" id="ads-rep-end" class="bg-slate-900 border-none text-xs text-white rounded p-2 focus:ring-1 focus:ring-brand-500 outline-none" onchange="loadAdsReport()">
            <button onclick="loadAdsReport()" class="px-3 py-1 bg-brand-600 hover:bg-brand-500 text-white rounded text-xs font-bold transition">Apply</button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-panel p-6 rounded-xl border-l-4 border-blue-500 bg-gradient-to-br from-slate-900 to-slate-900/50">
            <div class="text-slate-400 text-xs uppercase font-bold tracking-wider">Total Impressions</div>
            <div class="text-3xl font-bold text-white mt-1" id="ads-kpi-impressions">-</div>
            <div class="text-xs text-green-400 mt-2 flex items-center"><i class="fa-solid fa-arrow-up mr-1"></i> <span id="ads-kpi-impressions-trend">0%</span> vs last period</div>
        </div>
        <div class="glass-panel p-6 rounded-xl border-l-4 border-purple-500 bg-gradient-to-br from-slate-900 to-slate-900/50">
            <div class="text-slate-400 text-xs uppercase font-bold tracking-wider">Total Clicks</div>
            <div class="text-3xl font-bold text-white mt-1" id="ads-kpi-clicks">-</div>
            <div class="text-xs text-green-400 mt-2 flex items-center"><i class="fa-solid fa-arrow-up mr-1"></i> <span id="ads-kpi-clicks-trend">0%</span> vs last period</div>
        </div>
        <div class="glass-panel p-6 rounded-xl border-l-4 border-yellow-500 bg-gradient-to-br from-slate-900 to-slate-900/50">
            <div class="text-slate-400 text-xs uppercase font-bold tracking-wider">Avg. CTR</div>
            <div class="text-3xl font-bold text-white mt-1" id="ads-kpi-ctr">-</div>
             <div class="text-xs text-slate-500 mt-2">Target: > 1.5%</div>
        </div>
         <div class="glass-panel p-6 rounded-xl border-l-4 border-green-500 bg-gradient-to-br from-slate-900 to-slate-900/50">
            <div class="text-slate-400 text-xs uppercase font-bold tracking-wider">Active Campaigns</div>
            <div class="text-3xl font-bold text-white mt-1" id="ads-kpi-active">-</div>
             <div class="text-xs text-slate-500 mt-2" id="ads-kpi-total-ads">of - Total Ads</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Main Line Chart -->
        <div class="lg:col-span-2 glass-panel p-6 rounded-xl border border-slate-700 flex flex-col">
            <h3 class="font-bold text-white mb-4">Performance Over Time</h3>
            <div class="flex-1 relative min-h-[300px]">
                <canvas id="adsPerformanceChart"></canvas>
            </div>
        </div>
        
        <!-- App Distribution Donut -->
        <div class="glass-panel p-6 rounded-xl border border-slate-700 flex flex-col">
            <h3 class="font-bold text-white mb-4">Impressions by App</h3>
             <div class="flex-1 relative min-h-[300px] flex items-center justify-center">
                <canvas id="adsAppChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Performance Table -->
    <div class="glass-panel rounded-xl overflow-hidden border border-slate-700">
        <div class="p-4 border-b border-slate-800 bg-slate-900/50 flex justify-between items-center">
             <h3 class="font-bold text-white">Campaign Performance</h3>
             <div class="flex gap-2">
                 <button onclick="printAdsReport()" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1 rounded border border-slate-700"><i class="fa-solid fa-file-pdf mr-1"></i> Print / PDF</button>
                 <button onclick="printAdsReport()" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1 rounded border border-slate-700"><i class="fa-solid fa-file-pdf mr-1"></i> Print / PDF</button>
             </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-400">
                <thead class="bg-slate-800/50 text-slate-200 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Ad Campaign</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3 text-right">Impressions</th>
                        <th class="px-6 py-3 text-right">Clicks</th>
                        <th class="px-6 py-3 text-right">CTR</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800" id="ads-report-table-body">
                    <tr><td colspan="6" class="p-8 text-center italic">Select date range to view data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let adsPerfChart = null;
let adsAppChart = null;

async function loadAdsReport() {
    // Defaults
    const dStart = document.getElementById('ads-rep-start').value || new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0];
    const dEnd = document.getElementById('ads-rep-end').value || new Date().toISOString().split('T')[0];
    
    // Set inputs if empty
    if(!document.getElementById('ads-rep-start').value) document.getElementById('ads-rep-start').value = dStart;
    if(!document.getElementById('ads-rep-end').value) document.getElementById('ads-rep-end').value = dEnd;

    document.getElementById('ads-report-table-body').innerHTML = '<tr><td colspan="6" class="p-8 text-center italic">Loading report data...</td></tr>';

    try {
        const fd = new FormData();
        fd.append('action', 'get_report');
        fd.append('start_date', dStart);
        fd.append('end_date', dEnd);

        const res = await fetch('../../admin/ads/ads_manager.php', { method: 'POST', body: fd });
        const text = await res.text();
        console.log("Ads Report Response:", text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            console.error("JSON Parse Error:", e);
            document.getElementById('ads-report-table-body').innerHTML = `<tr><td colspan="6" class="p-8 text-center text-red-500 font-mono text-xs whitespace-pre-wrap">Server Error: ${text.substring(0, 200)}...</td></tr>`;
            return;
        }

        if (data.status === 'success') {
            updateAdsKPI(data.kpi);
            updateAdsCharts(data.charts);
            updateAdsTable(data.table);
        } else {
            console.error('Ads Report Error:', data.message);
            document.getElementById('ads-report-table-body').innerHTML = `<tr><td colspan="6" class="p-8 text-center text-red-400">Error: ${data.message}</td></tr>`;
        }
    } catch (e) {
        console.error('Fetch Error:', e);
        document.getElementById('ads-report-table-body').innerHTML = '<tr><td colspan="6" class="p-8 text-center text-red-400">Failed to load data.</td></tr>';
    }
}

function updateAdsKPI(kpi) {
    document.getElementById('ads-kpi-impressions').innerText = parseInt(kpi.impressions).toLocaleString();
    document.getElementById('ads-kpi-clicks').innerText = parseInt(kpi.clicks).toLocaleString();
    document.getElementById('ads-kpi-ctr').innerText = parseFloat(kpi.ctr).toFixed(2) + '%';
    document.getElementById('ads-kpi-active').innerText = kpi.active_ads;
    document.getElementById('ads-kpi-total-ads').innerText = `of ${kpi.total_ads} Total Ads`;
    
    // Trends can be calculated if we fetch prev period, for now placeholder
}

function updateAdsCharts(charts) {
    // Line Chart
    const ctxPerf = document.getElementById('adsPerformanceChart').getContext('2d');
    if (adsPerfChart) adsPerfChart.destroy();
    
    adsPerfChart = new Chart(ctxPerf, {
        type: 'line',
        data: {
            labels: charts.dates,
            datasets: [
                {
                    label: 'Impressions',
                    data: charts.impressions,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Clicks',
                    data: charts.clicks,
                    borderColor: '#a855f7',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { type: 'linear', display: true, position: 'left', grid: { color: 'rgba(255,255,255,0.05)' }, title: { display: true, text: 'Impressions' } },
                y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Clicks' } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { labels: { color: '#cbd5e1' } } }
        }
    });

    // Pie Chart
    const ctxApp = document.getElementById('adsAppChart').getContext('2d');
    if (adsAppChart) adsAppChart.destroy();

    const appLabels = Object.keys(charts.apps);
    const appData = Object.values(charts.apps);

    adsAppChart = new Chart(ctxApp, {
        type: 'doughnut',
        data: {
            labels: appLabels,
            datasets: [{
                data: appData,
                backgroundColor: ['#3b82f6', '#a855f7', '#f59e0b', '#10b981', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#cbd5e1' } } }
        }
    });
}

function updateAdsTable(rows) {
    // Filter rows based on Show Completed toggle
    const showCompleted = document.getElementById('chk-show-completed').checked;
    const filteredRows = rows.filter(r => showCompleted || r.status !== 'completed');

    if (!filteredRows || filteredRows.length === 0) {
        document.getElementById('ads-report-table-body').innerHTML = '<tr><td colspan="6" class="p-8 text-center italic">No performance data for this period.</td></tr>';
        return;
    }

    const html = filteredRows.map(r => `
        <tr class="hover:bg-slate-800/30 border-b border-slate-800/50">
            <td class="px-6 py-3 font-medium text-white">
                <div>${r.ad_title}</div>
                <div class="text-[10px] text-slate-500 font-mono">ID: ${r.ad_id}</div>
            </td>
            <td class="px-6 py-3"><span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold bg-slate-800 text-slate-400 border border-slate-700">${r.ad_type}</span></td>
            <td class="px-6 py-3 text-right font-mono">${parseInt(r.impressions).toLocaleString()}</td>
            <td class="px-6 py-3 text-right font-mono text-blue-400">${parseInt(r.clicks).toLocaleString()}</td>
            <td class="px-6 py-3 text-right font-mono font-bold ${parseFloat(r.ctr) > 1.0 ? 'text-green-400' : 'text-slate-400'}">${parseFloat(r.ctr).toFixed(2)}%</td>
            <td class="px-6 py-3 text-center flex gap-2 justify-center">
                <button onclick="printSingleAdReport(${r.ad_id})" class="text-slate-500 hover:text-white transition" title="Print this Ad Report"><i class="fa-solid fa-file-pdf"></i></button>
                ${r.status === 'completed' 
                    ? '<span class="text-xs text-slate-500 font-bold ml-2"><i class="fa-solid fa-check-double mr-1"></i> Completed</span> <button onclick="deleteAdFromReport('+r.ad_id+')" class="text-slate-500 hover:text-red-500 transition ml-2"><i class="fa-solid fa-trash"></i></button>'
                    : `
                    <button onclick="toggleAdStatusInReport(${r.ad_id}, '${r.status}')" class="px-2 py-1 rounded text-xs font-bold border transition ${r.status === 'active' ? 'bg-green-500/10 text-green-400 border-green-500/50 hover:bg-green-500/20' : 'bg-yellow-500/10 text-yellow-400 border-yellow-500/50 hover:bg-yellow-500/20'}">
                        ${r.status === 'active' ? '<i class="fa-solid fa-pause mr-1"></i> Active' : '<i class="fa-solid fa-play mr-1"></i> Paused'}
                    </button>
                    <button onclick="markAdCompleted(${r.ad_id})" class="text-slate-500 hover:text-blue-400 transition ml-2" title="Mark as Completed"><i class="fa-solid fa-flag-checkered"></i></button>
                    `
                }
            </td>
        </tr>
    `).join('');
    
    document.getElementById('ads-report-table-body').innerHTML = html;
}

function printSingleAdReport(id) {
    const dStart = document.getElementById('ads-rep-start').value;
    const dEnd = document.getElementById('ads-rep-end').value;
    window.open(`../../admin/ads/print_report.php?start_date=${dStart}&end_date=${dEnd}&ad_id=${id}`, '_blank');
}

function exportAdsReport() {
    const dStart = document.getElementById('ads-rep-start').value;
    const dEnd = document.getElementById('ads-rep-end').value;
    window.location.href = `../../admin/ads/ads_manager.php?action=export_report&start_date=${dStart}&end_date=${dEnd}`;
}

async function toggleAdStatusInReport(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';
    if(!confirm(`Are you sure you want to change status to ${newStatus.toUpperCase()}?`)) return;

    const fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('ad_id', id);
    fd.append('status', newStatus);

    try {
        const res = await fetch('../../admin/ads/ads_manager.php', { method: 'POST', body: fd });
        const data = await res.json();
        if(data.status === 'success') {
            loadAdsReport(); // Reload to update UI
        } else {
            alert('Error: ' + data.message);
        }
    } catch(e) {
        console.error(e);
        alert('Request failed');
    }
}

async function markAdCompleted(id) {
    if(!confirm("Are you sure you want to mark this campaign as COMPLETED?\n\n- It will stop serving immediately.\n- It cannot be reactivated.\n- It will be hidden from the active ads list.")) return;

    const fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('ad_id', id);
    fd.append('status', 'completed');

    try {
        const res = await fetch('../../admin/ads/ads_manager.php', { method: 'POST', body: fd });
        const data = await res.json();
        if(data.status === 'success') {
            loadAdsReport();
        } else {
            alert('Error: ' + data.message);
        }
    } catch(e) {
        console.error(e);
        alert('Request failed');
    }
}

async function deleteAdFromReport(id) {
    if(!confirm("Delete this completed campaign permanently? This cannot be undone.")) return;
    
    // Check if delete is implemented in backend, assuming 'delete_ad'
    const fd = new FormData();
    fd.append('action', 'delete_ad');
    fd.append('ad_id', id);
    
    try {
        const res = await fetch('../../admin/ads/ads_manager.php', { method: 'POST', body: fd });
        const data = await res.json();
        if(data.status === 'success') {
            loadAdsReport();
        } else {
            alert('Error: ' + data.message);
        }
    } catch(e) { console.error(e); alert('Request failed'); }
}

function printAdsReport() {
    // Open print view
    const dStart = document.getElementById('ads-rep-start').value;
    const dEnd = document.getElementById('ads-rep-end').value;
    const w = window.open(`../../admin/ads/print_report.php?start_date=${dStart}&end_date=${dEnd}`, '_blank');
}
</script>
