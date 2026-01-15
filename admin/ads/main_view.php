<?php
// admin/ads/main_view.php - Included in dashboard index.php
?>
<div id="tab-ads" class="tab-content hidden">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-white"><i class="fa-solid fa-rectangle-ad mr-2 text-brand-400"></i> Ads Manager</h2>
        
        <div class="flex gap-2">
            <button onclick="switchTab('ads-reports')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded shadow border border-slate-700 transition">
                <i class="fa-solid fa-chart-line mr-2"></i> Reports
            </button>
            <button onclick="openAdModal()" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded shadow transition">
                <i class="fa-solid fa-plus mr-2"></i> Create Ad
            </button>
            <button onclick="toggleGlobalSettings()" class="px-3 py-2 bg-slate-800 text-slate-300 border border-slate-700 rounded hover:bg-slate-700 transition">
                <i class="fa-solid fa-gears"></i>
            </button>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" id="ads-summary">
        <!-- Loaded via JS -->
    </div>

    <!-- Ads List Table -->
    <div class="glass-panel rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-400">
            <thead class="bg-slate-800/50 text-slate-200 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3">Reference/Title</th>
                    <th class="px-6 py-3">Type</th>
                    <th class="px-6 py-3">Placements</th>
                    <th class="px-6 py-3 text-center">Stats</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800" id="ads-table-body">
                <tr><td colspan="6" class="p-8 text-center italic">Loading ads...</td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Global Settings Modal -->
<div id="modal-ads-settings" class="fixed inset-0 bg-black/80 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-slate-900 rounded-xl border border-slate-700 w-full max-w-md p-6 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4">Ads Global Settings</h3>
        <form onsubmit="saveGlobalAdsSettings(event)">
            <input type="hidden" name="action" value="save_global_settings">
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-slate-800 rounded border border-slate-700">
                    <div>
                        <span class="text-white font-bold">Master Kill Switch</span>
                        <div class="text-xs text-slate-400">Enable/Disable all ads instantly</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads_enabled" class="sr-only peer" id="set-ads-enabled">
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">IP Privacy Retention (Days)</label>
                    <input type="number" name="ip_retention" id="set-ip-retention" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('modal-ads-settings').classList.add('hidden')" class="px-4 py-2 text-slate-400 hover:text-white">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded font-bold hover:bg-brand-500">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Create/Edit Ad Modal -->
