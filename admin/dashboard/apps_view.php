<div id="tab-apps" class="tab-content hidden">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-white">App Management</h2>
        <button onclick="openEditApp()" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 rounded text-white font-bold text-sm shadow transition">
            <i class="fa-solid fa-plus mr-2"></i> Add New App
        </button>
    </div>

    <?php
    $appConn = DB::connect();
    $appsRes = $appConn->query("SELECT * FROM apps ORDER BY display_order ASC");
    $allApps = [];
    if($appsRes) while($r=$appsRes->fetch_assoc()) $allApps[] = $r;
    ?>

    <div class="glass-panel rounded-xl overflow-hidden mb-8">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-400">
            <thead class="bg-slate-800/50 text-slate-200 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3">Order</th>
                    <th class="px-6 py-3">App Name (System)</th>
                    <th class="px-6 py-3">Nav Title</th>
                    <th class="px-6 py-3">Home Box</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <?php foreach($allApps as $app): ?>
                <tr class="hover:bg-slate-800/30">
                     <td class="px-6 py-3 font-mono"><?php echo $app['display_order']; ?></td>
                     <td class="px-6 py-3">
                        <div class="font-bold text-white"><?php echo htmlspecialchars($app['name']); ?></div>
                        <div class="text-xs text-slate-500">frontend/<?php echo htmlspecialchars($app['name']); ?>/</div>
                     </td>
                     <td class="px-6 py-3 text-white"><?php echo htmlspecialchars($app['nav_title']); ?></td>
                     <td class="px-6 py-3">
                        <div class="font-bold text-white mb-1"><i class="<?php echo htmlspecialchars($app['icon_class']); ?> mr-2"></i> <?php echo htmlspecialchars($app['home_title']); ?></div>
                        <div class="text-xs text-slate-500 truncate max-w-xs"><?php echo htmlspecialchars($app['home_description']); ?></div>
                     </td>
                     <td class="px-6 py-3">
                        <span class="px-2 py-1 rounded text-[10px] font-bold <?php echo $app['is_active']?'bg-green-900 text-green-300':'bg-red-900 text-red-300'; ?>">
                            <?php echo $app['is_active']?'Active':'Inactive'; ?>
                        </span>
                     </td>
                     <td class="px-6 py-3 text-right space-x-2">
                        <button onclick='openEditApp(<?php echo json_encode($app, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="text-blue-400 hover:text-white"><i class="fa-solid fa-pen-to-square"></i></button>
                        <form action="apps_actions.php" method="POST" class="inline" onsubmit="return confirm('Delete app <?php echo htmlspecialchars($app['name']); ?>?');">
                            <input type="hidden" name="action" value="delete_app">
                            <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                            <button type="submit" class="text-red-400 hover:text-white"><i class="fa-solid fa-trash"></i></button>
                        </form>
                     </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($allApps)): ?><tr><td colspan="6" class="p-4 text-center italic">No apps found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Modal: Edit App -->
<div id="modal-app" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center">
    <div class="glass-panel w-full max-w-lg p-6 rounded-xl relative bg-slate-900 border border-slate-700 max-h-[90vh] overflow-y-auto">
        <button onclick="document.getElementById('modal-app').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
        <h2 class="text-xl font-bold text-white mb-4" id="modal-app-title">Add/Edit App</h2>
        <form action="apps_actions.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="save_app">
            <input type="hidden" name="app_id" id="app-id">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">System Name (Folder)</label>
                    <input type="text" name="name" id="app-name" placeholder="PyViz" required class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white">
                    <p class="text-[10px] text-slate-500 mt-1">Found in frontend/[Name]/</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Nav Bar Title</label>
                    <input type="text" name="nav_title" id="app-nav-title" placeholder="PyViz" required class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Home Box Title</label>
                    <input type="text" name="home_title" id="app-home-title" placeholder="PyViz Explainer" required class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Theme Color</label>
                    <select name="theme_color" id="app-theme-color" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white">
                        <option value="blue">Blue (Stats)</option>
                        <option value="green">Green (Linear)</option>
                        <option value="amber">Amber/Orange (Graph)</option>
                        <option value="sky">Sky/Blue (PyViz)</option>
                        <option value="purple">Purple/Pink (DViz)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1">Icon Class (FontAwesome)</label>
                <div class="flex gap-2">
                    <input type="text" name="icon_class" id="app-icon-class" placeholder="fa-brands fa-python" required class="flex-1 bg-slate-800 border border-slate-700 rounded p-2 text-white">
                    <div class="w-10 h-10 bg-slate-800 border border-slate-700 rounded flex items-center justify-center text-white" id="icon-preview">
                        <i class="fa-solid fa-question"></i>
                    </div>
                </div>
            </div>
            
            <div>
                 <label class="block text-xs font-bold text-slate-400 mb-1">Home Description</label>
                 <textarea name="home_description" id="app-home-desc" rows="3" required class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white resize-none"></textarea>
            </div>

            <div class="flex items-center gap-6">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-400 mb-1">Display Order</label>
                    <input type="number" name="display_order" id="app-order" value="10" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white">
                </div>
                <div class="flex items-center gap-2 mt-5">
                    <input type="checkbox" name="is_active" id="app-is-active" value="1" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600">
                    <label for="app-is-active" class="text-sm text-white font-bold">Is Active?</label>
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-3 rounded shadow transition">Save App Details</button>
        </form>
    </div>
</div>

<script>
    function openEditApp(app = null) {
        document.getElementById('modal-app').classList.remove('hidden');
        if (app) {
            document.getElementById('modal-app-title').innerText = 'Edit App: ' + app.name;
            document.getElementById('app-id').value = app.id;
            document.getElementById('app-name').value = app.name;
            document.getElementById('app-nav-title').value = app.nav_title;
            document.getElementById('app-home-title').value = app.home_title;
            document.getElementById('app-home-desc').value = app.home_description;
            document.getElementById('app-icon-class').value = app.icon_class;
            document.getElementById('app-theme-color').value = app.theme_color || 'blue';
            document.getElementById('app-order').value = app.display_order;
            document.getElementById('app-is-active').checked = app.is_active == 1;
        } else {
            document.getElementById('modal-app-title').innerText = 'Add New App';
            document.getElementById('app-id').value = '';
            document.getElementById('app-name').value = '';
            document.getElementById('app-nav-title').value = '';
            document.getElementById('app-home-title').value = '';
            document.getElementById('app-home-desc').value = '';
            document.getElementById('app-icon-class').value = '';
            document.getElementById('app-theme-color').value = 'blue';
            document.getElementById('app-order').value = '10';
            document.getElementById('app-is-active').checked = true;
        }
        updateIconPreview();
    }
    
    document.getElementById('app-icon-class').addEventListener('input', updateIconPreview);
    
    function updateIconPreview() {
        const cls = document.getElementById('app-icon-class').value || 'fa-solid fa-question';
        document.getElementById('icon-preview').innerHTML = `<i class="${cls}"></i>`;
    }
</script>
