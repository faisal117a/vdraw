<?php
// admin/dashboard/changelog_view.php - Included in dashboard index.php
?>
<div id="tab-changelog" class="tab-content hidden">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-white"><i class="fa-solid fa-clock-rotate-left mr-2 text-brand-400"></i> Change Logs</h2>
        <button onclick="openChangeLogModal()" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded shadow transition">
            <i class="fa-solid fa-plus mr-2"></i> Add Entry
        </button>
    </div>

    <!-- Change Logs List Table -->
    <div class="glass-panel rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-400">
                <thead class="bg-slate-800/50 text-slate-200 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Title / Apps</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Button Text</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800" id="changelog-table-body">
                    <tr>
                        <td colspan="6" class="p-8 text-center italic">Loading change logs...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Change Log Modal -->
<div id="modal-changelog" class="fixed inset-0 bg-black/80 hidden z-50 flex items-center justify-center backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-slate-900 rounded-xl border border-slate-700 w-full max-w-2xl shadow-2xl my-auto">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white" id="modal-changelog-title">Add Change Log Entry</h3>
            <button onclick="document.getElementById('modal-changelog').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark fa-lg"></i></button>
        </div>
        <form onsubmit="saveChangeLog(event)" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto custom-scrollbar">
            <input type="hidden" name="id" id="cl-id">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Title *</label>
                    <input type="text" name="title" id="cl-title" required class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Change Type *</label>
                    <select name="change_type" id="cl-change-type" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                        <option value="new_feature">🆕 New Feature</option>
                        <option value="improvement">⚡ Improvement</option>
                        <option value="bug_fix">🐛 Bug Fix</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Date</label>
                    <input type="date" name="created_date" id="cl-date" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Description *</label>
                <textarea name="description" id="cl-description" rows="5" required class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none font-mono text-sm" placeholder="Rich HTML supported..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Button Title</label>
                    <input type="text" name="button_title" id="cl-button-title" value="What's New" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Version Tag</label>
                    <input type="text" name="version_tag" id="cl-version" placeholder="e.g. v1.6, Phase-18" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Priority Order</label>
                    <input type="number" name="priority_order" id="cl-priority" value="0" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                    <p class="text-[10px] text-slate-600 mt-1">Higher = shown first</p>
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_visible" id="cl-visible" checked class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-brand-600 focus:ring-offset-slate-900">
                        <span class="text-sm text-slate-300">Visible on Home Page</span>
                    </label>
                </div>
            </div>

            <!-- Apps Multi-Select -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Related Apps</label>
                <div class="grid grid-cols-3 gap-2 text-xs bg-slate-950 p-2 rounded border border-slate-800" id="cl-apps-container">
                    <label class="flex items-center gap-2"><input type="checkbox" name="app_names[]" value="PyViz"> PyViz</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="app_names[]" value="PDraw"> PDraw</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="app_names[]" value="TGDraw"> TGDraw</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="app_names[]" value="Stats"> Stats</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="app_names[]" value="AlgoViz"> AlgoViz</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="app_names[]" value="DViz"> DViz</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="app_names[]" value="VDraw"> VDraw (Home)</label>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-800">
                <button type="submit" class="px-6 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded shadow transition">Save Entry</button>
            </div>
        </form>
    </div>
</div>