<div id="modal-ad-edit" class="fixed inset-0 bg-black/80 hidden z-50 flex items-center justify-center backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-slate-900 rounded-xl border border-slate-700 w-full max-w-2xl shadow-2xl my-auto">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white" id="modal-ad-title">Create New Ad</h3>
            <button onclick="document.getElementById('modal-ad-edit').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark fa-lg"></i></button>
        </div>
        <form onsubmit="saveAd(event)" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto custom-scrollbar">
            <input type="hidden" name="action" value="save_ad">
            <input type="hidden" name="ad_id" id="inp-ad-id">

            <!-- Basic Info -->
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Internal Title / Reference</label>
                    <input type="text" name="ad_title" id="inp-ad-title" required class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                </div>
                <div>
                     <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type</label>
                     <select name="ad_type" id="inp-ad-type" onchange="toggleAdFields()" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                         <option value="sponsor">Sponsor (Image)</option>
                         <option value="google">Google / Network</option>
                         <option value="push">Push Notification</option>
                     </select>
                </div>
                <div>
                     <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                     <select name="status" id="inp-status" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                         <option value="active">Active</option>
                         <option value="paused">Paused</option>
                         <option value="suspended">Suspended</option>
                     </select>
                </div>
            </div>

             <!-- Sponsor Fields -->
            <div id="fields-sponsor" class="space-y-4 border-t border-slate-800 pt-4">
                 <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sponsor Image</label>
                    <input type="file" name="ad_image" accept="image/*" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-brand-400 hover:file:bg-slate-700">
                    <div id="prev-ad-image-container" class="mt-2 hidden">
                        <p class="text-[10px] text-slate-500 mb-1">Current Image:</p>
                        <img id="prev-ad-image-img" src="" alt="Ad Preview" class="h-20 rounded border border-slate-700">
                    </div>
                 </div>
                 <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Target URL</label>
                    <input type="url" name="ad_code_sponsor" id="inp-target-url" placeholder="https://..." class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white focus:border-brand-500 outline-none">
                 </div>
            </div>

            <!-- Google Fields -->
            <div id="fields-google" class="space-y-4 border-t border-slate-800 pt-4 hidden">
                 <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Network Name</label>
                    <input type="text" name="network_name" id="inp-network" placeholder="e.g. Google AdSense" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white">
                 </div>
                 <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Embed Code (JS/HTML)</label>
                    <textarea name="ad_code_google" id="inp-embed-code" rows="4" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white font-mono text-xs"></textarea>
                 </div>
            </div>

            <!-- Push Fields -->
            <div id="fields-push" class="space-y-4 border-t border-slate-800 pt-4 hidden">
                <div class="grid grid-cols-2 gap-4">
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Display Type</label>
                        <select name="push_display_type" id="inp-push-type" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white">
                             <option value="toast">Toast Notification</option>
                             <option value="modal">Modal Popup</option>
                             <option value="fullscreen">Fullscreen Overlay</option>
                        </select>
                     </div>
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Close Delay (sec)</label>
                         <input type="number" name="push_close_delay" id="inp-push-delay" value="3" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white">
                     </div>
                </div>
                 <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="push_allow_close" id="inp-push-allow-close" checked class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-brand-600 focus:ring-offset-slate-900">
                        <span class="text-sm text-slate-300">Allow user to close immediately</span>
                    </label>
                 </div>
                 <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Notification Content (HTML/Text)</label>
                    <textarea name="ad_code_push" id="inp-push-content" rows="2" placeholder="Text or HTML content for the popup..." class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white font-mono text-xs"></textarea>
                 </div>
            </div>

            <!-- Targeting & Schedule -->
            <div class="border-t border-slate-800 pt-4">
                 <h4 class="text-sm font-bold text-white mb-3">Targeting & Schedule</h4>
                 <div class="grid grid-cols-2 gap-4 mb-4">
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Audience</label>
                        <select name="audience" id="inp-audience" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white">
                             <option value="both">All Users</option>
                             <option value="guest">Guests Only</option>
                             <option value="logged_in">Logged-in Only</option>
                        </select>
                     </div>
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Priority (1-10)</label>
                        <input type="number" name="priority" id="inp-priority" value="5" min="0" max="100" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white">
                     </div>
                 </div>

                 <!-- Apps (Multi Select) -->
                 <div class="mb-4">
                     <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Target Apps</label>
                     <div class="grid grid-cols-3 gap-2 text-xs bg-slate-950 p-2 rounded border border-slate-800">
                        <label class="flex items-center gap-2"><input type="checkbox" name="apps[]" value="pyviz" checked> PyViz</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="apps[]" value="pdraw" checked> PDraw</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="apps[]" value="tgdraw" checked> TGDraw</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="apps[]" value="dviz" checked> DViz</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="apps[]" value="vdraw" checked> VDraw (Landing)</label>
                     </div>
                 </div>

                 <!-- Placements (Multi Select) -->
                 <div class="mb-4">
                     <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Placements</label>
                     <div class="grid grid-cols-2 gap-2 text-xs bg-slate-950 p-2 rounded border border-slate-800 h-32 overflow-y-auto">
                        <!-- Populated by JS -->
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="home_between_sections"> Home: Sections</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="vdraw_manual_upload"> VDraw: Manual</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="vdraw_stats_panel"> VDraw: Stats</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="pdraw_sidebar_top"> PDraw: Sidebar</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="pdraw_trace_bottom"> PDraw: Trace</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="tgdraw_top"> TGDraw: Top</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="tgdraw_bottom"> TGDraw: Bottom</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="pyviz_feedback"> PyViz: Feedback</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="pyviz_sidebar"> PyViz: Sidebar</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="dviz_levels"> DViz: Levels</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="dviz_main_right"> DViz: Right</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="pdraw_empty_state"> PDraw: Empty State</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="placements[]" value="tgdraw_empty_state"> TGDraw: Empty State</label>
                     </div>
                 </div>

                 <div class="grid grid-cols-2 gap-4">
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Date (Optional)</label>
                        <input type="datetime-local" name="start_datetime" id="inp-start" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white text-xs">
                     </div>
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Date (Optional)</label>
                        <input type="datetime-local" name="end_datetime" id="inp-end" class="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white text-xs">
                     </div>
                 </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-800">
                 <button type="submit" class="px-6 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded shadow transition">Save Ad</button>
            </div>
        </form>
    </div>
