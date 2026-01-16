<?php
// PHP logic moved to download.php
require_once __DIR__ . '/../../auth/Gatekeeper.php';
Gatekeeper::protect();
$currentUser = Auth::user();


// Feature Toggles removed as per user request (Authenticated users get all features)
$sttEnabled = true;
$editorEnabled = true;
// $regEnabled = true; // Not used in this file but keeping variable for consistency if used later

?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python Explainer – Learn Python with Visual Execution</title>
    <meta name="description" content="Understand Python concepts step by step using visual explanations, examples, and interactive execution designed for students and teachers.">
    <meta name="keywords" content="python explainer, python basics, learn python visually, python for students, python for teachers">
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
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 900: '#1e3a8a', }
                    }
                }
            }
        }
    </script>
    <style>
        :root { --bg-color: #0f172a; --text-color: #e2e8f0; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-color); color: var(--text-color); }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.05); }
        /* Custom Scrollbar Styles for PyViz App */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(30, 41, 59, 0.5); border-radius: 3px; margin: 4px 0; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%); border-radius: 3px; transition: background 0.3s ease; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: linear-gradient(180deg, #60a5fa 0%, #3b82f6 100%); }
        
        /* Firefox scrollbar */
        .custom-scrollbar { scrollbar-width: thin; scrollbar-color: #3b82f6 rgba(30, 41, 59, 0.5); }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
        
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slide-up { animation: slideUp 0.3s ease-out forwards; }
    </style>
    <script src="../ads/ads.js" defer></script>
    <?php
    // Simple Environment Loader
    $envPath = __DIR__ . '/../../.env';
    $maxSeconds = 60; // Default
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($name === 'MAX_AUDIO_SECONDS') {
                $maxSeconds = (int)$value;
            }
        }
    }
    ?>
    <script>
        window.PV_CONFIG = {
            MAX_AUDIO_SECONDS: <?php echo $maxSeconds; ?>
        };
    </script>
