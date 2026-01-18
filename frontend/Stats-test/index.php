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
    <title>Statistics Visualizer – Mean, Median, Mode & IQR</title>
    <meta name="description" content="Understand statistics concepts using charts, graphs, and step-by-step visual explanations.">
    <meta name="keywords" content="statistics visualization, mean median mode, iqr, variance, standard deviation">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../../images/favicon.png" type="image/png">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Focus Magnifier Styles -->
    <link rel="stylesheet" href="js/focus-magnifier/focus-magnifier.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Plotly.js -->
    <script src="https://cdn.plot.ly/plotly-2.27.0.min.js"></script>

    <!-- MathJax -->
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']]
            },
            svg: {
                fontCache: 'global'
            }
        };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script src="../ads/ads.js" defer></script>

    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.4/gsap.min.js"></script>

    <!-- Custom CSS -->
    <style>
        :root {
            --bg-color: #0f172a;
            --text-color: #f8fafc;
            --panel-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.05);
            --input-bg: rgba(15, 23, 42, 0.6);
            --heading-color: #e2e8f0;
            --sub-text: #94a3b8;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s, color 0.3s;
        }

        .glass-panel {
            background: var(--panel-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            transition: background-color 0.3s, border-color 0.3s;
        }

        /* Light Mode Overrides (Stronger Contrast) */
        body.light-mode {
            --bg-color: #f1f5f9;
            --text-color: #334155;
            --panel-bg: rgba(255, 255, 255, 0.85);
            --border-color: rgba(203, 213, 225, 0.6);
            --input-bg: rgba(255, 255, 255, 1);
            --heading-color: #1e293b;
            --sub-text: #475569;
        }
        body.light-mode .text-slate-200 { color: #334155 !important; }
        body.light-mode .text-slate-300 { color: #475569 !important; }
        body.light-mode .text-slate-400 { color: #64748b !important; }
        body.light-mode .text-white { color: #0f172a !important; }

        .input-glass {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        /* Custom Scrollbar Styles for Sidebars */
        .sidebar-scrollbar::-webkit-scrollbar,
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar-scrollbar::-webkit-scrollbar-track,
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 3px;
            margin: 4px 0;
        }
        
        .sidebar-scrollbar::-webkit-scrollbar-thumb,
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #818cf8 0%, #6366f1 100%);
            border-radius: 3px;
            transition: background 0.3s ease;
        }
        
        .sidebar-scrollbar::-webkit-scrollbar-thumb:hover,
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #a5b4fc 0%, #818cf8 100%);
        }
        
        /* Firefox scrollbar */
        .sidebar-scrollbar,
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #6366f1 rgba(30, 41, 59, 0.5);
        }
        
        /* Light mode scrollbar adjustments */
        body.light-mode .sidebar-scrollbar::-webkit-scrollbar-track,
        body.light-mode .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(203, 213, 225, 0.5);
        }
        
        body.light-mode .sidebar-scrollbar,
        body.light-mode .custom-scrollbar {
            scrollbar-color: #6366f1 rgba(203, 213, 225, 0.5);
        }

        @media print {
             body { background-color: white !important; color: black !important; overflow: visible !important; height: auto !important; }
             aside#sidebar, header, #sidebar-overlay, #mobile-menu-btn, #btn-calculate, #btn-export-png, #btn-export-svg, .scrollbar-thin::-webkit-scrollbar { display: none !important; }
             main { margin: 0 !important; padding: 0 !important; height: auto !important; width: 100% !important; overflow: visible !important; display: block !important; }
             .overflow-y-auto, .overflow-hidden, .max-h-\[800px\] { overflow: visible !important; height: auto !important; max-height: none !important; }
             .glass-panel, .bg-slate-900, .bg-slate-800, .bg-slate-700, .bg-brand-900\/30, .bg-slate-900\/50, .bg-red-900\/20, #manual-data, input, textarea, select, .input-glass, #regression-content, #val-outliers { background: white !important; background-color: white !important; border: 1px solid #ddd !important; box-shadow: none !important; backdrop-filter: none !important; color: black !important; -webkit-print-color-adjust: exact; }
             * { color: black !important; text-shadow: none !important; }
        }
    </style>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
</head>

<body class="h-screen flex overflow-hidden text-slate-100">

    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-20 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed md:relative top-0 left-0 h-full w-80 bg-slate-900 border-r border-slate-800 flex flex-col z-30 shadow-2xl transform -translate-x-full md:translate-x-0 transition-transform duration-300">
        <!-- Logo -->
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-brand-400 to-purple-500">
                    <i class="fa-solid fa-chart-simple mr-2 text-brand-500"></i>Statistics
                </h1>
                <p class="text-slate-500 text-xs mt-1 uppercase tracking-wider">Statistics Explained Visually</p>
            </div>
            <button id="btn-close-sidebar" class="md:hidden text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <!-- Scrollable settings -->
        <div class="flex-1 overflow-y-auto p-4 pr-2 space-y-6 sidebar-scrollbar">
            <!-- Input Method -->
            <div class="space-y-3">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Input Source</h3>
                <div class="grid grid-cols-3 gap-2 bg-slate-800 p-1 rounded-lg">
                    <button id="btn-manual" class="px-2 py-2 text-xs font-medium rounded-md bg-brand-600 text-white shadow-lg transition-all">Manual</button>
                    <button id="btn-upload" class="px-2 py-2 text-xs font-medium rounded-md text-slate-400 hover:text-white hover:bg-slate-700 transition-all">Upload</button>
                    <button id="btn-sample" class="px-2 py-2 text-xs font-medium rounded-md text-slate-400 hover:text-white hover:bg-slate-700 transition-all">Sample</button>
                </div>

                <!-- Manual Input Area -->
                <div id="manual-input-section" class="space-y-2">
                    <label class="text-sm text-slate-400">Enter Values</label>
                    <textarea id="manual-data" rows="6" class="w-full input-glass rounded-lg p-3 text-sm font-mono text-slate-200 placeholder-slate-600 focus:ring-1 focus:ring-brand-500 focus:outline-none resize-none" placeholder="10, 20, 35.5&#10;45, 12..."></textarea>
                    <p class="text-xs text-slate-500">Separate by comma, space or newline.</p>
                </div>

                <!-- Upload Section -->
                <div id="upload-section" class="hidden space-y-2 border-2 border-dashed border-slate-700 rounded-lg p-6 text-center hover:border-brand-500 transition-colors cursor-pointer relative group">
                    <input type="file" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept=".csv, .xlsx">
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-500 group-hover:text-brand-400 transition-colors"></i>
                    <p class="text-sm text-slate-400 mt-2 font-medium group-hover:text-slate-200">Click to Upload</p>
                    <p class="text-xs text-slate-600">CSV or Excel (Max 3MB)</p>
                </div>

                <!-- Sample Section -->
                <div id="sample-section" class="hidden space-y-3">
                    <label class="text-sm text-slate-400">Choose Sample Data</label>
                    <select id="sample-select" class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded p-2 focus:border-brand-500 focus:outline-none">
                        <option value="" disabled selected>Select a file...</option>
                        <?php
                        $sampleDir = __DIR__ . '/sample';
                        if (is_dir($sampleDir)) {
                            $files = scandir($sampleDir);
                            foreach ($files as $f) {
                                if ($f !== '.' && $f !== '..' && preg_match('/\.(xlsx|csv)$/i', $f)) {
                                    echo '<option value="' . htmlspecialchars($f) . '">' . htmlspecialchars($f) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                    <button id="btn-load-sample" class="w-full py-2 bg-slate-700 hover:bg-brand-600 text-white text-xs font-bold rounded transition-colors">
                        <i class="fa-solid fa-file-import mr-2"></i> Load Sample
                    </button>
                </div>
            </div>

            <!-- Column Selection -->
            <div id="column-selectors" class="space-y-3 hidden">
                <div class="flex justify-between items-center">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Data Columns</h3>
                    <button id="btn-view-data" class="text-xs text-brand-400 hover:text-brand-300 underline"><i class="fa-solid fa-table mr-1"></i>View Data</button>
                </div>
                <!-- Stats Target -->
                <div class="space-y-1">
                    <label class="text-xs text-brand-300 font-bold block">Target for Stats</label>
                    <select id="col-analysis" class="w-full bg-slate-800 border-l-4 border-brand-500 text-slate-200 text-sm rounded p-2 focus:border-brand-500 focus:outline-none"></select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-1">
                        <label class="text-xs text-slate-500">Graph X-Axis</label>
                        <select id="col-x" class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded p-2 focus:border-brand-500 focus:outline-none"><option value="auto">Auto</option></select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-slate-500">Graph Y-Axis</label>
                        <select id="col-y" class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded p-2 focus:border-brand-500 focus:outline-none"><option value="auto">Auto</option></select>
                    </div>
                </div>
            </div>

            <!-- Parameters -->
            <div class="space-y-3">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Settings</h3>
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 glass-panel rounded-lg">
                        <span class="text-xs text-slate-400 block mb-1">Variance</span>
                        <select id="variance-type" class="w-full bg-transparent border-none text-brand-400 text-xs font-bold p-0 focus:ring-0">
                            <option value="sample">Sample</option>
                            <option value="population">Population</option>
                        </select>
                    </div>
                     <div class="p-2 glass-panel rounded-lg">
                        <span class="text-xs text-slate-400 block mb-1">Quartiles</span>
                        <select id="quartile-method" class="w-full bg-transparent border-none text-brand-400 text-xs font-bold p-0 focus:ring-0">
                            <option value="exclusive">Exclusive</option>
                            <option value="inclusive">Inclusive</option>
                            <option value="tukey">Tukey</option>
                        </select>
                    </div>
                </div>
                 <div class="p-3 glass-panel rounded-lg flex justify-between items-center">
                    <span class="text-sm text-slate-300">Chart Type</span>
                    <select id="chart-type" class="bg-slate-800 border-none text-brand-400 text-sm font-medium focus:ring-0 rounded">
                        <option value="bar">Bar Chart</option>
                        <option value="line">Line Chart</option>
                        <option value="histogram">Histogram</option>
                        <option value="boxplot">Box Plot</option>
                        <option value="scatter">Scatter Plot</option>
                    </select>
                </div>
                 <!-- Regression Block -->
                <div class="p-3 glass-panel rounded-lg space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-300">Regression</span>
                         <select id="regression-select" class="bg-slate-800 border-none text-brand-400 text-sm font-medium focus:ring-0 rounded">
                            <option value="">None</option>
                            <option value="linear">Linear</option>
                            <option value="logistic">Logistic</option>
                        </select>
                    </div>
                     <div id="regression-cols" class="hidden grid grid-cols-2 gap-2 border-t border-slate-700/50 pt-2">
                        <div class="space-y-1"><label class="text-[10px] text-slate-500 uppercase">Train X</label><select id="reg-x-col" class="w-full bg-slate-900 border border-slate-700 text-white text-xs rounded p-1"></select></div>
                         <div class="space-y-1"><label class="text-[10px] text-slate-500 uppercase">Train Y</label><select id="reg-y-col" class="w-full bg-slate-900 border border-slate-700 text-white text-xs rounded p-1"></select></div>
                    </div>
                </div>
            </div>
            <button id="btn-calculate" class="w-full py-3 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-brand-900/50 transform hover:scale-[1.02] transition-all">
                <i class="fa-solid fa-calculator mr-2"></i> Calculate
            </button>
            
            <!-- AD PLACEMENT: Stats Sidebar Bottom -->
            <div class="mt-4 hidden" data-ad-placement="stats_sidebar_bottom"></div>
        </div>
         <div class="p-4 border-t border-slate-800 text-center"><p class="text-xs text-slate-600">by F. Hassan &copy; 2025 VDraw</p></div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col relative overflow-hidden transition-all duration-300">
        <!-- Decorative Background -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
            <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-brand-600/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-[-10%] left-[-10%] w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
        </div>

        <!-- Header -->
        <header class="h-16 border-b border-slate-800/50 flex items-center justify-between px-3 md:px-8 glass-panel z-10 transition-colors duration-300">
            <div class="flex items-center shrink-0">
                <button id="mobile-menu-btn" class="mr-3 md:hidden text-slate-300 hover:text-white"><i class="fa-solid fa-bars text-xl"></i></button>
                <h2 id="page-title" class="text-lg font-semibold text-slate-200 truncate max-w-[100px] md:max-w-none">Stats Dashboard</h2>
            </div>
             <div class="flex items-center space-x-2 md:space-x-4 overflow-hidden justify-end flex-1 pl-2">
                <!-- Module Switcher -->
                <?php 
                require_once __DIR__ . '/../../auth/AppHelper.php';
                $apps = AppHelper::getAllApps();
                ?>
                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700 overflow-x-auto scrollbar-hide">
                    <a href="../../" class="px-3 md:px-4 py-1.5 text-xs md:text-sm font-bold rounded text-slate-200 bg-slate-700/50 hover:text-white hover:bg-slate-600 transition-all whitespace-nowrap border border-slate-600/50"><i class="fa-solid fa-house mr-1"></i> Vdraw Home</a>
                    <?php foreach($apps as $app): 
                        $isActive = ($app['name'] === 'Stats'); 
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
                        <option value="../<?php echo $app['name']; ?>/" <?php echo ($app['name'] === 'Stats') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($app['nav_title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <!-- User Profile -->
                 <!-- User Profile -->
                 <?php $dashboardLink = ($currentUser['role'] === 'admin') ? '../../admin/dashboard/' : '../../user/dashboard/'; ?>
                 <a href="<?php echo $dashboardLink; ?>" class="flex items-center gap-2 text-slate-300 hover:text-white transition group mr-4">
                    <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center font-bold text-white shadow-lg group-hover:bg-brand-500">
                        <?php echo substr($currentUser['full_name'] ?? 'U', 0, 1); ?>
                    </div>
                    <span class="hidden md:inline font-medium text-xs"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'User'); ?></span>
                 </a>


                 <!-- PDF Btn -->
                <button id="btn-export-pdf" class="h-8 px-3 rounded-full bg-slate-700 hover:bg-slate-600 flex items-center justify-center text-slate-300 transition-colors">
                    <i class="fa-solid fa-file-pdf md:mr-2"></i> <span class="hidden md:inline">PDF Report</span>
                </button>
            </div>
        </header>

        <!-- Scrollable Dashboard -->
        <div class="flex-1 overflow-y-auto p-8 relative">
            <!-- AD PLACEMENT: Stats Main Top -->
            <div class="mb-6 hidden" data-ad-placement="stats_main_top"></div>
            
            <!-- VDraw Dashboard Wrapper -->
            <div id="vdraw-dashboard" class="transition-opacity duration-300">
                <!-- Stats overview grid -->
                <div id="stats-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 hidden">
                    <div class="glass-panel p-5 rounded-xl border-l-4 border-brand-500"><p class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Mean</p><h3 id="val-mean" class="text-3xl font-bold text-white">--</h3></div>
                    <div class="glass-panel p-5 rounded-xl border-l-4 border-purple-500"><p class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Median</p><h3 id="val-median" class="text-3xl font-bold text-white">--</h3></div>
                    <div class="glass-panel p-5 rounded-xl border-l-4 border-pink-500"><p class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Mode</p><h3 id="val-mode" class="text-3xl font-bold text-white">--</h3></div>
                    <div class="glass-panel p-5 rounded-xl border-l-4 border-cyan-500"><p class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Std Dev</p><h3 id="val-std" class="text-3xl font-bold text-white">--</h3></div>
                </div>

                <!-- Main Charts Area -->
                <div id="empty-state" class="flex flex-col items-center justify-center h-[60vh] text-center">
                    <div class="bg-slate-800/50 p-6 rounded-full mb-4"><i class="fa-solid fa-chart-column text-4xl text-slate-600"></i></div>
                    <h3 class="text-xl font-semibold text-slate-300 mb-2">Statistics Explained Visually</h3>
                    <p class="text-slate-500 max-w-md">Enter data and visualize statistical calculations instantly.</p>
                    <div class="mt-8 hidden w-full mx-auto" data-ad-placement="vdraw_manual_upload"></div>
                </div>

                <div id="results-area" class="space-y-6 hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Charts & Key Stats -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Chart -->
                            <div class="glass-panel p-6 rounded-xl flex flex-col">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-sm font-semibold text-slate-300">Visualization</h3>
                                    <div class="flex space-x-2">
                                        <button id="btn-export-png" class="text-xs text-brand-400 hover:text-brand-300 border border-brand-500/30 px-2 py-1 rounded transition-colors"><i class="fa-solid fa-download mr-1"></i> PNG</button>
                                        <button id="btn-export-svg" class="text-xs text-brand-400 hover:text-brand-300 border border-brand-500/30 px-2 py-1 rounded transition-colors"><i class="fa-solid fa-file-code mr-1"></i> SVG</button>
                                    </div>
                                </div>
                                <div class="relative w-full h-80 flex-1 min-h-[320px]">
                                    <canvas id="mainChart" class="w-full h-full"></canvas>
                                    <div id="plotlyChart" class="w-full h-full hidden"></div>
                                </div>
                            </div>
                            <!-- Ad between panels -->
                            <div class="hidden w-full mt-6 mb-6" data-ad-placement="vdraw_stats_panel"></div>
                            
                            <!-- Statistics Summary -->
                            <div class="glass-panel p-6 rounded-xl">
                                <h3 class="text-sm font-semibold text-slate-300 mb-2">Detailed Statistics</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="flex justify-between border-b border-slate-700/50 pb-2"><span class="text-slate-400 text-sm">Range</span><span id="val-range" class="text-slate-200 font-mono">--</span></div>
                                        <div class="flex justify-between border-b border-slate-700/50 pb-2 mt-2"><span class="text-slate-400 text-sm">Variance</span><span id="val-variance" class="text-slate-200 font-mono">--</span></div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between border-b border-slate-700/50 pb-2"><span class="text-slate-400 text-sm">Q1 (25%)</span><span id="val-q1" class="text-slate-200 font-mono">--</span></div>
                                        <div class="flex justify-between border-b border-slate-700/50 pb-2 mt-2"><span class="text-slate-400 text-sm">Q3 (75%)</span><span id="val-q3" class="text-slate-200 font-mono">--</span></div>
                                    </div>
                                </div>
                                <div class="flex justify-between border-b border-slate-700/50 pb-2 mt-2"><span class="text-slate-400 text-sm">IQR</span><span id="val-iqr" class="text-slate-200 font-mono">--</span></div>
                                <div class="mt-4 pt-2">
                                    <h4 class="text-xs font-bold text-red-400 uppercase tracking-wider mb-2">Outliers</h4>
                                    <p id="val-outliers" class="text-sm text-slate-300 bg-slate-900/50 p-3 rounded border border-red-500/20">None detected</p>
                                </div>
                                 <!-- Regression Output -->
                                <div id="regression-area" class="mt-4 pt-2 hidden">
                                    <h4 class="text-xs font-bold text-brand-400 uppercase tracking-wider mb-2">Regression Model</h4>
                                    <div id="regression-content" class="text-sm text-slate-300 bg-slate-900/50 p-3 rounded border border-brand-500/20 font-mono overflow-auto max-h-40 scrollbar-thin mb-3">--</div>
                                    <div id="prediction-ui" class="hidden border-t border-slate-700/50 pt-2">
                                        <h5 class="text-xs font-semibold text-slate-400 mb-2">Predict Y from X</h5>
                                        <div class="flex items-center space-x-2">
                                            <input type="number" id="predict-x" placeholder="Enter X value" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            <button id="btn-predict" class="bg-brand-600 hover:bg-brand-500 text-white px-3 py-2 rounded text-sm transition-colors">Predict</button>
                                        </div>
                                        <div id="predict-result" class="mt-2 text-sm font-bold text-brand-300 hidden">Predicted Y: <span id="val-predicted" class="text-white">--</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Explanations Panel -->
                        <div class="glass-panel p-6 rounded-xl overflow-hidden flex flex-col max-h-[800px]">
                            <h3 class="text-sm font-semibold text-slate-300 mb-4 border-b border-slate-700 pb-2"><i class="fa-solid fa-graduation-cap text-brand-400 mr-2"></i>Step-by-Step</h3>
                            <div id="explanation-container" class="overflow-y-auto pr-3 space-y-6 custom-scrollbar"><p class="text-slate-500 text-sm italic">Run a calculation to see steps here.</p></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- REMOVED OTHER DASHBOARDS -->
        </div>
    </main>

    <!-- Data View Modal -->
    <div id="data-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" id="data-modal-backdrop"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-4xl h-[80vh] flex flex-col shadow-2xl transform transition-all">
                <div class="flex justify-between items-center p-4 border-b border-slate-700 bg-slate-800 rounded-t-xl">
                    <h3 class="text-lg font-semibold text-white">Imported Data</h3>
                    <button id="btn-close-modal" class="text-slate-400 hover:text-white transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>
                <div class="p-3 bg-slate-800 border-b border-slate-700 space-y-3">
                     <!-- Toolbar -->
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2 border-r border-slate-700 pr-4"><span class="text-xs text-slate-400 uppercase font-bold">Cleaning</span><button id="btn-drop-missing" class="px-2 py-1 text-xs bg-red-900/30 text-red-300 border border-red-500/30 rounded hover:bg-red-900/50 transition-colors"><i class="fa-solid fa-trash-can mr-1"></i>Drop Missing</button></div>
                        <div class="flex items-center gap-2"><span class="text-xs text-slate-500">Impute:</span><select id="sel-impute-col" class="bg-slate-900 border border-slate-700 text-slate-300 text-xs rounded p-1"><option value="">Select Column...</option></select><button id="btn-fill-mean" class="px-2 py-1 text-xs bg-slate-700 text-slate-300 rounded hover:bg-brand-600 hover:text-white transition-colors">Mean</button><button id="btn-fill-median" class="px-2 py-1 text-xs bg-slate-700 text-slate-300 rounded hover:bg-brand-600 hover:text-white transition-colors">Median</button></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 border-t border-slate-700/50 pt-2">
                        <span class="text-xs text-slate-400 uppercase font-bold mr-2">Filter</span><select id="sel-filter-col" class="bg-slate-900 border border-slate-700 text-slate-300 text-xs rounded p-1"><option value="">Column...</option></select><select id="sel-filter-op" class="bg-slate-900 border border-slate-700 text-slate-300 text-xs rounded p-1"><option value=">">></option><option value="<">&lt;</option><option value="=">=</option><option value="!=">!=</option><option value="contains">Contains</option></select><input type="text" id="inp-filter-val" placeholder="Value" class="bg-slate-900 border border-slate-700 text-slate-300 text-xs rounded p-1 w-24"><button id="btn-apply-filter" class="px-2 py-1 text-xs bg-brand-600 text-white rounded hover:bg-brand-500 transition-colors ml-2"><i class="fa-solid fa-filter mr-1"></i>Apply</button><button id="btn-reset-data" class="px-2 py-1 text-xs bg-slate-700 text-slate-400 rounded hover:bg-slate-600 hover:text-white transition-colors ml-auto"><i class="fa-solid fa-rotate-left mr-1"></i>Reset Data</button>
                    </div>
                </div>
                <div class="flex-1 overflow-auto p-4 custom-scrollbar">
                    <table class="w-full text-sm text-left text-slate-300 border-collapse">
                        <thead class="text-xs text-slate-400 uppercase bg-slate-800 sticky top-0"><tr id="data-table-head"></tr></thead>
                        <tbody id="data-table-body" class="divide-y divide-slate-800"></tbody>
                    </table>
                </div>
                <div class="p-3 border-t border-slate-700 bg-slate-800 rounded-b-xl flex justify-between items-center">
                    <span class="text-xs text-slate-500 ml-2">Modifications apply immediately.</span>
                    <button id="btn-use-filtered" class="px-4 py-2 text-xs font-bold bg-green-600 text-white rounded hover:bg-green-500 transition-colors shadow-lg"><i class="fa-solid fa-check mr-2"></i>Use & Analyze Data</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-20 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- App Scripts -->
    <script src="js/backend-mock.js"></script>
    <script src="js/charts.js"></script>
    <script src="../js/tracking.js"></script>
    <!-- Focus Magnifier Feature -->
    <script src="js/focus-magnifier/focus-magnifier.js"></script>
    <script>
        // start tracking Stats api code
        if(window.initTracking) {
            window.initTracking('Stats', '../../api/track.php');
            // Specific Actions
            const btnCalc = document.getElementById('btn-calculate');
            if(btnCalc) btnCalc.addEventListener('click', () => track('action', 'Calculate Stats'));
            
            const btnUp = document.getElementById('btn-upload');
            if(btnUp) btnUp.addEventListener('click', () => track('action', 'Upload Data Click'));
        }
        // end tracking Stats api code
    </script>
    <script src="js/app.js"></script>
    <script src="js/nav.js?v=2"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
              new AdManager({
                  appKey: 'vdraw', // VDraw (Landing & Stats)
                  rootUrl: '../ads/' 
              });
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