</div>

<script>
const ADS_MANAGER_URL = '../ads/ads_manager.php';

function toggleAdFields() {
    const type = document.getElementById('inp-ad-type').value;
    document.getElementById('fields-sponsor').classList.toggle('hidden', type !== 'sponsor');
    document.getElementById('fields-google').classList.toggle('hidden', type !== 'google');
    document.getElementById('fields-push').classList.toggle('hidden', type !== 'push');
}

async function loadAdsList() {
    const fd = new FormData();
    fd.append('action', 'list_ads');
    document.getElementById('ads-table-body').innerHTML = '<tr><td colspan="6" class="p-8 text-center italic">Loading...</td></tr>';
    
    try {
        const res = await fetch(ADS_MANAGER_URL, {method: 'POST', body: fd});
        const d = await res.json();
        
        if(d.status === 'success') {
             const rows = d.ads.length ? d.ads.map(ad => {
                 let statusColor = ad.status === 'active' ? 'text-green-400' : (ad.status==='paused' ? 'text-yellow-400' : 'text-red-400');
                 return `
                 <tr class="hover:bg-slate-800/50 border-b border-slate-800/50">
                    <td class="px-6 py-4">
                        <div class="font-bold text-white">${ad.ad_title}</div>
                        <div class="text-[10px] text-slate-500">ID: ${ad.ad_id} | ${ad.start_datetime ? 'Runs from ' + ad.start_datetime : 'Immediate'}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-700 text-slate-300 uppercase">${ad.ad_type}</span>
                        ${ad.network_name ? '<div class="text-[9px] mt-1">'+ad.network_name+'</div>' : ''}
                    </td>
                    <td class="px-6 py-4 text-xs max-w-[200px] truncate">
                        ${ad.placements.join(', ')}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex flex-col text-xs">
                            <span class="text-white font-mono">${ad.stats_impressions.toLocaleString()} <span class="text-slate-600 text-[9px]">Views</span></span>
                            ${ad.ad_type !== 'google' ? `<span class="text-brand-400 font-mono">${ad.stats_clicks.toLocaleString()} <span class="text-slate-600 text-[9px]">Clicks</span></span>` : ''}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <button onclick="toggleAdStatus(${ad.ad_id}, '${ad.status}')" class="${statusColor} font-bold text-xs uppercase hover:underline">
                            ${ad.status}
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button onclick="editAd(${ad.ad_id})" class="text-blue-400 hover:text-white"><i class="fa-solid fa-pen"></i></button>
                        <button onclick="deleteAd(${ad.ad_id})" class="text-red-400 hover:text-white"><i class="fa-solid fa-trash"></i></button>
                    </td>
                 </tr>
                 `;
             }).join('') : '<tr><td colspan="6" class="p-8 text-center text-slate-500 italic">No ads created yet.</td></tr>';
             document.getElementById('ads-table-body').innerHTML = rows;
        }
    } catch(e) { console.error(e); }
}

async function toggleGlobalSettings() {
    const fd = new FormData();
    fd.append('action', 'get_global_settings');
    const res = await fetch(ADS_MANAGER_URL, {method:'POST', body:fd});
    const d = await res.json();
    if(d.status==='success') {
        const s = d.settings;
        document.getElementById('set-ads-enabled').checked = s.ads_enabled == 1;
        document.getElementById('set-ip-retention').value = s.ip_retention_days;
        document.getElementById('modal-ads-settings').classList.remove('hidden');
    }
}

async function saveGlobalAdsSettings(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch(ADS_MANAGER_URL, {method:'POST', body:fd});
    const d = await res.json();
    if(d.status === 'success') {
        alert('Settings Saved');
        document.getElementById('modal-ads-settings').classList.add('hidden');
    }
}