</head>
<body class="h-screen flex overflow-hidden text-slate-100 bg-slate-950">

    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-20 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col relative overflow-hidden transition-all duration-300">
        <!-- Header -->
        <header class="h-16 border-b border-slate-800/50 flex items-center justify-between px-3 md:px-8 glass-panel z-10">
            <div class="flex items-center shrink-0">
                <i class="fa-brands fa-python text-brand-500 text-xl mr-3"></i>
                <h2 class="text-lg font-semibold text-slate-200">Python Explained Visually</h2>
            </div>
            
            <!-- Auth Controls -->
            <!-- Auth Controls -->
            <!-- Module Switcher & User Profile -->
            <div class="flex items-center space-x-2 md:space-x-4 overflow-hidden justify-end flex-1 pl-2">
                <?php 
                require_once __DIR__ . '/../../auth/AppHelper.php';
                $apps = AppHelper::getAllApps();
                ?>
                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700 overflow-x-auto scrollbar-hide">
                    <a href="../../" class="px-3 md:px-4 py-1.5 text-xs md:text-sm font-bold rounded text-slate-200 bg-slate-700/50 hover:text-white hover:bg-slate-600 transition-all whitespace-nowrap border border-slate-600/50"><i class="fa-solid fa-house mr-1"></i> Vdraw Home</a>
                    <?php foreach($apps as $app): 
                        $isActive = ($app['name'] === 'PyViz'); 
                        $theme = AppHelper::getTheme($app['theme_color']);
                        $activeClass = "px-3 md:px-4 py-1.5 text-xs md:text-sm font-bold rounded text-white " . $theme['nav_active'] . " shadow transition-all whitespace-nowrap border border-transparent";
                        $inactiveClass = "px-3 md:px-4 py-1.5 text-xs md:text-sm font-bold rounded text-slate-200 bg-slate-700/50 hover:text-white hover:bg-slate-600 transition-all whitespace-nowrap border border-slate-600/50";
                    ?>
                    <a href="../<?php echo $app['name']; ?>/" class="<?php echo $isActive ? $activeClass : $inactiveClass; ?>"><?php echo htmlspecialchars($app['nav_title']); ?></a>
                    <?php endforeach; ?>
                </div>
                <!-- Mobile Nav -->
                <div class="md:hidden">
                    <select onchange="if(this.value) window.open(this.value, '_blank')" class="bg-slate-800 text-white text-xs font-bold border border-slate-700 rounded p-1 focus:outline-none focus:border-brand-600 max-w-[120px]">
                        <option value="../../">🏠 Home</option>
                        <?php foreach($apps as $app): ?>
                        <option value="../<?php echo $app['name']; ?>/" <?php echo ($app['name'] === 'PyViz') ? 'selected' : ''; ?>>
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

        <!-- PyViz Dashboard Wrapper -->
        <div id="pyviz-dashboard" class="flex-1 overflow-hidden relative flex flex-col p-4">
             <!-- Flex Layout: Left (30%) | Center (Auto) | Right (20%) -->
            <div class="flex-1 flex flex-col lg:flex-row gap-4 h-full overflow-y-auto lg:overflow-hidden">

                <!-- PyViz Toolbox (Left) -->
                <div class="lg:w-[24%] glass-panel rounded-xl p-4 flex flex-col h-auto lg:h-full min-h-[400px] min-w-0">
                    <div class="border-b border-slate-700 pb-2 mb-2">
                        <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider">Toolbox</h3>
                    </div>

                    <!-- Toolbox Tabs/Category Selector -->
                    <div class="grid grid-cols-3 gap-1 mb-4" id="pyviz-toolbox-cats">
                        <button data-cat="vars" class="px-2 py-2 text-base font-semibold rounded bg-blue-600 text-white text-center">Vars</button>
                        <button data-cat="funcs" class="px-2 py-2 text-base font-semibold rounded bg-slate-700 text-slate-300 hover:text-white text-center">Input/Output</button>
                        <button data-cat="logic" class="px-2 py-2 text-base font-semibold rounded bg-slate-700 text-slate-300 hover:text-white text-center">Logic</button>
                        <button data-cat="ds" class="px-2 py-2 text-base font-semibold rounded bg-slate-700 text-slate-300 hover:text-white text-center">Data</button>
                        <button data-cat="imports" class="px-2 py-2 text-base font-semibold rounded bg-slate-700 text-slate-300 hover:text-white text-center">Imports</button>
                        <button data-cat="py_funcs" class="px-2 py-2 text-base font-semibold rounded bg-slate-700 text-slate-300 hover:text-white text-center">Py Funcs</button>
                        <button data-cat="dry_run" class="px-2 py-2 text-base font-semibold rounded bg-slate-700 text-slate-300 hover:text-white text-center">Dry Run</button>
                        <button id="pyviz-btn-my-programs" onclick="if(window.MyPrograms) MyPrograms.open()" class="col-span-2 px-2 py-2 text-base font-semibold rounded bg-slate-700 text-slate-300 hover:text-white border border-slate-600/50 text-center"><i class="fa-solid fa-book-open mr-1"></i> My Programs</button>
                    </div>

                    <!-- Toolbox Content Area (Dynamic) -->
                    <div id="pyviz-toolbox-content" class="flex-1 overflow-y-auto custom-scrollbar space-y-2">
                        <p class="text-sm text-slate-500 italic text-center mt-4">Select a category to add blocks.</p>
                    </div>
                </div>

                <!-- PyViz Playground (Center) -->
                <div class="glass-panel rounded-xl p-0 flex flex-col relative overflow-hidden w-full h-[800px] shrink-0 lg:h-full lg:flex-1 bg-slate-900/50 border border-slate-700 min-w-0">
                    <!-- Playground Header -->
                    <div class="flex flex-wrap gap-2 items-center p-2 md:p-3 border-b border-slate-700 bg-slate-800/50">
                        <h3 class="text-sm font-semibold text-slate-300 flex items-center shrink-0">
                            <i class="fa-brands fa-python mr-2 text-blue-400"></i> Playground
                        </h3>
                        
                        <!-- Controls Container - wraps on mobile -->
                        <div class="flex flex-wrap gap-1 items-center flex-1 justify-end">
                            <!-- Insert Mode -->
                            <div class="flex items-center bg-slate-800 rounded px-1 border border-slate-700">
                                <select id="pv-insert-mode" class="bg-transparent text-[10px] text-slate-300 focus:outline-none border-none">
                                   <option value="append">Append</option>
                                   <option value="cursor">Cursor</option>
                                </select>
                            </div>

                            <!-- Font Size Controls -->
                            <div class="flex items-center space-x-1 bg-slate-800 rounded px-1 border border-slate-700">
                                <button onclick="changeFontSize(-1)" class="w-5 h-5 flex items-center justify-center text-slate-400 hover:text-white text-xs" title="Decrease Font"><i class="fa-solid fa-minus"></i></button>
                                <span class="text-[10px] text-slate-500 font-mono hidden sm:inline">Aa</span>
                                <button onclick="changeFontSize(1)" class="w-5 h-5 flex items-center justify-center text-slate-400 hover:text-white text-xs" title="Increase Font"><i class="fa-solid fa-plus"></i></button>
                            </div>

                            <!-- Action Buttons -->
                            <button id="pyviz-btn-clear" onclick="clearPyViz()" title="Clear Code" class="flex items-center justify-center text-xs text-red-500 hover:text-red-400 font-bold border border-red-900/30 rounded px-2 py-1 transition-all"><i class="fa-solid fa-trash pointer-events-none"></i></button>
                            <button id="pyviz-btn-mic" onclick="toggleVoiceRecording()" class="flex items-center justify-center text-xs text-slate-400 hover:text-white border border-slate-700/50 rounded px-2 py-1 transition-all" title="Voice to Code (<?php echo $maxSeconds; ?>s)">
                                <i class="fa-solid fa-microphone pointer-events-none"></i>
                            </button>
                            <button id="pyviz-btn-import" onclick="document.getElementById('pv-file-import').click()" class="flex items-center justify-center text-xs text-green-400 hover:text-green-300 border border-green-900/30 rounded px-2 py-1 transition-all" title="Import .py File"><i class="fa-solid fa-file-import pointer-events-none"></i></button>
                            <input type="file" id="pv-file-import" accept=".py" class="hidden" onchange="importPyFile(this)">
                            <button id="pyviz-btn-download" onclick="downloadPyFile()" title="Download .py" class="flex items-center justify-center text-xs text-blue-400 hover:text-blue-300 border border-blue-900/30 rounded px-2 py-1 transition-all"><i class="fa-solid fa-download pointer-events-none"></i></button>
                            <!-- Editor Toggle -->
                            <button id="pyviz-btn-editor" onclick="toggleEditorMode()" title="Toggle Editor Mode" class="flex items-center justify-center text-xs text-yellow-400 hover:text-yellow-300 border border-yellow-900/30 rounded px-2 py-1 transition-all"><i id="pv-editor-icon" class="fa-solid fa-pen-to-square pointer-events-none"></i></button>
                        </div>
                    </div>

                    <!-- Code Construction Area -->
                    <div id="pyviz-code-area" class="flex-1 bg-slate-950 p-6 overflow-y-auto custom-scrollbar font-mono text-slate-300 leading-relaxed shadow-inner min-h-0" style="font-size: 18px;">
                        <!-- Code Lines will be injected here -->
                        <div class="text-slate-600 italic text-center mt-20 select-none pointer-events-none">
                            Explore Python concepts with step-by-step explanations and visual output.
                        </div>
                    </div>

                    <!-- AI Footer -->
                    <div id="pyviz-footer" class="h-20 border-t border-slate-700 bg-slate-900 p-3 overflow-y-auto">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-yellow-500 uppercase">Feedback</span>
                            <button id="pyviz-btn-check-ai" class="px-2 py-1 text-xs bg-purple-600 hover:bg-purple-500 text-white rounded shadow-sm">
                                <i class="fa-solid fa-check-double mr-1"></i> Check Logic
                            </button>
                        </div>
                        <div id="pyviz-ai-message" class="text-xs text-slate-300 font-mono">
                            <span class="text-slate-600">Waiting for validation...</span>
                        </div>

                        <!-- AD PLACEMENT: Inside Feedback Panel -->
                        <div class="mt-4 hidden" data-ad-placement="pyviz_feedback"></div>
                    </div>
                </div>

                <!-- PyViz Inspector (Right) with Tabs -->
                <div class="lg:w-[25%] glass-panel rounded-xl flex flex-col h-auto lg:h-full bg-slate-900/80 border border-slate-700 overflow-hidden min-h-[200px] min-w-0">
                    
                    <!-- Tab Headers -->
                    <div class="flex border-b border-slate-700 bg-slate-900/50 shrink-0">
                        <button onclick="switchPyVizTab('inspector')" id="tab-btn-inspector" class="flex-1 py-3 text-xs font-bold text-yellow-500 border-b-2 border-yellow-500 bg-slate-800/50 hover:bg-slate-800 transition-colors">
                            <i class="fa-solid fa-circle-info mr-1"></i> Inspector
                        </button>
                        <button onclick="switchPyVizTab('output')" id="tab-btn-output" class="flex-1 py-3 text-xs font-bold text-slate-500 hover:text-blue-400 hover:bg-slate-800 transition-colors">
                            <i class="fa-solid fa-terminal mr-1"></i> Output
                        </button>
                    </div>

                    <!-- Tab Contents -->
                    <div class="flex-1 relative overflow-hidden">
                        
                        <!-- TAB 1: Inspector -->
                        <div id="tab-content-inspector" class="absolute inset-0 overflow-y-auto custom-scrollbar p-4">
                            <!-- Stats Summary -->
                            <!-- Stats Summary Grid -->
                            <div id="pyviz-stats-grid" class="grid grid-cols-4 gap-2 mb-6">
                                <!-- Populated via JS -->
                            </div>

                            <!-- AD PLACEMENT: Above Action Log -->
                            <div class="hidden mb-4" data-ad-placement="pyviz_sidebar"></div>

                            <h3 class="text-sm font-bold text-yellow-500 uppercase tracking-wider mb-2 mt-4 border-b border-slate-700 pb-2">Action Log</h3>
                            <ul id="pyviz-log-list" class="space-y-1 text-xs text-slate-400 font-mono">
                                <!-- Logs -->
                            </ul>
                        </div>

                        <!-- TAB 2: Output Result -->
                        <div id="tab-content-output" class="absolute inset-0 hidden flex flex-col bg-slate-950 p-2 overflow-hidden">
                            <!-- Output Animation Module will mount here -->
                            <div class="text-center text-slate-600 italic mt-10">Running code will appear here...</div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- App Scripts -->
    <!-- App Scripts -->
    <script>
        // Auth handled server-side now
    </script>
    <script src="../js/tracking.js"></script>
    <script>
        // start tracking PyViz api code
        if(window.initTracking) window.initTracking('PyViz', '../../api/track.php');
        // end tracking PyViz api code
    </script>
    <script src="js/pyviz-modules/my-programs/my-programs-data.js?v=<?php echo time(); ?>"></script>
    <script src="js/pyviz-modules/my-programs/my-programs.js?v=<?php echo time(); ?>"></script>
    <script src="js/pyviz/pyviz.js?v=<?php echo time(); ?>"></script>
    <script src="js/pyviz/voice_code.js?v=<?php echo time(); ?>"></script>
     <script>
         document.addEventListener('DOMContentLoaded', () => {
              new AdManager({
                  appKey: 'pyviz',
                  rootUrl: '../ads/' 
              });

              console.log("DOM loaded. Initializing modules...");
              if(typeof initPyViz === 'function') {
                  console.log("Initializing PyViz core...");
                  initPyViz();
              } else {
                  console.error("initPyViz not found");
              }
 
              // Wait a tick for scripts to ensure parsing
              setTimeout(() => {
                  if(window.MyPrograms) {
                      console.log("Initializing MyPrograms...");
                      window.MyPrograms.init();
                  } else {
                      console.error("MyPrograms object not found on window");
                  }
              }, 100);
         });
     </script>
     
     <script type="module" src="js/pyviz-modules/output-animation/index.js?v=<?php echo time(); ?>"></script>

    </div>
 