<script>
    const CHANGELOG_URL = 'changelog_actions.php';

    async function loadChangeLogsList() {
        const fd = new FormData();
        fd.append('action', 'list');
        document.getElementById('changelog-table-body').innerHTML = '<tr><td colspan="6" class="p-8 text-center italic">Loading...</td></tr>';

        try {
            const res = await fetch(CHANGELOG_URL, {
                method: 'POST',
                body: fd
            });
            const d = await res.json();

            if (d.status === 'success') {
                const typeBadge = (t) => {
                    if (t === 'new_feature') return '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-900 text-green-300">NEW FEATURE</span>';
                    if (t === 'bug_fix') return '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-900 text-red-300">BUG FIX</span>';
                    return '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-900 text-blue-300">IMPROVEMENT</span>';
                };
                const rows = d.logs.length ? d.logs.map(log => {
                    let apps = log.app_names && log.app_names.length ? log.app_names.join(', ') : 'All Apps';
                    let statusClass = log.is_visible == 1 ? 'text-green-400' : 'text-slate-500';
                    let statusText = log.is_visible == 1 ? 'Visible' : 'Hidden';
                    return `
                 <tr class="hover:bg-slate-800/50 border-b border-slate-800/50">
                    <td class="px-6 py-4">
                        <div class="font-bold text-white">${log.title}</div>
                        <div class="text-[10px] text-slate-500">${apps}</div>
                        ${log.version_tag ? `<span class="text-[9px] bg-slate-700 text-slate-300 px-1 rounded">${log.version_tag}</span>` : ''}
                    </td>
                    <td class="px-6 py-4">${typeBadge(log.change_type)}</td>
                    <td class="px-6 py-4 text-xs text-slate-300">${log.created_date}</td>
                    <td class="px-6 py-4 text-xs text-brand-300 font-medium">${log.button_title}</td>
                    <td class="px-6 py-4">
                        <button onclick="toggleChangeLogVisibility(${log.id}, ${log.is_visible == 1 ? 0 : 1})" class="${statusClass} font-bold text-xs uppercase hover:underline">
                            ${statusText}
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button onclick="editChangeLog(${log.id})" class="text-blue-400 hover:text-white"><i class="fa-solid fa-pen"></i></button>
                        <button onclick="deleteChangeLog(${log.id})" class="text-red-400 hover:text-white"><i class="fa-solid fa-trash"></i></button>
                    </td>
                 </tr>
                 `;
                }).join('') : '<tr><td colspan="6" class="p-8 text-center text-slate-500 italic">No change log entries yet.</td></tr>';
                document.getElementById('changelog-table-body').innerHTML = rows;
            }
        } catch (e) {
            console.error(e);
        }
    }

    function openChangeLogModal() {
        document.getElementById('modal-changelog').classList.remove('hidden');
        document.querySelector('#modal-changelog form').reset();
        document.getElementById('cl-id').value = '';
        document.getElementById('modal-changelog-title').innerText = 'Add Change Log Entry';
        document.getElementById('cl-date').value = new Date().toISOString().split('T')[0];
        // Uncheck all apps
        document.querySelectorAll('#cl-apps-container input[type="checkbox"]').forEach(cb => cb.checked = false);
    }

    async function saveChangeLog(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'save');
        try {
            const res = await fetch(CHANGELOG_URL, {
                method: 'POST',
                body: fd
            });
            const d = await res.json();
            if (d.status === 'success') {
                alert('Change log saved successfully');
                document.getElementById('modal-changelog').classList.add('hidden');
                loadChangeLogsList();
            } else {
                alert('Error: ' + d.message);
            }
        } catch (e) {
            console.error(e);
            alert('Failed to save');
        }
    }

    async function editChangeLog(id) {
        const fd = new FormData();
        fd.append('action', 'get');
        fd.append('id', id);

        try {
            const res = await fetch(CHANGELOG_URL, {
                method: 'POST',
                body: fd
            });
            const d = await res.json();
            if (d.status === 'success') {
                const log = d.log;
                openChangeLogModal();
                document.getElementById('cl-id').value = log.id;
                document.getElementById('modal-changelog-title').innerText = 'Edit: ' + log.title;
                document.getElementById('cl-title').value = log.title;
                document.getElementById('cl-description').value = log.description;
                document.getElementById('cl-change-type').value = log.change_type;
                document.getElementById('cl-date').value = log.created_date;
                document.getElementById('cl-button-title').value = log.button_title;
                document.getElementById('cl-version').value = log.version_tag || '';
                document.getElementById('cl-priority').value = log.priority_order || 0;
                document.getElementById('cl-visible').checked = log.is_visible == 1;

                // Apps
                document.querySelectorAll('#cl-apps-container input[type="checkbox"]').forEach(cb => {
                    cb.checked = log.app_names && log.app_names.includes(cb.value);
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function deleteChangeLog(id) {
        if (!confirm('Are you sure you want to delete this change log entry permanently?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        await fetch(CHANGELOG_URL, {
            method: 'POST',
            body: fd
        });
        loadChangeLogsList();
    }

    async function toggleChangeLogVisibility(id, newVal) {
        const fd = new FormData();
        fd.append('action', 'toggle_visibility');
        fd.append('id', id);
        fd.append('is_visible', newVal);
        await fetch(CHANGELOG_URL, {
            method: 'POST',
            body: fd
        });
        loadChangeLogsList();
    }
</script>