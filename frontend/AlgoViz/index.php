<?php
require_once __DIR__ . '/../../auth/Gatekeeper.php';
Gatekeeper::protect();
$currentUser = Auth::user();
require_once __DIR__ . '/../../auth/AppHelper.php';
$apps = AppHelper::getAllApps();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlgoViz - Interactive Algorithm Visualization</title>
    <!-- Tailwind CSS for Header Compatibility -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], },
                    colors: {
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 900: '#1e3a8a', }
                    }
                }
            }
        }
    </script>
    <!-- Error Handler -->
    <script>
        window.addEventListener('error', function(e) {
            console.error(e.message);
            const errDiv = document.createElement('div');
            errDiv.style.position = 'fixed';
            errDiv.style.bottom = '10px';
            errDiv.style.right = '10px';
            errDiv.style.background = 'red';
            errDiv.style.color = 'white';
            errDiv.style.padding = '10px';
            errDiv.style.zIndex = '9999';
            errDiv.textContent = 'JS Error: ' + e.message;
            document.body.appendChild(errDiv);
        });
    </script>
    <style>
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.05); }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <link rel="stylesheet" href="css/algoviz.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- GIF Export Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gif.js@0.2.0/dist/gif.min.js"></script>
</head>
<body class="bg-slate-950 text-slate-100 overflow-hidden">
    <!-- Standard VDraw Header -->
    <header class="h-16 border-b border-slate-800/50 flex items-center justify-between px-3 md:px-8 glass-panel z-10">
        <div class="flex items-center shrink-0">
            <i class="fa-solid fa-layer-group text-brand-500 text-xl mr-3"></i>
            <h2 class="text-lg font-semibold text-slate-200">AlgoViz <span class="text-slate-500 text-sm font-normal">| Analyze & Visualize</span></h2>
        </div>
        
        <!-- Module Switcher & User Profile -->
        <div class="flex items-center space-x-2 md:space-x-4 overflow-hidden justify-end flex-1 pl-2">
            <!-- Desktop Nav -->
            <div class="hidden md:flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700 overflow-x-auto scrollbar-hide">
                <a href="../../" class="px-2 md:px-3 py-1 text-[10px] md:text-xs font-bold rounded text-slate-400 hover:text-white hover:bg-slate-700 transition-all whitespace-nowrap"><i class="fa-solid fa-house mr-1"></i> Vdraw Home</a>
                <?php foreach($apps as $app): 
                    $isActive = ($app['name'] === 'AlgoViz'); 
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
                    <option value="../<?php echo $app['name']; ?>/" <?php echo ($app['name'] === 'AlgoViz') ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($app['nav_title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php $dashboardLink = ($currentUser['role'] === 'admin') ? '../../admin/dashboard/' : '../../user/dashboard/'; ?>
            <a href="<?php echo $dashboardLink; ?>" class="flex items-center gap-2 text-slate-300 hover:text-white transition group ml-2">
                <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center font-bold text-white shadow-lg group-hover:bg-brand-500">
                    <?php echo substr($currentUser['full_name'] ?? 'U', 0, 1); ?>
                </div>
                <span class="hidden md:inline font-medium text-xs"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'User'); ?></span>
            </a>
        </div>
    </header>

    <!-- App Container -->
    <div id="app-container">
        
        <!-- Left Panel: Controls -->
        <aside id="left-panel">
            <header class="panel-header">
                <h1>AlgoViz</h1>
            </header>
            
            <div class="controls-content">
                <!-- Controls will be injected here -->
                <div class="control-group">
                    <label for="data-type">Data Type</label>
                    <select id="data-type">
                        <option value="numbers">Numbers</option>
                        <option value="text">Text (Strings)</option>
                    </select>
                </div>

                <div class="control-group">
                    <label for="algorithm-select">Algorithm</label>
                    <select id="algorithm-select">
                        <!-- Populated dynamically -->
                    </select>
                </div>

                <div class="control-group">
                    <label for="input-data">Input Values (Max 50)</label>
                    <textarea id="input-data" placeholder="e.g. 5, 12, 8, 1..."></textarea>
                    <div id="input-error" class="error-msg"></div>
                </div>

                 <div class="control-group" id="algo-options">
                    <!-- Dynamic Options like Search Key, Sort Order -->
                </div>

                 <div class="control-group">
                    <label>Loop Construct</label>
                    <div class="toggle-group">
                        <button class="toggle-btn active" data-value="for">For</button>
                        <button class="toggle-btn" data-value="while">While</button>
                    </div>
                </div>

                <div class="control-group hidden" id="recursion-group">
                     <label class="checkbox-container">
                        <input type="checkbox" id="recursion-toggle">
                        <span class="checkmark"></span>
                        Show Recursion Tree
                    </label>
                </div>

                <div class="action-buttons">
                    <button id="btn-simulate" class="btn btn-primary">Simulate</button>
                    <button id="btn-reset-all" class="btn btn-secondary">Reset</button>
                </div>
            </div>
        </aside>

        <!-- Main Playground: Visualization -->
        <main id="main-playground">
            <!-- Empty State (Visible Initially) -->
            <div id="empty-state" class="empty-state-container">
                <div class="empty-state-icon">
                    <i class="fa-solid fa-diagram-project"></i>
                </div>
                <h2 class="empty-state-title">Algorithms Explained Visually</h2>
                <p class="empty-state-desc">Learn algorithms with step-by-step animations, Python code highlighting, and clear complexity analysis.</p>
            </div>

            <!-- Active State (Hidden Initially) -->
            <div id="active-state" class="hidden-initial">
                <div class="playground-header">
                    <div class="playback-controls">
                        <button id="btn-play-pause" class="icon-btn" title="Play/Pause">⏯</button>
                        <button id="btn-step-prev" class="icon-btn" title="Step Back">⏮</button>
                        <button id="btn-step-next" class="icon-btn" title="Step Forward">⏭</button>
                        <input type="range" id="speed-control" min="1" max="5" value="3" title="Speed">
                        <button id="btn-reset-view" class="icon-btn" title="Reset View">↺</button>
                    </div>
                    <div class="view-controls">
                        <button id="btn-text-size" class="icon-btn" title="Toggle Text Size">Aa</button>
                        <button id="btn-show-index" class="icon-btn active" title="Toggle Indexes">#</button>
                        <button id="btn-export-gif" class="icon-btn" title="Export GIF" style="display:none;">GIF</button>
                        <button id="btn-export-pdf" class="icon-btn" title="Export PDF">PDF</button>
                        <button id="btn-fullscreen" class="icon-btn" title="Fullscreen">⛶</button>
                    </div>
                </div>

                <div id="viz-canvas">
                    <!-- Algorithm visualization renders here -->
                    <div class="placeholder-msg">Select an algorithm and click Simulate</div>
                    <!-- Scroll to Top Button -->
                    <button id="btn-scroll-top" class="scroll-top-btn" title="Scroll to Top" style="display: none;">
                        <i class="fa-solid fa-arrow-up"></i>
                    </button>
                </div>
                
                <div id="status-bar">
                    <span id="step-counter">Step: 0 / 0</span>
                    <span id="status-message">Ready</span>
                </div>
                <div id="info-banner" style="display: none; background: linear-gradient(90deg, #1e3a5f, #2d4a6f); color: #fbbf24; padding: 8px 16px; border-radius: 6px; margin-top: 10px; font-size: 13px; text-align: center; border: 1px solid #3d5a7f;">
                    <span id="info-banner-text"></span>
                </div>
            </div>
        </main>

        <!-- Right Panel: Code & Complexity (Hidden Initially) -->
        <aside id="right-panel" class="hidden-initial">
            
            <!-- Code Playground -->
            <section id="code-playground">
                <div class="panel-header">
                    <h2>Python Code</h2>
                </div>
                <div class="code-container">
                    <pre><code id="code-display" class="language-python"># Code will appear here...</code></pre>
                </div>
            </section>

            <!-- Complexity Panel -->
            <section id="complexity-panel">
                <div class="panel-header">
                    <h2>Complexity & Properties</h2>
                </div>
                <div class="complexity-content">
                    <div class="stat-row">
                        <span class="label">Time (Avg):</span>
                        <span class="value" id="stat-time-avg">-</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">Time (Worst):</span>
                        <span class="value" id="stat-time-worst">-</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">Space:</span>
                        <span class="value" id="stat-space">-</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">Stable:</span>
                        <span class="value" id="stat-stable">-</span>
                    </div>
                     <div class="stat-row">
                        <span class="label">Notes:</span>
                        <p class="value note-text" id="stat-note">-</p>
                    </div>
                </div>
            </section>

        </aside>

    </div>

    <!-- Scripts -->
    <script src="js/app.js?v=<?php echo time(); ?>" type="module"></script>
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
