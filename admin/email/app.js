// admin/email/app.js

const API_BASE = 'api/';
let currentTab = 'templates';

// State
let isSending = false;
let deliveryStats = { total: 0, sent: 0, failed: 0 };
let deliveryLogText = "";
let deliveryStartTime = null;
let consecutiveFailures = 0;

// --- Utils ---
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(el => {
        el.classList.remove('bg-brand-600', 'text-white');
        el.classList.add('bg-slate-800', 'text-slate-300');
    });

    document.getElementById('tab-' + tab).classList.add('active');
    document.querySelector(`.nav-btn[data-tab="${tab}"]`).classList.remove('bg-slate-800', 'text-slate-300');
    document.querySelector(`.nav-btn[data-tab="${tab}"]`).classList.add('bg-brand-600', 'text-white');

    currentTab = tab;
    loadTabContent(tab);
}

function loadTabContent(tab) {
    if (tab === 'templates') loadTemplates();
    if (tab === 'batches') loadBatches();
    if (tab === 'smtp') loadSMTP();
    if (tab === 'delivery') loadDeliveryOptions();
    if (tab === 'logs') loadLogs();
}

async function api(endpoint, data = {}) {
    const formData = new FormData();
    for (const key in data) {
        formData.append(key, data[key]);
    }
    try {
        const res = await fetch(API_BASE + endpoint, { method: 'POST', body: formData });
        return await res.json();
    } catch (e) {
        return { status: 'error', message: 'Network Error: ' + e.message };
    }
}

