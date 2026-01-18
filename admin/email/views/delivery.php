<!-- Delivery Tab -->
<div class="glass-panel rounded-xl p-6 border border-slate-700">
    <h2 class="text-xl font-bold text-white mb-6">Email Delivery Manager</h2>

    <!-- Row 1: Selection Dropdowns -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 bg-slate-900 rounded-xl border border-slate-700">
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">1. Select Batch</label>
            <select id="delivery_batch" onchange="loadBatchSnapshotInfo()" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none"></select>
            <p id="batch_info" class="text-xs text-brand-400 mt-1">Select a batch...</p>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">2. Select Template</label>
            <select id="delivery_template" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none"></select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">3. Select SMTP</label>
            <select id="delivery_smtp" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none"></select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">4. Delay Per Email</label>
            <select id="delivery_delay" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none">
                <option value="0">No Delay</option>
                <option value="500">0.5 Seconds</option>
                <option value="1000" selected>1.0 Seconds</option>
                <option value="2000">2.0 Seconds</option>
                <option value="3000">3.0 Seconds</option>
            </select>
        </div>
    </div>

    <!-- Row 2: Safety Settings -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-4 bg-slate-900/50 rounded-xl border border-slate-700">
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Max Emails Per Run</label>
            <select id="delivery_max_per_run" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none">
                <option value="50">50</option>
                <option value="100" selected>100</option>
                <option value="200">200</option>
                <option value="500">500</option>
                <option value="-1">No Limit</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Auto-Stop After Failures</label>
            <select id="delivery_fail_threshold" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white outline-none">
                <option value="5">5 Consecutive</option>
                <option value="10" selected>10 Consecutive</option>
                <option value="20">20 Consecutive</option>
                <option value="-1">Never (Dangerous)</option>
            </select>
        </div>
        <div class="flex items-end">
            <button onclick="testSMTPFromDelivery()" class="w-full px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded text-sm transition">
                <i class="fa-solid fa-plug mr-1"></i>Test SMTP
            </button>
        </div>
        <div class="flex items-end">
            <label class="flex items-center gap-2 text-slate-300 text-sm p-2 bg-slate-800 rounded border border-slate-700 w-full justify-center">
                <input type="checkbox" id="delivery_autoscroll" checked class="w-4 h-4 text-brand-600 bg-slate-800 border-slate-600 rounded">
                Auto-Scroll Log
            </label>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-3 mb-6">
        <button id="btn_init_snapshot" onclick="initSnapshot()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-sm transition border border-slate-600">
            <i class="fa-solid fa-refresh mr-1"></i>Initialize Snapshot
        </button>
        <button id="btn_start" onclick="startDelivery()" disabled class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white font-bold rounded text-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fa-solid fa-play mr-1"></i>Start Sending
        </button>
        <button id="btn_stop" onclick="stopDelivery()" disabled class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-bold rounded text-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fa-solid fa-stop mr-1"></i>STOP
        </button>
        <button id="btn_save_log" onclick="saveLog()" disabled class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-sm transition border border-slate-600 disabled:opacity-50">
            <i class="fa-solid fa-save mr-1"></i>Save Log
        </button>
        <button id="btn_reset_failed" onclick="resetFailed()" style="display:none;" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded text-sm transition">
            <i class="fa-solid fa-redo mr-1"></i>Reset Failed
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 text-center">
            <div id="stat_total" class="text-3xl font-bold text-white">0</div>
            <div class="text-xs text-slate-500 uppercase font-bold">Total</div>
        </div>
        <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 text-center">
            <div id="stat_sent" class="text-3xl font-bold text-green-400">0</div>
            <div class="text-xs text-slate-500 uppercase font-bold">Sent</div>
        </div>
        <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 text-center">
            <div id="stat_failed" class="text-3xl font-bold text-red-400">0</div>
            <div class="text-xs text-slate-500 uppercase font-bold">Failed</div>
        </div>
    </div>

    <!-- Live Console -->
    <div id="delivery_console" class="h-64 overflow-y-auto bg-slate-900 border border-slate-700 rounded-xl p-4 font-mono text-xs text-slate-400 whitespace-pre-wrap">Waiting to start...</div>
</div>