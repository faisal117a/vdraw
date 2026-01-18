<!-- SMTP Tab -->
<div class="glass-panel rounded-xl p-6 border border-slate-700">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-white">SMTP Profiles</h2>
        <button onclick="openSMTPModal()" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded text-sm transition">
            <i class="fa-solid fa-plus mr-2"></i>New Profile
        </button>
    </div>
    <div id="smtp-list" class="text-slate-400 text-sm">Loading...</div>
</div>

<!-- SMTP Modal -->
<div id="modal-smtp" class="modal-overlay">
    <div class="glass-panel rounded-xl p-6 border border-slate-700 w-full max-w-xl mx-4">
        <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2">SMTP Profile</h3>
        <form id="form-smtp" onsubmit="saveSMTP(event)" class="space-y-4">
            <input type="hidden" name="id" id="smtp_id">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Profile Name</label>
                <input type="text" name="profile_name" id="smtp_name" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Host</label>
                    <input type="text" name="smtp_host" id="smtp_host" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Port</label>
                    <input type="number" name="smtp_port" id="smtp_port" value="587" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Username</label>
                <input type="text" name="smtp_username" id="smtp_user" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Password (Leave blank to keep unchanged)</label>
                <input type="password" name="smtp_password" id="smtp_pass" class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sender Email (From)</label>
                    <input type="email" name="sender_email" id="smtp_sender_email" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sender Name</label>
                    <input type="text" name="sender_name" id="smtp_sender_name" value="VDraw System" class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white focus:border-brand-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Encryption</label>
                <select name="encryption" id="smtp_enc" class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-sm text-white outline-none">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="none">None</option>
                </select>
            </div>
            <div class="flex items-center gap-3 p-3 bg-slate-900 rounded border border-slate-700">
                <input type="checkbox" name="is_default" id="smtp_default" value="1" class="w-5 h-5 text-brand-600 bg-slate-800 border-slate-600 rounded focus:ring-brand-500">
                <label for="smtp_default" class="text-slate-300 font-medium">Set as Default</label>
            </div>
            <div class="flex justify-between items-center pt-4 border-t border-slate-700">
                <button type="button" onclick="testSMTP()" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded text-sm transition">
                    <i class="fa-solid fa-plug mr-1"></i>Test Connection
                </button>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal('modal-smtp')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-sm transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded text-sm transition">Save Profile</button>
                </div>
            </div>
        </form>
    </div>
</div>