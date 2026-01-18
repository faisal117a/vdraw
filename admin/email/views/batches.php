<!-- Batches Tab -->
<div class="glass-panel rounded-xl p-6 border border-slate-700">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-white">Email Batches</h2>
        <button onclick="openBatchModal()" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded text-sm transition">
            <i class="fa-solid fa-plus mr-2"></i>New Batch
        </button>
    </div>
    <div id="batches-list" class="text-slate-400 text-sm">Loading...</div>
</div>

<!-- Batch Modal -->
<div id="modal-batch" class="modal-overlay">
    <div class="glass-panel rounded-xl p-6 border border-slate-700 w-full max-w-3xl mx-4">
        <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2">Manage Batch</h3>
        <form id="form-batch" onsubmit="saveBatch(event)" class="space-y-4">
            <input type="hidden" name="id" id="batch_id">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Title</label>
                <input type="text" name="title" id="batch_title" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Description (Optional)</label>
                <input type="text" name="description" id="batch_desc" class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
            </div>
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Email List (comma separated)</label>
                    <button type="button" onclick="openUserFilterModal()" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-xs transition">
                        <i class="fa-solid fa-search mr-1"></i>Add Users from DB
                    </button>
                </div>
                <textarea name="email_list_source" id="batch_source" rows="6" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none font-mono"></textarea>
                <p class="text-xs text-slate-600 mt-1">Editing source list does not affect running batches.</p>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-700">
                <button type="button" onclick="closeModal('modal-batch')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-sm transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded text-sm transition">Save Batch</button>
            </div>
        </form>
    </div>
</div>

<!-- User Filter Modal -->
<div id="modal-filter-users" class="modal-overlay" style="z-index: 110;">
    <div class="glass-panel rounded-xl p-6 border border-slate-700 w-full max-w-4xl mx-4">
        <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2">Search & Add Users</h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Role</label>
                <select id="filter_role" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none">
                    <option value="">All Roles</option>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Search</label>
                <input type="text" id="filter_search" placeholder="Email/Name..." class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">From Date</label>
                <input type="date" id="filter_date_start" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">To Date</label>
                <input type="date" id="filter_date_end" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 mb-4 p-3 bg-slate-900 rounded border border-slate-700">
            <label class="flex items-center gap-2 text-slate-300 text-sm">
                <input type="checkbox" id="filter_active" checked class="w-4 h-4 text-brand-600 bg-slate-800 border-slate-600 rounded">
                Active Only
            </label>
            <label class="flex items-center gap-2 text-slate-300 text-sm">
                <input type="checkbox" id="filter_verified" checked class="w-4 h-4 text-brand-600 bg-slate-800 border-slate-600 rounded">
                Verified Only
            </label>
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500 uppercase font-bold">Limit:</label>
                <select id="filter_limit" class="bg-slate-800 border border-slate-700 rounded p-1 text-sm text-white outline-none">
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                    <option value="-1">All</option>
                </select>
            </div>
            <button onclick="searchUsers()" class="ml-auto px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded text-sm transition">
                <i class="fa-solid fa-search mr-1"></i>Search
            </button>
        </div>

        <div id="user-results" class="h-64 overflow-y-auto bg-slate-900 border border-slate-700 rounded p-3 mb-4">
            <p class="text-slate-500 text-sm italic">Enter filters and click Search.</p>
        </div>

        <div class="flex justify-between items-center pt-4 border-t border-slate-700">
            <span class="text-slate-400 text-sm">Selected: <b id="selected_count" class="text-white">0</b></span>
            <div class="flex gap-3">
                <button onclick="closeModal('modal-filter-users')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-sm transition">Cancel</button>
                <button onclick="addSelectedUsers()" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white font-bold rounded text-sm transition">Add to Batch</button>
            </div>
        </div>
    </div>
</div>