function toast(msg, type = 'success') {
    let t = document.createElement('div');
    t.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} text-white font-medium`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.getElementById(id).style.display = 'none';
}

function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.getElementById(id).style.display = 'flex';
}

// Table style helper
function tableHTML(headers, rows) {
    let html = `<div class="overflow-x-auto"><table class="w-full text-left text-sm text-slate-400">`;
    html += `<thead class="bg-slate-800/50 text-slate-200 uppercase text-xs"><tr>`;
    headers.forEach(h => html += `<th class="px-4 py-3">${h}</th>`);
    html += `</tr></thead><tbody class="divide-y divide-slate-800">`;
    rows.forEach(r => {
        html += `<tr class="hover:bg-slate-800/30">`;
        r.forEach(c => html += `<td class="px-4 py-3">${c}</td>`);
        html += `</tr>`;
    });
    if (rows.length === 0) {
        html += `<tr><td colspan="${headers.length}" class="px-4 py-4 text-center italic text-slate-600">No records found.</td></tr>`;
    }
    html += `</tbody></table></div>`;
    return html;
}

// --- Templates ---
async function loadTemplates() {
    const res = await api('templates.php', { action: 'list' });
    const div = document.getElementById('templates-list');
    if (res.status === 'success') {
        const rows = res.data.map(t => [
            t.template_title,
            `v${t.template_version}`,
            t.is_active == 1 ? '<span class="px-2 py-0.5 bg-green-900 text-green-300 rounded text-xs font-bold">Active</span>' : '<span class="px-2 py-0.5 bg-slate-700 text-slate-400 rounded text-xs">Inactive</span>',
            t.created_at,
            `<button onclick="editTemplate(${t.tid})" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-xs mr-1"><i class="fa-solid fa-pen"></i></button>
             <button onclick="deleteTemplate(${t.tid})" class="px-2 py-1 bg-red-900 hover:bg-red-800 text-red-300 rounded text-xs"><i class="fa-solid fa-trash"></i></button>`
        ]);
        div.innerHTML = tableHTML(['Title', 'Version', 'Status', 'Created', 'Actions'], rows);
    } else {
        div.innerHTML = `<p class="text-red-400">${res.message}</p>`;
    }
}

function openTemplateModal() {
    document.getElementById('form-template').reset();
    document.getElementById('tpl_id').value = 0;
    openModal('modal-template');
}

async function editTemplate(id) {
    const res = await api('templates.php', { action: 'get', id });
    if (res.status === 'success') {
        const t = res.data;
        document.getElementById('tpl_id').value = t.tid;
        document.getElementById('tpl_title').value = t.template_title;
        document.getElementById('tpl_html').value = t.html_body;
        document.getElementById('tpl_text').value = t.text_body;
        document.getElementById('tpl_active').checked = t.is_active == 1;
        openModal('modal-template');
    }
}

async function saveTemplate(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'save');
    if (!document.getElementById('tpl_active').checked) fd.set('is_active', 0);

    const res = await fetch(API_BASE + 'templates.php', { method: 'POST', body: fd }).then(r => r.json());
    if (res.status === 'success') {
        closeModal('modal-template');
        loadTemplates();
        toast(res.message);
    } else {
        toast(res.message, 'error');
    }
}

async function deleteTemplate(id) {
    if (!confirm('Delete this template?')) return;
    const res = await api('templates.php', { action: 'delete', id });
    if (res.status === 'success') { loadTemplates(); toast('Deleted'); }
}

// --- Batches ---
async function loadBatches() {
    const res = await api('batches.php', { action: 'list' });
    const div = document.getElementById('batches-list');
    if (res.status === 'success') {
        const rows = res.data.map(b => {
            const count = b.email_list_source ? b.email_list_source.split(',').length : 0;
            return [
                `<div class="text-white font-medium">${b.title}</div><div class="text-xs text-slate-500">${b.description || ''}</div>`,
                `${count} emails`,
                b.created_at,
                `<button onclick="editBatch(${b.bid})" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-xs mr-1"><i class="fa-solid fa-pen"></i></button>
                 <button onclick="deleteBatch(${b.bid})" class="px-2 py-1 bg-red-900 hover:bg-red-800 text-red-300 rounded text-xs mr-1"><i class="fa-solid fa-trash"></i></button>
                 <button onclick="initSnapshotFor(${b.bid})" class="px-2 py-1 bg-brand-700 hover:bg-brand-600 text-white rounded text-xs"><i class="fa-solid fa-bolt"></i> Init</button>`
            ];
        });
        div.innerHTML = tableHTML(['Title', 'Source Count', 'Created', 'Actions'], rows);
    } else {
        div.innerHTML = `<p class="text-red-400">${res.message}</p>`;
    }
}

function openBatchModal() {
    document.getElementById('form-batch').reset();
    document.getElementById('batch_id').value = 0;
    openModal('modal-batch');
}

async function editBatch(id) {
    const res = await api('batches.php', { action: 'get', id });
    if (res.status === 'success') {
        const d = res.data;
        document.getElementById('batch_id').value = d.bid;
        document.getElementById('batch_title').value = d.title;
        document.getElementById('batch_desc').value = d.description || '';
        document.getElementById('batch_source').value = d.email_list_source;
        openModal('modal-batch');
    }
}

async function saveBatch(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'save');
    const res = await fetch(API_BASE + 'batches.php', { method: 'POST', body: fd }).then(r => r.json());
    if (res.status === 'success') {
        closeModal('modal-batch');
        loadBatches();
        toast(res.message);
    } else {
        toast(res.message, 'error');
    }
}

async function deleteBatch(id) {
    if (!confirm('Delete batch?')) return;
    const res = await api('batches.php', { action: 'delete', id });
    if (res.status === 'success') { loadBatches(); toast('Deleted'); }
}

// User Filter
function openUserFilterModal() {
    openModal('modal-filter-users');
    document.getElementById('user-results').innerHTML = '<p class="text-slate-500 text-sm italic">Enter filters and click Search.</p>';
    document.getElementById('selected_count').innerText = 0;
}

async function searchUsers() {
    const role = document.getElementById('filter_role').value;
    const search = document.getElementById('filter_search').value;
    const dStart = document.getElementById('filter_date_start').value;
    const dEnd = document.getElementById('filter_date_end').value;
    const active = document.getElementById('filter_active').checked;
    const verified = document.getElementById('filter_verified').checked;
    const limit = parseInt(document.getElementById('filter_limit').value);

    document.getElementById('user-results').innerHTML = '<p class="text-slate-400 text-sm">Searching...</p>';

    const res = await api('batches.php', {
        action: 'search_users',
        role_filter: role,
        search: search,
        date_start: dStart,
        date_end: dEnd,
        active_only: active ? 'true' : 'false',
        verified_only: verified ? 'true' : 'false',
        limit: limit
    });

    if (res.status === 'success') {
        const users = res.data;
        if (users.length === 0) {
            document.getElementById('user-results').innerHTML = '<p class="text-slate-500 text-sm italic">No users found matching criteria.</p>';
            return;
        }

        let html = `<div class="flex justify-between items-center mb-2 text-sm">
            <label class="flex items-center gap-2 text-slate-300"><input type="checkbox" onchange="toggleSelectAll(this)" class="w-4 h-4"> Select All</label>
            <span class="text-slate-500">Found: ${res.total} (Showing ${users.length})</span>
        </div>`;
        html += `<table class="w-full text-sm text-slate-400"><tbody>`;
        users.forEach(u => {
            html += `<tr class="border-b border-slate-800 hover:bg-slate-800/30">
                <td class="py-2 pr-2 w-8"><input type="checkbox" class="user-select-chk w-4 h-4" value="${u.email}"></td>
                <td class="py-2">${u.email}</td>
                <td class="py-2 text-slate-500">${u.full_name || '-'}</td>
                <td class="py-2 text-xs">${u.role}</td>
            </tr>`;
        });
        html += `</tbody></table>`;
        document.getElementById('user-results').innerHTML = html;
        document.querySelectorAll('.user-select-chk').forEach(c => c.addEventListener('change', updateSelectedCount));
    } else {
        document.getElementById('user-results').innerHTML = `<p class="text-red-400">${res.message}</p>`;
    }
}

function toggleSelectAll(source) {
    document.querySelectorAll('.user-select-chk').forEach(c => c.checked = source.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    document.getElementById('selected_count').innerText = document.querySelectorAll('.user-select-chk:checked').length;
}

function addSelectedUsers() {
    const emails = Array.from(document.querySelectorAll('.user-select-chk:checked')).map(c => c.value);
    if (emails.length === 0) { toast('No users selected', 'error'); return; }

    const textarea = document.getElementById('batch_source');
    const existingText = textarea.value.trim();
    const existingEmails = existingText ? existingText.split(',').map(e => e.trim().toLowerCase()).filter(e => e) : [];

    let added = 0;
    let duplicates = 0;
    let invalid = 0;

    const newEmails = [];
    emails.forEach(email => {
        const emailLower = email.trim().toLowerCase();
        // Check if valid email format
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailLower)) {
            invalid++;
            return;
        }
        // Check for duplicates
        if (existingEmails.includes(emailLower) || newEmails.includes(emailLower)) {
            duplicates++;
            return;
        }
        newEmails.push(email.trim());
        added++;
    });

    if (newEmails.length > 0) {
        textarea.value = existingText ? existingText + ', ' + newEmails.join(', ') : newEmails.join(', ');
    }

    closeModal('modal-filter-users');

    // Show summary
    let summary = `✓ Added: ${added}`;
    if (duplicates > 0) summary += ` | Duplicates skipped: ${duplicates}`;
    if (invalid > 0) summary += ` | Invalid skipped: ${invalid}`;
    toast(summary, added > 0 ? 'success' : 'error');
}

function initSnapshotFor(id) {
    switchTab('delivery');
    setTimeout(() => {
        document.getElementById('delivery_batch').value = id;
        loadBatchSnapshotInfo();
    }, 500);
}

// --- SMTP ---
async function loadSMTP() {
    const res = await api('smtp.php', { action: 'list' });
    const div = document.getElementById('smtp-list');
    if (res.status === 'success') {
        const rows = res.data.map(p => [
            p.profile_name,
            `${p.smtp_host}:${p.smtp_port}`,
            p.smtp_username,
            p.is_default == 1 ? '<span class="px-2 py-0.5 bg-green-900 text-green-300 rounded text-xs font-bold">Default</span>' : '',
            `<button onclick="editSMTP(${p.id})" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-xs mr-1"><i class="fa-solid fa-pen"></i></button>
             <button onclick="deleteSMTP(${p.id})" class="px-2 py-1 bg-red-900 hover:bg-red-800 text-red-300 rounded text-xs"><i class="fa-solid fa-trash"></i></button>`
        ]);
        div.innerHTML = tableHTML(['Profile Name', 'Host', 'Username', 'Default', 'Actions'], rows);
    } else {
        div.innerHTML = `<p class="text-red-400">${res.message}</p>`;
    }
}

function openSMTPModal() {
    document.getElementById('form-smtp').reset();
    document.getElementById('smtp_id').value = 0;
    openModal('modal-smtp');
}

async function editSMTP(id) {
    const res = await api('smtp.php', { action: 'get', id });
    if (res.status === 'success') {
        const d = res.data;
        document.getElementById('smtp_id').value = d.id;
        document.getElementById('smtp_name').value = d.profile_name;
        document.getElementById('smtp_host').value = d.smtp_host;
        document.getElementById('smtp_port').value = d.smtp_port;
        document.getElementById('smtp_user').value = d.smtp_username;
        document.getElementById('smtp_sender_email').value = d.sender_email;
        document.getElementById('smtp_sender_name').value = d.sender_name;
        document.getElementById('smtp_enc').value = d.encryption;
        document.getElementById('smtp_default').checked = d.is_default == 1;
        openModal('modal-smtp');
    }
}

async function saveSMTP(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'save');
    if (!document.getElementById('smtp_default').checked) fd.set('is_default', 0);

    const res = await fetch(API_BASE + 'smtp.php', { method: 'POST', body: fd }).then(r => r.json());
    if (res.status === 'success') {
        closeModal('modal-smtp');
        loadSMTP();
        toast(res.message);
    } else {
        toast(res.message, 'error');
    }
}

async function deleteSMTP(id) {
    if (!confirm('Delete profile?')) return;
    const res = await api('smtp.php', { action: 'delete', id });
    if (res.status === 'success') { loadSMTP(); toast('Deleted'); }
}

async function testSMTP() {
    const fd = new FormData(document.getElementById('form-smtp'));
    fd.append('action', 'test');
    toast('Testing connection...', 'info');
    const res = await fetch(API_BASE + 'smtp.php', { method: 'POST', body: fd }).then(r => r.json());
    if (res.status === 'success') {
        alert("✓ Success: " + res.message);
    } else {
        alert("✗ Failed: " + res.message + (res.logs ? "\n\nLogs:\n" + res.logs.join('\n') : ''));
    }
}

// Test SMTP from Delivery tab using selected profile
async function testSMTPFromDelivery() {
    const smtpId = document.getElementById('delivery_smtp').value;
    if (smtpId == 0) { toast('Select an SMTP profile first', 'error'); return; }

    toast('Testing SMTP connection...', 'info');
    const res = await api('smtp.php', { action: 'test', id: smtpId });
    if (res.status === 'success') {
        alert("✓ Success: " + res.message);
    } else {
        alert("✗ Failed: " + res.message + (res.logs ? "\n\nLogs:\n" + res.logs.join('\n') : ''));
    }
}

// --- Delivery ---
async function loadDeliveryOptions() {
    const resB = await api('batches.php', { action: 'list' });
    const selB = document.getElementById('delivery_batch');
    selB.innerHTML = '<option value="0">-- Select Batch --</option>';
    if (resB.status === 'success') {
        resB.data.forEach(b => selB.innerHTML += `<option value="${b.bid}">${b.title}</option>`);
    }

    const resT = await api('templates.php', { action: 'list' });
    const selT = document.getElementById('delivery_template');
    selT.innerHTML = '<option value="0">-- Select Template --</option>';
    if (resT.status === 'success') {
        resT.data.filter(t => t.is_active == 1).forEach(t => selT.innerHTML += `<option value="${t.tid}">${t.template_title}</option>`);
    }

    const resS = await api('smtp.php', { action: 'list' });
    const selS = document.getElementById('delivery_smtp');
    selS.innerHTML = '<option value="0">-- Select Profile --</option>';
    if (resS.status === 'success') {
        resS.data.forEach(s => selS.innerHTML += `<option value="${s.id}" ${s.is_default == 1 ? 'selected' : ''}>${s.profile_name}</option>`);
    }
}

async function loadBatchSnapshotInfo() {
    const bid = document.getElementById('delivery_batch').value;
    const info = document.getElementById('batch_info');
    if (bid == 0) { info.innerText = 'Select a batch...'; return; }

    info.innerText = 'Loading...';
    const res = await api('batches.php', { action: 'get', id: bid });
    if (res.status === 'success' && res.data.stats) {
        const s = res.data.stats;
        info.innerText = `Snapshot: ${s.total} (Pending: ${s.pending}, Sent: ${s.sent}, Failed: ${s.failed})`;
        document.getElementById('stat_total').innerText = s.total;
        document.getElementById('stat_sent').innerText = s.sent;
        document.getElementById('stat_failed').innerText = s.failed;
        document.getElementById('btn_start').disabled = (s.pending == 0);
        document.getElementById('btn_reset_failed').style.display = s.failed > 0 ? 'inline-block' : 'none';
    } else {
        info.innerText = 'No snapshot. Click Initialize.';
        document.getElementById('stat_total').innerText = 0;
        document.getElementById('stat_sent').innerText = 0;
        document.getElementById('stat_failed').innerText = 0;
        document.getElementById('btn_start').disabled = true;
    }
}

async function initSnapshot() {
    const bid = document.getElementById('delivery_batch').value;
    if (bid == 0) return toast('Select a batch first', 'error');
    if (!confirm('Initialize snapshot from batch source?')) return;
    const res = await api('batches.php', { action: 'init_snapshot', id: bid });
    if (res.status === 'success') {
        toast(res.message);
        loadBatchSnapshotInfo();
        appendConsole("Snapshot initialized: " + res.count + " emails");
    } else { toast(res.message, 'error'); }
}

async function resetFailed() {
    const bid = document.getElementById('delivery_batch').value;
    if (bid == 0) return;
    const res = await api('delivery.php', { action: 'reset_failed', batch_id: bid });
    if (res.status === 'success') {
        toast(res.message);
        loadBatchSnapshotInfo();
        appendConsole("Reset failed items to pending.");
    }
}

function appendConsole(msg) {
    const c = document.getElementById('delivery_console');
    c.textContent += msg + "\n";
    // Check auto-scroll toggle
    const autoScroll = document.getElementById('delivery_autoscroll');
    if (!autoScroll || autoScroll.checked) {
        c.scrollTop = c.scrollHeight;
    }
    deliveryLogText += msg + "\n";
}

function startDelivery() {
    const bid = document.getElementById('delivery_batch').value;
    const tid = document.getElementById('delivery_template').value;
    const smtp = document.getElementById('delivery_smtp').value;
    if (bid == 0 || tid == 0 || smtp == 0) { toast('Select Batch, Template, and SMTP', 'error'); return; }

    isSending = true;
    consecutiveFailures = 0;
    document.getElementById('btn_start').disabled = true;
    document.getElementById('btn_stop').disabled = false;
    document.getElementById('btn_save_log').disabled = true;
    document.getElementById('delivery_batch').disabled = true;

    deliveryStats = {
        total: parseInt(document.getElementById('stat_total').innerText),
        sent: parseInt(document.getElementById('stat_sent').innerText),
        failed: parseInt(document.getElementById('stat_failed').innerText)
    };
    deliveryLogText = "";
    deliveryStartTime = new Date().toISOString();
    appendConsole("--- Starting Delivery " + new Date().toLocaleTimeString() + " ---");
    sendNextChunk();
}

function stopDelivery() {
    isSending = false;
    appendConsole("--- STOPPED BY USER ---");
    finishDelivery();
}

async function sendNextChunk() {
    if (!isSending) return;

    const bid = document.getElementById('delivery_batch').value;
    const tid = document.getElementById('delivery_template').value;
    const smtp = document.getElementById('delivery_smtp').value;
    const delay = parseInt(document.getElementById('delivery_delay').value);
    const maxPerRun = parseInt(document.getElementById('delivery_max_per_run').value);
    const failThreshold = parseInt(document.getElementById('delivery_fail_threshold').value);

    // Check max per run limit
    if (maxPerRun > 0 && (deliveryStats.sent + deliveryStats.failed) >= maxPerRun) {
        appendConsole(`!! MAX PER RUN LIMIT (${maxPerRun}) REACHED !!`);
        stopDelivery();
        return;
    }

    try {
        const res = await api('delivery.php', { action: 'process_batch', batch_id: bid, template_id: tid, smtp_id: smtp, limit: 5 });
        if (res.status === 'success') {
            res.logs.forEach(l => {
                appendConsole(`${l.email} : ${l.msg}`);
                if (l.status === 'sent') { deliveryStats.sent++; consecutiveFailures = 0; }
                if (l.status === 'failed') { deliveryStats.failed++; consecutiveFailures++; }
            });
            document.getElementById('stat_sent').innerText = deliveryStats.sent;
            document.getElementById('stat_failed').innerText = deliveryStats.failed;

            // Check configurable failure threshold
            if (failThreshold > 0 && consecutiveFailures >= failThreshold) {
                appendConsole(`!! AUTO-PAUSE: ${failThreshold} consecutive failures !!`);
                stopDelivery();
                return;
            }

            // Check max per run again after processing
            if (maxPerRun > 0 && (deliveryStats.sent + deliveryStats.failed) >= maxPerRun) {
                appendConsole(`!! MAX PER RUN LIMIT (${maxPerRun}) REACHED !!`);
                stopDelivery();
                return;
            }

            if (res.remaining > 0) {
                setTimeout(sendNextChunk, delay);
            } else {
                appendConsole("--- Finished ---");
                isSending = false;
                finishDelivery();
            }
        } else { appendConsole("API Error: " + res.message); stopDelivery(); }
    } catch (e) { appendConsole("Network Error: " + e.message); stopDelivery(); }
}

function finishDelivery() {
    document.getElementById('btn_start').disabled = false;
    document.getElementById('btn_stop').disabled = true;
    document.getElementById('btn_save_log').disabled = false;
    document.getElementById('delivery_batch').disabled = false;
    loadBatchSnapshotInfo();
}

async function saveLog() {
    if (!deliveryLogText) return;
    const res = await api('delivery.php', {
        action: 'save_log',
        batch_id: document.getElementById('delivery_batch').value,
        template_id: document.getElementById('delivery_template').value,
        smtp_profile_id: document.getElementById('delivery_smtp').value,
        log_text: deliveryLogText,
        started_at: deliveryStartTime,
        total_emails: deliveryStats.total,
        sent_count: deliveryStats.sent,
        failed_count: deliveryStats.failed
    });
    if (res.status === 'success') { toast('Log Saved'); document.getElementById('btn_save_log').disabled = true; }
    else { toast(res.message, 'error'); }
}

// --- Logs ---
async function loadLogs() {
    const res = await api('logs.php', { action: 'list' });
    const div = document.getElementById('logs-list');
    if (res.status === 'success') {
        const rows = res.data.map(l => [
            l.started_at,
            l.batch_title || '-',
            l.template_title || '-',
            `<span class="text-green-400 font-bold">${l.sent_count}</span>`,
            `<span class="text-red-400 font-bold">${l.failed_count}</span>`,
            `<button onclick="viewLog(${l.id})" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-xs"><i class="fa-solid fa-eye"></i></button>`
        ]);
        div.innerHTML = tableHTML(['Date', 'Batch', 'Template', 'Sent', 'Failed', 'Actions'], rows);
    } else {
        div.innerHTML = `<p class="text-red-400">${res.message}</p>`;
    }
}

async function viewLog(id) {
    const res = await api('logs.php', { action: 'get', id });
    if (res.status === 'success') {
        document.getElementById('view_log_content').textContent = res.data.log_text;
        openModal('modal-view-log');
    }
}

// Init
loadTemplates();