function openAdModal() {
    document.getElementById('modal-ad-edit').classList.remove('hidden');
    document.forms[0].reset(); // Does this target correct form?
    // Safer to get by container
    document.querySelector('#modal-ad-edit form').reset();
    document.getElementById('inp-ad-id').value = '';
    document.getElementById('modal-ad-title').innerText = 'Create New Ad';
    document.getElementById('prev-ad-image-container').classList.add('hidden');
    document.getElementById('prev-ad-image-img').src = '';
    toggleAdFields();
}

async function saveAd(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
        const res = await fetch(ADS_MANAGER_URL, {method: 'POST', body: fd});
        const d = await res.json();
        if(d.status === 'success') {
            alert('Ad Saved Successfully');
            document.getElementById('modal-ad-edit').classList.add('hidden');
            loadAdsList();
        } else {
            alert('Error: ' + d.message);
        }
    } catch (e) { console.error(e); alert('Failed to save ad'); }
}

async function editAd(id) {
    const fd = new FormData();
    fd.append('action', 'get_ad');
    fd.append('ad_id', id);
    
    const res = await fetch(ADS_MANAGER_URL, {method:'POST', body:fd});
    const d = await res.json();
    if(d.status ==='success') {
        const ad = d.ad;
        openAdModal();
        document.getElementById('inp-ad-id').value = ad.ad_id;
        document.getElementById('modal-ad-title').innerText = 'Edit Ad: ' + ad.ad_title;
        document.getElementById('inp-ad-title').value = ad.ad_title;
        document.getElementById('inp-ad-type').value = ad.ad_type;
        document.getElementById('inp-status').value = ad.status;
        document.getElementById('inp-priority').value = ad.priority;
        document.getElementById('inp-audience').value = ad.audience;
        document.getElementById('inp-start').value = ad.start_datetime;
        document.getElementById('inp-end').value = ad.end_datetime;
        
        // Placements
        const pBoxes = document.querySelectorAll('input[name="placements[]"]');
        pBoxes.forEach(b => b.checked = ad.placements.includes(b.value));
        
        // Apps
        const aBoxes = document.querySelectorAll('input[name="apps[]"]');
        aBoxes.forEach(b => b.checked = ad.apps ? ad.apps.includes(b.value) : false);
        
        // Fields
        // Fields
        toggleAdFields();
        if(ad.ad_type === 'sponsor') {
            document.getElementById('inp-target-url').value = ad.ad_code || '';
            // Show image if exists
            if (ad.images && ad.images.length > 0) {
                const imgContainer = document.getElementById('prev-ad-image-container');
                const img = document.getElementById('prev-ad-image-img');
                // Assume images[0] is correct. relative to admin/ads/../.. aka root
                // Path from DB is 'uploads/ads/...'. 
                // We are in admin/ads/, we need to go ../../uploads
                img.src = '../../' + ad.images[0];
                imgContainer.classList.remove('hidden');
            } else {
                 document.getElementById('prev-ad-image-container').classList.add('hidden');
            }
        } else if (ad.ad_type === 'google') {
            document.getElementById('inp-network').value = ad.network_name;
            document.getElementById('inp-embed-code').value = ad.ad_code;
        } else if (ad.ad_type === 'push') {
             document.getElementById('inp-push-type').value = ad.display_type;
             document.getElementById('inp-push-delay').value = ad.close_delay_seconds;
             document.getElementById('inp-push-allow-close').checked = ad.allow_immediate_close == 1;
             document.getElementById('inp-push-content').value = ad.ad_code; 
        }
        
    }
}

async function deleteAd(id) {
    if(!confirm('Delete this ad permanently?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_ad');
    fd.append('ad_id', id);
    await fetch(ADS_MANAGER_URL, {method:'POST', body:fd});
    loadAdsList();
}

async function toggleAdStatus(id, current) {
    const newS = current === 'active' ? 'paused' : 'active';
    const fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('ad_id', id);
    fd.append('status', newS);
    await fetch(ADS_MANAGER_URL, {method:'POST', body:fd});
    loadAdsList();
}

// Auto load on tab switch (handled in index.php switchTab if I add logic there, or just lazy load)
// But I need to hook into existing switchTab.
// I can add an event listener for hash or just rely on manual refresh for now.
</script>
<?php
// End of main_view.php
?>
