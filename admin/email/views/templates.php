<!-- Templates Tab -->
<div class="glass-panel rounded-xl p-6 border border-slate-700">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-white">Email Templates</h2>
        <button onclick="openTemplateModal()" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded text-sm transition">
            <i class="fa-solid fa-plus mr-2"></i>New Template
        </button>
    </div>
    <div id="templates-list" class="text-slate-400 text-sm">Loading...</div>
</div>

<!-- Template Modal -->
<div id="modal-template" class="modal-overlay">
    <div class="glass-panel rounded-xl p-6 border border-slate-700 w-full max-w-2xl mx-4">
        <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2">Manage Template</h3>
        <form id="form-template" onsubmit="saveTemplate(event)" class="space-y-4">
            <input type="hidden" name="id" id="tpl_id">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Template Title (Subject)</label>
                <input type="text" name="title" id="tpl_title" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">HTML Body (Supports {name}, {email})</label>
                <textarea name="html_body" id="tpl_html" rows="5" class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none font-mono"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Plain Text Body</label>
                <textarea name="text_body" id="tpl_text" rows="3" class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none font-mono"></textarea>
            </div>
            <div class="flex items-center gap-3 p-3 bg-slate-900 rounded border border-slate-700">
                <input type="checkbox" name="is_active" id="tpl_active" value="1" class="w-5 h-5 text-brand-600 bg-slate-800 border-slate-600 rounded focus:ring-brand-500">
                <label for="tpl_active" class="text-slate-300 font-medium">Is Active</label>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-700">
                <button type="button" onclick="closeModal('modal-template')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-sm transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded text-sm transition">Save Template</button>
            </div>
        </form>
    </div>
</div>