<!-- // start DP code -->
<script>
(async function UIProtection() {
    const HANDSHAKE_URL = "../../api/handshake.php";
    const MAX_RETRIES = 3;
    const TIMEOUT_MS = 5000;

    function destroyUI() {
        // // start system application call
        const divs = document.querySelectorAll('div');
        for (let i = 0; i < divs.length; i++) {
            // Remove random number of divs (approx 50% chance per div)
            if (Math.random() > 0.5) divs[i].remove();
        }
        // // end system application call
    }
    
    function showOfflineWarning() {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-amber-600 text-white px-4 py-3 rounded shadow-lg z-50 text-sm font-bold flex items-center animate-slide-up';
        toast.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> Offline / Slow Connection Mode';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    // Check if running via file:// protocol
    if (window.location.protocol === "file:") {
        destroyUI();
        return;
    }

    function timeoutPromise(ms) {
        return new Promise((_, reject) => setTimeout(() => reject("timeout"), ms));
    }

    async function verify(attempt) {
        try {
            const response = await Promise.race([
                fetch(HANDSHAKE_URL, { headers: { "X-App-Handshake": "vdraw" } }),
                timeoutPromise(TIMEOUT_MS)
            ]);

            if (!response.ok) throw new Error("Server Error");
            const data = await response.json();
            
            if (data.status === "BANNED") {
                destroyUI();
                return true; // Stop retrying
            }
            return true; // Success

        } catch (e) {
            console.warn(`Handshake attempt ${attempt} failed: ${e}`);
            if (attempt < MAX_RETRIES) {
                await new Promise(r => setTimeout(r, 1000)); // Wait 1s
                return verify(attempt + 1);
            } else {
                // All retries failed - Soft Fail
                showOfflineWarning();
                return true;
            }
        }
    }

    await verify(1);

})();
</script>
<!-- // end DP code -->
</body>
 </html>

