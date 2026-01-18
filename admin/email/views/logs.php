<!-- Logs Tab -->
<div class="glass-panel rounded-xl p-6 border border-slate-700">
    <h2 class="text-xl font-bold text-white mb-6">Saved Email Logs</h2>
    <div id="logs-list" class="text-slate-400 text-sm">Loading...</div>
</div>

<!-- View Log Modal -->
<div id="modal-view-log" class="modal-overlay">
    <div class="glass-panel rounded-xl p-6 border border-slate-700 w-full max-w-4xl mx-4">
        <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2">View Log</h3>
        <div id="view_log_content" class="h-96 overflow-y-auto bg-slate-900 border border-slate-700 rounded-xl p-4 font-mono text-xs text-slate-400 whitespace-pre-wrap mb-4"></div>
        <div class="flex justify-end pt-4 border-t border-slate-700">
            <button onclick="closeModal('modal-view-log')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-sm transition">Close</button>
        </div>
    </div>
</div>