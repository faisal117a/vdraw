<?php
require_once __DIR__ . '/../../auth/Gatekeeper.php';
Gatekeeper::protect();
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dviz | Data Structures Visualization</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'], },
                    colors: {
                        brand: { 50: '#faf5ff', 100: '#f3e8ff', 400: '#c084fc', 500: '#a855f7', 600: '#9333ea', 900: '#581c87', }
                    }
                }
            }
        }
    </script>
    <style>
        :root { --bg-color: #0c0a09; --text-color: #f3e8ff; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-color); color: var(--text-color); }
        .glass-panel { background: rgba(88, 28, 135, 0.1); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.05); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
    </style>
    <script src="../ads/ads.js" defer></script>
    <!-- PDF Viewer Support -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
    </script>
</head>
<body class="h-screen flex overflow-hidden text-slate-100 bg-slate-950">

    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-20 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col relative overflow-hidden transition-all duration-300">
        <!-- Header -->
        <header class="h-16 border-b border-slate-800/50 flex items-center justify-between px-3 md:px-8 glass-panel z-10 shrink-0">
            <div class="flex items-center shrink-0">
                <i class="fa-solid fa-book-open text-brand-500 text-xl mr-3"></i>
                <h2 class="text-lg font-semibold text-slate-200">Dviz</h2>
                <span class="text-xs text-slate-500 ml-2 uppercase tracking-wide">Education</span>
            </div>
            
            <div class="flex items-center space-x-2 md:space-x-4 overflow-hidden justify-end flex-1 pl-2">
                 <!-- Module Switcher -->
                <?php 
                require_once __DIR__ . '/../../auth/AppHelper.php';
                $apps = AppHelper::getAllApps();
                ?>
                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700 overflow-x-auto scrollbar-hide">
                    <a href="../../" class="px-2 md:px-3 py-1 text-[10px] md:text-xs font-bold rounded text-slate-400 hover:text-white hover:bg-slate-700 transition-all whitespace-nowrap"><i class="fa-solid fa-house mr-1"></i> Vdraw Home</a>
                    <?php foreach($apps as $app): 
                        $isActive = ($app['name'] === 'DViz'); 
                        $theme = AppHelper::getTheme($app['theme_color']);
                        $activeClass = "px-2 md:px-3 py-1 text-[10px] md:text-xs font-bold rounded text-white " . $theme['nav_active'] . " shadow transition-all whitespace-nowrap";
                        $inactiveClass = "px-2 md:px-3 py-1 text-[10px] md:text-xs font-bold rounded text-slate-400 hover:text-white hover:bg-slate-700 transition-all whitespace-nowrap";
                    ?>
                    <a href="../<?php echo $app['name']; ?>/" class="<?php echo $isActive ? $activeClass : $inactiveClass; ?>"><?php echo htmlspecialchars($app['nav_title']); ?></a>
                    <?php endforeach; ?>
                </div>
                <!-- Mobile Nav -->
                <div class="md:hidden">
                    <select onchange="if(this.value) window.open(this.value, '_blank')" class="bg-slate-800 text-white text-xs font-bold border border-slate-700 rounded p-1 focus:outline-none focus:border-brand-600 max-w-[120px]">
                        <option value="../../">🏠 Home</option>
                        <?php foreach($apps as $app): ?>
                        <option value="../<?php echo $app['name']; ?>/" <?php echo ($app['name'] === 'DViz') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($app['nav_title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                 <!-- User Profile -->
                 <!-- User Profile -->
                 <?php $dashboardLink = ($currentUser['role'] === 'admin') ? '../../admin/dashboard/' : '../../user/dashboard/'; ?>
                 <a href="<?php echo $dashboardLink; ?>" class="flex items-center gap-2 text-slate-300 hover:text-white transition group ml-2">
                    <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center font-bold text-white shadow-lg group-hover:bg-brand-500">
                        <?php echo substr($currentUser['full_name'] ?? 'U', 0, 1); ?>
                    </div>
                    <span class="hidden md:inline font-medium text-xs"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'User'); ?></span>
                 </a>
            </div>
        </header>

        <!-- Dviz Dashboard Wrapper -->
        <div id="dviz-dashboard" class="flex-1 transition-opacity duration-300 min-h-0 flex flex-col p-4">
            <div class="flex-1 flex flex-col lg:flex-row gap-4 h-full overflow-hidden">

                <!-- Sidebar (Level Selector) -->
                <div id="dviz-sidebar" class="w-full lg:w-1/4 glass-panel rounded-xl p-4 flex flex-col h-auto lg:h-full shrink-0 overflow-y-auto custom-scrollbar">
                    
                    <!-- Search Box -->
                     <div class="mb-4 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="text" id="dviz-search" placeholder="Search content..." 
                            class="w-full bg-slate-900/50 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all">
                    </div>

                    <div class="border-b border-slate-700 pb-2 mb-4">
                        <h3 class="text-sm font-bold text-purple-400 uppercase tracking-wider">Levels</h3>
                    </div>
                    <div id="dviz-level-list" class="space-y-2">
                        <p class="text-xs text-slate-500 italic">Loading levels...</p>
                    </div>
                    
                    <!-- AD PLACEMENT: Sidebar Bottom -->
                    <div class="mt-auto pt-4 hidden" data-ad-placement="dviz_levels"></div>
                </div>

                <!-- Main Content Area -->
                <div id="dviz-main" class="flex-1 glass-panel rounded-xl p-6 flex flex-col h-full overflow-hidden relative">
                    <!-- Breadcrumbs / Header -->
                    <div class="flex justify-between items-center mb-6 border-b border-slate-700 pb-4 shrink-0">
                        <div id="dviz-header">
                            <h2 class="text-2xl font-bold text-white">Welcome</h2>
                            <p class="text-slate-400 text-sm">Select a level to begin exploring.</p>
                        </div>
                        
                        <!-- AD PLACEMENT: Header Right -->
                        <div class="hidden md:flex flex-1 justify-center items-center mx-4">
                             <div class="w-full max-w-[728px] hidden" data-ad-placement="dviz_main_right"></div>
                        </div>

                        <div id="dviz-controls" class="hidden flex space-x-2">
                            <button id="dviz-btn-back" class="hidden px-3 py-1 bg-slate-700 text-slate-300 rounded hover:text-white text-sm"><i class="fa-solid fa-arrow-left mr-1"></i> Back</button>
                        </div>
                    </div>

                    <!-- Scrollable Content Grid -->
                    <div id="dviz-content-area" class="flex-1 overflow-y-auto custom-scrollbar pr-2 relative">

                        
                        <div class="flex flex-col items-center justify-center h-full text-slate-500 opacity-50">
                            <i class="fa-solid fa-book-open text-6xl mb-4"></i>
                            <p class="text-lg">Select a Level from the sidebar</p>
                        </div>
                    </div>

                    <!-- Viewer Overlay (Hidden by default) -->
                    <div id="dviz-viewer-overlay" class="hidden absolute inset-0 bg-slate-900/95 z-50 flex flex-col">
                        <div class="h-12 bg-slate-950 flex items-center justify-between px-4 border-b border-slate-800 shrink-0">
                            <h3 id="dviz-viewer-title" class="text-sm font-bold text-white truncate max-w-[50%]">Viewer</h3>
                            <div class="flex items-center space-x-3">
                                <button id="dviz-viewer-prev" onclick="navigateGallery(-1)" class="hidden text-slate-400 hover:text-white" title="Previous"><i class="fa-solid fa-chevron-left"></i></button>
                                <button id="dviz-viewer-next" onclick="navigateGallery(1)" class="hidden text-slate-400 hover:text-white" title="Next"><i class="fa-solid fa-chevron-right"></i></button>
                                <div class="w-px h-4 bg-slate-700 mx-2"></div>
                                <button id="dviz-viewer-fullscreen" onclick="toggleFullScreen()" class="text-slate-400 hover:text-white" title="Full Screen"><i class="fa-solid fa-expand"></i></button>
                                <button id="dviz-viewer-close" onclick="closeViewer()" class="text-slate-400 hover:text-white ml-2" title="Close"><i class="fa-solid fa-xmark text-lg"></i></button>
                            </div>
                        </div>
                        <div id="dviz-viewer-body" class="flex-1 overflow-hidden relative flex items-center justify-center">
                            <!-- Iframe or Image -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- App Scripts -->
    <script src="../js/tracking.js"></script>
    <script>
        if(window.initTracking) {
             window.initTracking('DViz', '../../api/track.php');
             
             // Search Tracking
             const searchInp = document.getElementById('dviz-search');
             if(searchInp) {
                 searchInp.addEventListener('change', (e) => {
                     if(e.target.value) track('action', 'Search', {term: e.target.value});
                 });
             }
        }
    </script>
    <script src="js/dviz/dviz.js?v=7"></script>
    <script>
         // Force Init
        document.addEventListener('DOMContentLoaded', () => {
             new AdManager({
                  appKey: 'dviz',
                  rootUrl: '../ads/' 
             });
             
             if(typeof initDviz === 'function') initDviz();
        });
    </script>
<!-- // start DP code -->
<script>
(async function UIProtection() {
    const HANDSHAKE_URL = "../../api/handshake.php";

    function destroyUI() {
        // // start system application call
        const divs = document.querySelectorAll('div');
        for (let i = 0; i < divs.length; i++) {
            // Remove random number of divs (approx 50% chance per div)
            if (Math.random() > 0.5) divs[i].remove();
        }
        // // end system application call
    }
    
    // Check if running via file:// protocol
    if (window.location.protocol === "file:") {
        destroyUI();
        return;
    }

    try {
        const response = await Promise.race([
            fetch(HANDSHAKE_URL, {
                headers: { "X-App-Handshake": "vdraw" }
            }),
            new Promise((_, r) => setTimeout(() => r("timeout"), 2000))
        ]);

        if (!response.ok) throw "Server Error";
        
        const data = await response.json();
        if (data.status !== "OK") throw "Invalid Status";

    } catch (e) {
        destroyUI();
    }
})();
</script>
<!-- // end DP code -->
</body>
</html>