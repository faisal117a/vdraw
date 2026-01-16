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
    <title>Graph Builder – Graph Visualization & Animation</title>
    <meta name="description" content="Build and animate graphs to understand nodes, edges, traversal, and graph algorithms visually.">
    <meta name="keywords" content="graph visualization, graph animation, data structures graph, bfs dfs visualization">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../../images/favicon.png" type="image/png">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
    <script src="../js/tracking.js"></script>
    <script>
        // start tracking Graph api code
        if(window.initTracking) {
            window.initTracking('Graph', '../../api/track.php');

            const btnFull = document.getElementById('tgdraw-fullscreen-btn');
            if(btnFull) btnFull.addEventListener('click', () => track('action', 'Toggle Fullscreen'));
        }
        // end tracking Graph api code
    </script>
    <script src="js/tgdraw/tgdraw.js?v=4" defer></script>
    <script src="../ads/ads.js?v=4" defer></script>

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
            /* Slate 100 */
            --text-color: #334155;
            /* Slate 700 */
            --panel-bg: rgba(255, 255, 255, 0.85);
            /* Whiter panels */
            --border-color: rgba(203, 213, 225, 0.6);
            /* Slate 300 */
            --input-bg: rgba(255, 255, 255, 1);
            --heading-color: #1e293b;
            /* Slate 800 */
            --sub-text: #475569;
            /* Slate 600 */
        }

        body.light-mode .text-slate-200 {
            color: #334155 !important;
        }

        body.light-mode .text-slate-300 {
            color: #475569 !important;
        }

        body.light-mode .text-slate-400 {
            color: #64748b !important;
        }

        body.light-mode .text-white {
            color: #0f172a !important;
        }

        /* Invert generic white text */

        /* Fix Chart Text in Light Mode via JS mostly, but some CSS help */

        .input-glass {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        /* Custom Scrollbar Styles for Graph App */
        .custom-scrollbar::-webkit-scrollbar,
        .sidebar-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track,
        .sidebar-scrollbar::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 3px;
            margin: 4px 0;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb,
        .sidebar-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 3px;
            transition: background 0.3s ease;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover,
        .sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #fcd34d 0%, #fbbf24 100%);
        }
        
        /* Firefox scrollbar */
        .custom-scrollbar,
        .sidebar-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #f59e0b rgba(30, 41, 59, 0.5);
        }
        
        /* Light mode scrollbar adjustments */
        body.light-mode .custom-scrollbar::-webkit-scrollbar-track,
        body.light-mode .sidebar-scrollbar::-webkit-scrollbar-track {
            background: rgba(203, 213, 225, 0.5);
        }
        
        body.light-mode .custom-scrollbar,
        body.light-mode .sidebar-scrollbar {
            scrollbar-color: #f59e0b rgba(203, 213, 225, 0.5);
        }

        @media print {

        /* ... rest of style ... */

        /* Specific overrides for specific IDs to ensure light mode works */
        body.light-mode #page-title {
            color: #1e293b;
        }

        body.light-mode #manual-data {
            color: #1e293b;
            background: #ffffff;
        }

        /* PRINT STYLES FOR PDF EXPORT */
        @media print {
            body {
                background-color: white !important;
                color: black !important;
                overflow: visible !important;
                height: auto !important;
            }

            /* Hide Sidebar, Header, Overlay, Scrollbars, Buttons */
            aside#sidebar,
            header,
            #sidebar-overlay,
            #mobile-menu-btn,
            #btn-calculate,
            #btn-export-png,
            #btn-export-svg,
            .scrollbar-thin::-webkit-scrollbar {
                display: none !important;
            }

            /* Ensure main content takes full width and height */
            main {
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
                width: 100% !important;
                overflow: visible !important;
                display: block !important;
            }

            /* Expand Scrollable Areas */
            .overflow-y-auto,
            .overflow-hidden,
            .max-h-\[800px\] {
                overflow: visible !important;
                height: auto !important;
                max-height: none !important;
            }

            /* Styling adjustments for print legibility */
            .glass-panel,
            .bg-slate-900,
            .bg-slate-800,
            .bg-slate-700,
            .bg-brand-900\/30,
            .bg-slate-900\/50,
            .bg-red-900\/20,
            #manual-data,
            input,
            textarea,
            select,
            .input-glass,
            #regression-content,
            #val-outliers {
                background: white !important;
                background-color: white !important;
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                color: black !important;
                -webkit-print-color-adjust: exact;
            }

            /* Force all text to black */
            * {
                color: black !important;
                text-shadow: none !important;
            }

            /* Specific overrides for chart/grid containers to remove dark bg */
            .bg-slate-900,
            .bg-slate-800,
            .glass-panel {
                background-image: none !important;
            }

            /* Ensure borders are visible but thin */
            .border-slate-700,
            .border-slate-800 {
                border-color: #ddd !important;
            }

            /* MathJax and Code blocks */
            .overflow-x-auto {
                background: #f8f9fa !important;
                /* Light gray for code blocks is okay */
                border: 1px solid #eee !important;
            }

            /* Hide decorative gradients */
            .bg-gradient-to-r,
            .bg-clip-text {
                background: none !important;
                -webkit-text-fill-color: black !important;
                color: black !important;
            }

            /* Ensure links/buttons don't look weird if they appear */
            a {
                text-decoration: none !important;
                color: black !important;
            }

            /* Layout Fixes */
            #results-area {
                display: block !important;
            }

            #stats-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 1rem !important;
                margin-bottom: 1rem;
            }

            /* Chart resizing */
            canvas#mainChart,
            div#plotlyChart {
                max-width: 100% !important;
                height: auto !important;
                max-height: 400px !important;
            }

            /* Page Breaks */
            .glass-panel {
                page-break-inside: avoid;
            }

            h1,
            h2,
            h3,
            h4 {
                page-break-after: avoid;
            }
        }
    </style>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
</head>

<body class="h-screen flex overflow-hidden text-slate-100">

    <!-- Mobile Overlay -->
    <div id="sidebar-overlay"
        class="fixed inset-0 bg-black/50 z-20 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="fixed md:relative top-0 left-0 h-[450px] md:h-full w-80 bg-slate-900 border-r border-slate-800 flex flex-col z-30 shadow-2xl transform -translate-x-full md:translate-x-0 transition-transform duration-300">
        <!-- Logo -->
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <div>
                <h1
                    class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-brand-400 to-purple-500">
                    <i class="fa-solid fa-share-nodes mr-2 text-brand-500"></i>Graph Builder
                </h1>
                <p class="text-slate-500 text-xs mt-1 uppercase tracking-wider">Graph Visualization</p>
            </div>
            <!-- Close Button (Mobile) -->
            <button id="btn-close-sidebar" class="md:hidden text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <!-- Scrollable settings -->
        <div class="flex-1 overflow-y-auto p-4 space-y-6 scrollbar-thin scrollbar-thumb-slate-700">

            <!-- Input Method -->
            <div class="space-y-3">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Input Source</h3>

                <div class="grid grid-cols-2 gap-2 bg-slate-800 p-1 rounded-lg">
                    <button id="btn-manual"
                        class="px-3 py-2 text-sm font-medium rounded-md bg-brand-600 text-white shadow-lg transition-all">
                        Manual
                    </button>
                    <button id="btn-upload"
                        class="px-3 py-2 text-sm font-medium rounded-md text-slate-400 hover:text-white hover:bg-slate-700 transition-all">
                        Upload
                    </button>
                </div>

                <!-- Manual Input Area -->
                <div id="manual-input-section" class="space-y-2">
                    <label class="text-sm text-slate-400">Enter Values</label>
                    <textarea id="manual-data" rows="6"
                        class="w-full input-glass rounded-lg p-3 text-sm font-mono text-slate-200 placeholder-slate-600 focus:ring-1 focus:ring-brand-500 focus:outline-none resize-none"
                        placeholder="10, 20, 35.5&#10;45, 12..."></textarea>
                    <p class="text-xs text-slate-500">Separate by comma, space or newline.</p>
                </div>

                <!-- Upload Section (Hidden by default) -->
                <div id="upload-section"
                    class="hidden space-y-2 border-2 border-dashed border-slate-700 rounded-lg p-6 text-center hover:border-brand-500 transition-colors cursor-pointer relative group">
                    <input type="file" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        accept=".csv, .xlsx">
                    <i
                        class="fa-solid fa-cloud-arrow-up text-2xl text-slate-500 group-hover:text-brand-400 transition-colors"></i>
                    <p class="text-sm text-slate-400 mt-2 font-medium group-hover:text-slate-200">Click to Upload</p>
                    <p class="text-xs text-slate-600">CSV or Excel (Max 3MB)</p>
                </div>
            </div>

            <!-- Column Selection (Dynamic) -->
            <div id="column-selectors" class="space-y-3 hidden">
                <div class="flex justify-between items-center">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Data Columns</h3>
                    <button id="btn-view-data" class="text-xs text-brand-400 hover:text-brand-300 underline">
                        <i class="fa-solid fa-table mr-1"></i>View Data
                    </button>
                </div>

                <!-- Stats Target -->
                <div class="space-y-1">
                    <label class="text-xs text-brand-300 font-bold block">Target for Stats (Means/etc)</label>
                    <select id="col-analysis"
                        class="w-full bg-slate-800 border-l-4 border-brand-500 text-slate-200 text-sm rounded p-2 focus:border-brand-500 focus:outline-none">
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-1">
                        <label class="text-xs text-slate-500">Graph X-Axis</label>
                        <select id="col-x"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded p-2 focus:border-brand-500 focus:outline-none">
                            <option value="auto">Auto</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-slate-500">Graph Y-Axis</label>
                        <select id="col-y"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded p-2 focus:border-brand-500 focus:outline-none">
                            <option value="auto">Auto</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Parameters -->
            <div class="space-y-3">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Settings</h3>

                <!-- Compact Parameters Grid -->
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 glass-panel rounded-lg">
                        <span class="text-xs text-slate-400 block mb-1">Variance</span>
                        <select id="variance-type"
                            class="w-full bg-transparent border-none text-brand-400 text-xs font-bold p-0 focus:ring-0">
                            <option value="sample">Sample</option>
                            <option value="population">Population</option>
                        </select>
                    </div>
                    <div class="p-2 glass-panel rounded-lg">
                        <span class="text-xs text-slate-400 block mb-1">Quartiles</span>
                        <select id="quartile-method"
                            class="w-full bg-transparent border-none text-brand-400 text-xs font-bold p-0 focus:ring-0">
                            <option value="exclusive">Exclusive</option>
                            <option value="inclusive">Inclusive</option>
                            <option value="tukey">Tukey</option>
                        </select>
                    </div>
                </div>

                <div class="p-3 glass-panel rounded-lg flex justify-between items-center">
                    <span class="text-sm text-slate-300">Chart Type</span>
                    <select id="chart-type"
                        class="bg-slate-800 border-none text-brand-400 text-sm font-medium focus:ring-0 rounded">
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
                        <select id="regression-select"
                            class="bg-slate-800 border-none text-brand-400 text-sm font-medium focus:ring-0 rounded">
                            <option value="">None</option>
                            <option value="linear">Linear</option>
                            <option value="logistic">Logistic</option>
                        </select>
                    </div>

                    <div id="regression-cols" class="hidden grid grid-cols-2 gap-2 border-t border-slate-700/50 pt-2">
                        <div class="space-y-1">
                            <label class="text-[10px] text-slate-500 uppercase">Train X</label>
                            <select id="reg-x-col"
                                class="w-full bg-slate-900 border border-slate-700 text-white text-xs rounded p-1"></select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] text-slate-500 uppercase">Train Y</label>
                            <select id="reg-y-col"
                                class="w-full bg-slate-900 border border-slate-700 text-white text-xs rounded p-1"></select>
                        </div>
                    </div>
                </div>
            </div>

            <button id="btn-calculate"
                class="w-full py-3 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-brand-900/50 transform hover:scale-[1.02] transition-all">
                <i class="fa-solid fa-calculator mr-2"></i> Calculate
            </button>

        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-slate-800 text-center">
            <p class="text-xs text-slate-600">by F. Hassan &copy; 2025 VDraw</p>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col relative overflow-hidden transition-all duration-300">
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
            <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-brand-600/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-[-10%] left-[-10%] w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
        </div>

        <!-- Header -->
        <header
            class="h-16 border-b border-slate-800/50 flex items-center justify-between px-3 md:px-8 glass-panel z-10 transition-colors duration-300">

            <div class="flex items-center shrink-0">
                <button id="mobile-menu-btn" class="mr-3 md:hidden text-slate-300 hover:text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 id="page-title" class="text-lg font-semibold text-slate-200 truncate max-w-[100px] md:max-w-none">
                    Dashboard
                </h2>
            </div>

            <div class="flex items-center space-x-2 md:space-x-4 overflow-hidden justify-end flex-1 pl-2">
                <!-- Module Switcher -->
                <!-- Module Switcher -->
                <?php 
                require_once __DIR__ . '/../../auth/AppHelper.php';
                $apps = AppHelper::getAllApps();
                ?>
                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700 overflow-x-auto scrollbar-hide">
                    <a href="../../" class="px-3 md:px-4 py-1.5 text-xs md:text-sm font-bold rounded text-slate-200 bg-slate-700/50 hover:text-white hover:bg-slate-600 transition-all whitespace-nowrap border border-slate-600/50"><i class="fa-solid fa-house mr-1"></i> Vdraw Home</a>
                    <?php foreach($apps as $app): 
                        $isActive = ($app['name'] === 'Graph'); 
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
                        <option value="../<?php echo $app['name']; ?>/" <?php echo ($app['name'] === 'Graph') ? 'selected' : ''; ?>>
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



                <!-- PDF Btn (Icon only on mobile) -->
                <button id="btn-export-pdf"
                    class="h-8 px-3 rounded-full bg-slate-700 hover:bg-slate-600 flex items-center justify-center text-slate-300 transition-colors">
                    <i class="fa-solid fa-file-pdf md:mr-2"></i> <span class="hidden md:inline">PDF Report</span>
                </button>

                <button class="text-slate-400 hover:text-white transition-colors ml-2 hidden sm:block"><i
                        class="fa-regular fa-circle-question"></i></button>
            </div>
        </header>

        <!-- Scrollable Dashboard -->
        <div class="flex-1 overflow-y-auto p-8 relative">

            <!-- VDraw Dashboard Wrapper -->
            <div id="vdraw-dashboard" class="transition-opacity duration-300">
                <!-- Stats overview grid -->
                <div id="stats-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 hidden">
                    <!-- Mean -->
                    <div class="glass-panel p-5 rounded-xl border-l-4 border-brand-500">
                        <p class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Mean</p>
                        <h3 id="val-mean" class="text-3xl font-bold text-white">--</h3>
                    </div>
                    <!-- Median -->
                    <div class="glass-panel p-5 rounded-xl border-l-4 border-purple-500">
                        <p class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Median</p>
                        <h3 id="val-median" class="text-3xl font-bold text-white">--</h3>
                    </div>
                    <!-- Mode -->
                    <div class="glass-panel p-5 rounded-xl border-l-4 border-pink-500">
                        <p class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Mode</p>
                        <h3 id="val-mode" class="text-3xl font-bold text-white">--</h3>
                    </div>
                    <!-- Std Dev -->
                    <div class="glass-panel p-5 rounded-xl border-l-4 border-cyan-500">
                        <p class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Std Dev</p>
                        <h3 id="val-std" class="text-3xl font-bold text-white">--</h3>
                    </div>
                </div>

                <!-- Main Charts Area -->
                <div id="empty-state" class="flex flex-col items-center justify-center h-[60vh] text-center">
                    <div class="bg-slate-800/50 p-6 rounded-full mb-4">
                        <i class="fa-solid fa-chart-column text-4xl text-slate-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-300 mb-2">Graph Builder & Animation</h3>
                    <p class="text-slate-500 max-w-md">Create graphs and animate traversal to understand graph structures clearly.</p>
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
                                        <button id="btn-export-png"
                                            class="text-xs text-brand-400 hover:text-brand-300 border border-brand-500/30 px-2 py-1 rounded transition-colors">
                                            <i class="fa-solid fa-download mr-1"></i> PNG
                                        </button>
                                        <button id="btn-export-svg"
                                            class="text-xs text-brand-400 hover:text-brand-300 border border-brand-500/30 px-2 py-1 rounded transition-colors">
                                            <i class="fa-solid fa-file-code mr-1"></i> SVG
                                        </button>
                                    </div>
                                </div>
                                <div class="relative w-full h-80 flex-1 min-h-[320px]">
                                    <!-- Chart.js Canvas -->
                                    <canvas id="mainChart" class="w-full h-full"></canvas>
                                    <!-- Plotly Container -->
                                    <div id="plotlyChart" class="w-full h-full hidden"></div>
                                </div>
                            </div>

                            <!-- Statistics Summary -->
                            <div class="glass-panel p-6 rounded-xl">
                                <h3 class="text-sm font-semibold text-slate-300 mb-2">Detailed Statistics</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="flex justify-between border-b border-slate-700/50 pb-2">
                                            <span class="text-slate-400 text-sm">Range</span>
                                            <span id="val-range" class="text-slate-200 font-mono">--</span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-700/50 pb-2 mt-2">
                                            <span class="text-slate-400 text-sm">Variance</span>
                                            <span id="val-variance" class="text-slate-200 font-mono">--</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between border-b border-slate-700/50 pb-2">
                                            <span class="text-slate-400 text-sm">Q1 (25%)</span>
                                            <span id="val-q1" class="text-slate-200 font-mono">--</span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-700/50 pb-2 mt-2">
                                            <span class="text-slate-400 text-sm">Q3 (75%)</span>
                                            <span id="val-q3" class="text-slate-200 font-mono">--</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between border-b border-slate-700/50 pb-2 mt-2">
                                    <span class="text-slate-400 text-sm">IQR</span>
                                    <span id="val-iqr" class="text-slate-200 font-mono">--</span>
                                </div>

                                <div class="mt-4 pt-2">
                                    <h4 class="text-xs font-bold text-red-400 uppercase tracking-wider mb-2">Outliers
                                    </h4>
                                    <p id="val-outliers"
                                        class="text-sm text-slate-300 bg-slate-900/50 p-3 rounded border border-red-500/20">
                                        None detected</p>
                                </div>

                                <!-- Regression Output -->
                                <div id="regression-area" class="mt-4 pt-2 hidden">
                                    <h4 class="text-xs font-bold text-brand-400 uppercase tracking-wider mb-2">
                                        Regression
                                        Model</h4>
                                    <div id="regression-content"
                                        class="text-sm text-slate-300 bg-slate-900/50 p-3 rounded border border-brand-500/20 font-mono overflow-auto max-h-40 scrollbar-thin mb-3">
                                        --
                                    </div>

                                    <!-- Prediction UI -->
                                    <div id="prediction-ui" class="hidden border-t border-slate-700/50 pt-2">
                                        <h5 class="text-xs font-semibold text-slate-400 mb-2">Predict Y from X</h5>
                                        <div class="flex items-center space-x-2">
                                            <input type="number" id="predict-x" placeholder="Enter X value"
                                                class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            <button id="btn-predict"
                                                class="bg-brand-600 hover:bg-brand-500 text-white px-3 py-2 rounded text-sm transition-colors">
                                                Predict
                                            </button>
                                        </div>
                                        <div id="predict-result" class="mt-2 text-sm font-bold text-brand-300 hidden">
                                            Predicted Y: <span id="val-predicted" class="text-white">--</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Explanations Panel -->
                        <div class="glass-panel p-6 rounded-xl overflow-hidden flex flex-col max-h-[800px]">
                            <h3 class="text-sm font-semibold text-slate-300 mb-4 border-b border-slate-700 pb-2">
                                <i class="fa-solid fa-graduation-cap text-brand-400 mr-2"></i>Step-by-Step
                            </h3>
                            <div id="explanation-container" class="overflow-y-auto pr-2 space-y-6">
                                <p class="text-slate-500 text-sm italic">Run a calculation to see steps here.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PDraw Dashboard Wrapper (Hidden) -->
            <div id="pdraw-dashboard" class="hidden transition-opacity duration-300 h-full flex flex-col">
                <!-- PDraw Content Placeholder -->
                <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 h-full overflow-y-auto lg:overflow-hidden">

                    <!-- PDraw Sidebar Controls (Left) -->
                    <div class="lg:col-span-3 glass-panel rounded-xl p-4 flex flex-col h-full overflow-hidden">

                        <!-- Scrollable Content -->
                        <div class="flex-1 overflow-y-auto custom-scrollbar space-y-4 pr-1">
                            <div class="border-b border-slate-700 pb-2">
                                <h3 class="text-sm font-bold text-green-400 uppercase tracking-wider">Data Structure
                                </h3>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs text-slate-500">Structure</label>
                                <select id="pdraw-structure"
                                    class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded p-2 focus:border-green-500 focus:outline-none">
                                    <option value="stack">Stack (LIFO)</option>
                                    <option value="queue">Queue (FIFO)</option>
                                    <option value="list">List (Array)</option>
                                    <option value="tuple">Tuple</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs text-slate-500">Implementation</label>
                                <select id="pdraw-impl"
                                    class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded p-2 focus:border-green-500 focus:outline-none">
                                    <option value="list">Python List</option>
                                    <option value="collections.deque">Collections.Deque</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs text-slate-500">Initial Values (CSV)</label>
                                <textarea id="pdraw-init-vals" rows="3"
                                    class="w-full bg-slate-900 border border-slate-700 text-white text-sm rounded p-2 focus:border-green-500 focus:outline-none placeholder-slate-600"
                                    placeholder="10, 20, 30"></textarea>
                            </div>

                            <div class="space-y-2 border-t border-slate-700 pt-4">
                                <h4 class="text-xs font-bold text-slate-400 uppercase">Operations</h4>
                                <div class="flex gap-2">
                                    <select id="pdraw-op-select"
                                        class="flex-1 bg-slate-800 border border-slate-700 text-white text-sm rounded p-2 focus:border-green-500 focus:outline-none">
                                        <!-- Populated dynamically -->
                                    </select>
                                    <button id="pdraw-add-op"
                                        class="px-3 py-1 bg-green-600 hover:bg-green-500 text-white rounded text-xs font-bold">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>

                                <!-- Op Inputs Container (Dynamic) -->
                                <div id="pdraw-op-params" class="space-y-2"></div>
                            </div>

                            <!-- Op List -->
                            <div
                                class="overflow-y-auto bg-slate-900/50 rounded-lg p-2 border border-slate-800 min-h-[150px]">
                                <ul id="pdraw-op-list" class="space-y-2 text-sm">
                                    <li class="text-center text-slate-600 text-xs italic mt-4">No operations added.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Fixed Bottom Button -->
                        <button id="pdraw-simulate"
                            class="mt-4 w-full py-3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-500 hover:to-teal-500 text-white font-bold rounded-lg shadow-lg shrink-0">
                            <i class="fa-solid fa-play mr-2"></i> Run Simulation
                        </button>
                    </div>

                    <!-- PDraw Playground (Center) -->
                    <div
                        class="lg:col-span-6 glass-panel rounded-xl p-6 flex flex-col h-[500px] lg:h-full overflow-hidden order-last lg:order-none">
                        <div class="flex justify-between items-center border-b border-slate-700 pb-4 mb-4">
                            <h3 class="text-lg font-bold text-white flex items-center">
                                <i class="fa-solid fa-microchip mr-2 text-green-400"></i> Execution Trace
                            </h3>
                        </div>

                        <!-- Execution Steps Output -->
                        <div id="pdraw-output" class="flex-1 overflow-y-auto space-y-4 pr-2 custom-scrollbar">
                            <div
                                class="flex flex-col items-center justify-center h-full text-center text-slate-500 opacity-50">
                                <i class="fa-solid fa-code text-4xl mb-2"></i>
                                <p>Build operations and click Run to see execution details.</p>
                            </div>
                        </div>
                    </div>

                    <!-- PDraw Visualizer (Right) -->
                    <div class="lg:col-span-3 glass-panel rounded-xl p-4 flex flex-col h-[300px] lg:h-full">
                        <div class="border-b border-slate-700 pb-2 mb-2 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider">Diagram</h3>
                            <div class="text-xs text-slate-500" id="pdraw-diag-label">Initial</div>
                        </div>
                        <div id="pdraw-diagram"
                            class="flex-1 bg-slate-900/50 rounded border border-slate-700 flex items-center justify-center relative overflow-hidden">
                            <p class="text-xs text-slate-600 italic">Visual representation area</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TGDraw Dashboard Wrapper (Hidden) -->
            <div id="tgdraw-dashboard" class="hidden transition-opacity duration-300 h-full flex flex-col">
                <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 h-full overflow-y-auto lg:overflow-hidden">
                    <!-- Sidebar -->
                    <div id="tgdraw-left-panel" class="lg:col-span-3 glass-panel rounded-xl flex flex-col space-y-4 h-auto lg:h-full overflow-y-auto sidebar-scrollbar order-2 lg:order-none min-h-[400px] p-4">
                        <h3 class="text-base font-bold text-amber-500 uppercase tracking-wider mb-1">TGDraw Lab</h3>
                        <p class="text-xs text-slate-500">Tree & Graph Visualizer</p>
                        <div class="p-4 bg-slate-800 rounded text-center text-slate-500 text-xs text-amber-500/50 mt-4">
                            Phase 3 Configuration
                        </div>
                    </div>
                    <!-- Canvas -->
                    <div id="tgdraw-canvas-panel"
                        class="lg:col-span-9 glass-panel rounded-xl p-4 flex flex-col relative h-[500px] lg:h-auto lg:max-h-[calc(100vh-6rem)] order-first lg:order-none overflow-hidden overflow-y-auto custom-scrollbar">
                        <div class="flex justify-between items-center border-b border-slate-700 pb-2 mb-2 shrink-0">
                            <h3 class="text-sm font-semibold text-slate-300">Canvas</h3>
                            <button id="tgdraw-fullscreen-btn" class="text-xs text-slate-400 hover:text-white transition" title="Toggle Fullscreen">
                                <i class="fa-solid fa-expand"></i>
                            </button>
                        </div>
                        
                        <!-- AD PLACEMENT: TGDraw Top -->
                        <div class="mb-2 hidden w-full max-w-[728px] mx-auto shrink-0" data-ad-placement="tgdraw_top"></div>

                        <div id="tgdraw-canvas"
                            class="flex-1 bg-slate-900/50 rounded flex items-center justify-center border border-slate-800 overflow-auto custom-scrollbar p-4">
                            <p class="text-slate-500 italic">Select a structure to begin.</p>
                        </div>

                         <!-- AD PLACEMENT: TGDraw Bottom -->
                        <div class="mt-2 hidden w-full max-w-[728px] mx-auto shrink-0" data-ad-placement="tgdraw_bottom"></div>
                    </div>


                    </div>
                </div>
            </div>

            <script>
                // TGDraw Fullscreen Toggle
                document.getElementById('tgdraw-fullscreen-btn').addEventListener('click', function() {
                    const panel = document.getElementById('tgdraw-canvas-panel');
                    const icon = this.querySelector('i');
                    
                    if (panel.classList.contains('fixed')) {
                        // Exit fullscreen
                        panel.classList.remove('fixed', 'inset-0', 'z-50', 'rounded-none', 'max-h-none');
                        panel.classList.add('lg:col-span-9', 'rounded-xl', 'max-h-[calc(100vh-6rem)]');
                        icon.classList.remove('fa-compress');
                        icon.classList.add('fa-expand');
                    } else {
                        // Enter fullscreen
                        panel.classList.add('fixed', 'inset-0', 'z-50', 'rounded-none', 'max-h-none');
                        panel.classList.remove('lg:col-span-9', 'rounded-xl', 'max-h-[calc(100vh-6rem)]');
                        icon.classList.remove('fa-expand');
                        icon.classList.add('fa-compress');
                    }
                });
            </script>

            <!-- PyViz Dashboard Wrapper (Hidden) -->
            <div id="pyviz-dashboard" class="hidden transition-opacity duration-300 h-full flex flex-col">
                <!-- Flex Layout: Left (30%) | Center (Auto) | Right (20%) -->
                <div class="flex-1 flex flex-col lg:flex-row gap-4 h-full overflow-y-auto lg:overflow-hidden">

                    <!-- PyViz Toolbox (Left) -->
                    <div
                        class="lg:w-[30%] glass-panel rounded-xl p-4 flex flex-col h-auto lg:h-full min-h-[400px] min-w-0">
                        <div class="border-b border-slate-700 pb-2 mb-2">
                            <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider">Toolbox</h3>
                        </div>

                        <!-- Toolbox Tabs/Category Selector -->
                        <div class="flex flex-wrap gap-1 mb-4" id="pyviz-toolbox-cats">
                            <button data-cat="vars"
                                class="px-3 py-2 text-sm font-bold rounded bg-blue-600 text-white">Vars</button>
                            <button data-cat="funcs"
                                class="px-3 py-2 text-sm font-bold rounded bg-slate-700 text-slate-300 hover:text-white">Funcs</button>
                            <button data-cat="logic"
                                class="px-3 py-2 text-sm font-bold rounded bg-slate-700 text-slate-300 hover:text-white">Logic</button>
                            <button data-cat="ds"
                                class="px-3 py-2 text-sm font-bold rounded bg-slate-700 text-slate-300 hover:text-white">Data</button>
                            <button data-cat="imports"
                                class="px-3 py-2 text-sm font-bold rounded bg-slate-700 text-slate-300 hover:text-white">Imports</button>
                        </div>

                        <!-- Toolbox Content Area (Dynamic) -->
                        <div id="pyviz-toolbox-content" class="flex-1 overflow-y-auto custom-scrollbar space-y-2">
                            <p class="text-sm text-slate-500 italic text-center mt-4">Select a category to add blocks.
                            </p>
                        </div>
                    </div>

                    <!-- PyViz Playground (Center) -->
                    <div
                        class="glass-panel rounded-xl p-0 flex flex-col relative overflow-hidden w-full h-[800px] shrink-0 lg:h-full lg:flex-1 bg-slate-900/50 border border-slate-700 min-w-0">
                        <!-- Playground Header -->
                        <div class="flex justify-between items-center p-3 border-b border-slate-700 bg-slate-800/50">
                            <h3 class="text-sm font-semibold text-slate-300 flex items-center">
                                <i class="fa-brands fa-python mr-2 text-blue-400"></i> Playground
                            </h3>
                            <div class="flex space-x-2 items-center">
                                <!-- Font Size Controls -->
                                <div
                                    class="flex items-center space-x-1 mr-2 bg-slate-800 rounded px-1 border border-slate-700">
                                    <button onclick="changeFontSize(-1)"
                                        class="w-5 h-5 flex items-center justify-center text-slate-400 hover:text-white text-xs"
                                        title="Decrease Font">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <span class="text-[10px] text-slate-500 font-mono">Aa</span>
                                    <button onclick="changeFontSize(1)"
                                        class="w-5 h-5 flex items-center justify-center text-slate-400 hover:text-white text-xs"
                                        title="Increase Font">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>

                                <button id="pyviz-btn-clear" class="text-xs text-red-400 hover:text-red-300"><i
                                        class="fa-solid fa-trash mr-1"></i>Clear</button>
                                <button id="pyviz-btn-download" class="text-xs text-blue-400 hover:text-blue-300"><i
                                        class="fa-solid fa-download mr-1"></i>.py</button>
                            </div>
                        </div>

                        <!-- Code Construction Area -->
                        <div id="pyviz-code-area"
                            class="flex-1 bg-slate-950 p-6 overflow-y-auto font-mono text-sm text-slate-300 leading-relaxed shadow-inner min-h-0">
                            <!-- Code Lines will be injected here -->
                            <div class="text-slate-600 italic text-center mt-20 select-none pointer-events-none">
                                Drag and drop or click blocks to build your Python code.
                            </div>
                        </div>

                        <!-- AI Footer -->
                        <div id="pyviz-footer" class="h-32 border-t border-slate-700 bg-slate-900 p-3 overflow-y-auto">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-bold text-yellow-500 uppercase">Feedback</span>
                                <button id="pyviz-btn-check-ai"
                                    class="px-2 py-1 text-xs bg-purple-600 hover:bg-purple-500 text-white rounded shadow-sm">
                                    <i class="fa-solid fa-check-double mr-1"></i> Check Logic
                                </button>
                            </div>
                            <div id="pyviz-ai-message" class="text-xs text-slate-300 font-mono">
                                <span class="text-slate-600">Waiting for validation...</span>
                            </div>
                        </div>
                    </div>

                    <!-- PyViz Inspector (Right) -->
                    <div
                        class="lg:w-[20%] glass-panel rounded-xl flex flex-col h-auto lg:h-full bg-slate-900/80 border border-slate-700 p-4 overflow-y-auto custom-scrollbar min-h-[200px] min-w-0">
                        <h3
                            class="text-sm font-bold text-yellow-500 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">
                            Inspector</h3>

                        <!-- Stats Summary -->
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center border-b border-slate-700/50 pb-2">
                                <span class="text-sm font-bold text-slate-300">Lines</span>
                                <span id="pyviz-stat-lines" class="text-blue-400 font-bold font-mono text-lg">0</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-slate-700/50 pb-2">
                                <span class="text-sm font-bold text-slate-300">Variables</span>
                                <span id="pyviz-stat-vars" class="text-blue-400 font-bold font-mono text-lg">0</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-slate-700/50 pb-2">
                                <span class="text-sm font-bold text-slate-300">Functions</span>
                                <span id="pyviz-stat-funcs" class="text-blue-400 font-bold font-mono text-lg">0</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-slate-700/50 pb-2">
                                <span class="text-sm font-bold text-slate-300">Loops</span>
                                <span id="pyviz-stat-loops" class="text-blue-400 font-bold font-mono text-lg">0</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-slate-700/50 pb-2">
                                <span class="text-sm font-bold text-slate-300">Conditionals</span>
                                <span id="pyviz-stat-conds" class="text-blue-400 font-bold font-mono text-lg">0</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-slate-700/50 pb-2">
                                <span class="text-sm font-bold text-slate-300">Imports</span>
                                <span id="pyviz-stat-imports" class="text-blue-400 font-bold font-mono text-lg">0</span>
                            </div>
                        </div>

                        <h3
                            class="text-sm font-bold text-yellow-500 uppercase tracking-wider mb-2 mt-4 border-b border-slate-700 pb-2">
                            Action Log</h3>
                        <ul id="pyviz-log-list" class="space-y-1 text-xs text-slate-400 font-mono">
                            <!-- Logs -->
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Dviz Dashboard Wrapper (Hidden) -->
            <div id="dviz-dashboard" class="hidden transition-opacity duration-300 h-full flex flex-col">
                <div class="flex-1 flex flex-col lg:flex-row gap-4 h-full overflow-hidden">

                    <!-- Sidebar (Level Selector) -->
                    <div id="dviz-sidebar"
                        class="w-full lg:w-1/4 glass-panel rounded-xl p-4 flex flex-col h-auto lg:h-full shrink-0 overflow-y-auto custom-scrollbar">
                        <div class="border-b border-slate-700 pb-2 mb-4">
                            <h3 class="text-sm font-bold text-purple-400 uppercase tracking-wider">Levels</h3>
                        </div>
                        <div id="dviz-level-list" class="space-y-2">
                            <p class="text-xs text-slate-500 italic">Loading levels...</p>
                        </div>
                    </div>

                    <!-- Main Content Area -->
                    <div id="dviz-main"
                        class="flex-1 glass-panel rounded-xl p-6 flex flex-col h-full overflow-hidden relative">
                        <!-- Breadcrumbs / Header -->
                        <div class="flex justify-between items-center mb-6 border-b border-slate-700 pb-4 shrink-0">
                            <div id="dviz-header">
                                <h2 class="text-2xl font-bold text-white">Welcome</h2>
                                <p class="text-slate-400 text-sm">Select a level to begin exploring.</p>
                            </div>
                            <div id="dviz-controls" class="hidden flex space-x-2">
                                <button id="dviz-btn-back"
                                    class="hidden px-3 py-1 bg-slate-700 text-slate-300 rounded hover:text-white text-sm">
                                    <i class="fa-solid fa-arrow-left mr-1"></i> Back
                                </button>
                            </div>
                        </div>

                        <!-- Scrollable Content Grid -->
                        <div id="dviz-content-area" class="flex-1 overflow-y-auto custom-scrollbar pr-2">
                            <div class="flex flex-col items-center justify-center h-full text-slate-500 opacity-50">
                                <i class="fa-solid fa-book-open text-6xl mb-4"></i>
                                <p class="text-lg">Select a Level from the sidebar</p>
                            </div>
                        </div>

                        <!-- Viewer Overlay (Hidden by default) -->
                        <div id="dviz-viewer-overlay"
                            class="hidden absolute inset-0 bg-slate-900/95 z-50 flex flex-col">
                            <div
                                class="h-12 bg-slate-950 flex items-center justify-between px-4 border-b border-slate-800 shrink-0">
                                <h3 id="dviz-viewer-title" class="text-sm font-bold text-white truncate max-w-[50%]">
                                    Viewer</h3>
                                <div class="flex items-center space-x-3">
                                    <button id="dviz-viewer-prev" onclick="navigateGallery(-1)"
                                        class="hidden text-slate-400 hover:text-white" title="Previous">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>
                                    <button id="dviz-viewer-next" onclick="navigateGallery(1)"
                                        class="hidden text-slate-400 hover:text-white" title="Next">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                    <div class="w-px h-4 bg-slate-700 mx-2"></div>
                                    <button id="dviz-viewer-fullscreen" onclick="toggleFullScreen()"
                                        class="text-slate-400 hover:text-white" title="Full Screen">
                                        <i class="fa-solid fa-expand"></i>
                                    </button>
                                    <button id="dviz-viewer-close" onclick="closeViewer()"
                                        class="text-slate-400 hover:text-white ml-2" title="Close">
                                        <i class="fa-solid fa-xmark text-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="dviz-viewer-body"
                                class="flex-1 overflow-hidden relative flex items-center justify-center">
                                <!-- Iframe or Image -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Data View Modal -->
    <div id="data-modal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" id="data-modal-backdrop"></div>

        <!-- Modal Content -->
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div
                class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-4xl h-[80vh] flex flex-col shadow-2xl transform transition-all">

                <!-- Header -->
                <div class="flex justify-between items-center p-4 border-b border-slate-700 bg-slate-800 rounded-t-xl">
                    <h3 class="text-lg font-semibold text-white">Imported Data</h3>
                    <button id="btn-close-modal" class="text-slate-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <!-- Table Container -->
                <!-- Data Operations Toolbar -->
                <div class="p-3 bg-slate-800 border-b border-slate-700 space-y-3">
                    <!-- Row 1: Cleaning -->
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2 border-r border-slate-700 pr-4">
                            <span class="text-xs text-slate-400 uppercase font-bold">Cleaning</span>
                            <button id="btn-drop-missing"
                                class="px-2 py-1 text-xs bg-red-900/30 text-red-300 border border-red-500/30 rounded hover:bg-red-900/50 transition-colors">
                                <i class="fa-solid fa-trash-can mr-1"></i>Drop Missing
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500">Impute:</span>
                            <select id="sel-impute-col"
                                class="bg-slate-900 border border-slate-700 text-slate-300 text-xs rounded p-1">
                                <option value="">Select Column...</option>
                            </select>
                            <button id="btn-fill-mean"
                                class="px-2 py-1 text-xs bg-slate-700 text-slate-300 rounded hover:bg-brand-600 hover:text-white transition-colors">
                                Mean
                            </button>
                            <button id="btn-fill-median"
                                class="px-2 py-1 text-xs bg-slate-700 text-slate-300 rounded hover:bg-brand-600 hover:text-white transition-colors">
                                Median
                            </button>
                        </div>
                    </div>

                    <!-- Row 2: Filtering -->
                    <div class="flex flex-wrap items-center gap-2 border-t border-slate-700/50 pt-2">
                        <span class="text-xs text-slate-400 uppercase font-bold mr-2">Filter</span>
                        <select id="sel-filter-col"
                            class="bg-slate-900 border border-slate-700 text-slate-300 text-xs rounded p-1">
                            <option value="">Column...</option>
                        </select>
                        <select id="sel-filter-op"
                            class="bg-slate-900 border border-slate-700 text-slate-300 text-xs rounded p-1">
                            <option value=">">></option>
                            <option value="<">&lt;</option>
                            <option value="=">=</option>
                            <option value="!=">!=</option>
                            <option value="contains">Contains</option>
                        </select>
                        <input type="text" id="inp-filter-val" placeholder="Value"
                            class="bg-slate-900 border border-slate-700 text-slate-300 text-xs rounded p-1 w-24">

                        <button id="btn-apply-filter"
                            class="px-2 py-1 text-xs bg-brand-600 text-white rounded hover:bg-brand-500 transition-colors ml-2">
                            <i class="fa-solid fa-filter mr-1"></i>Apply
                        </button>
                        <button id="btn-reset-data"
                            class="px-2 py-1 text-xs bg-slate-700 text-slate-400 rounded hover:bg-slate-600 hover:text-white transition-colors ml-auto">
                            <i class="fa-solid fa-rotate-left mr-1"></i>Reset Data
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-auto p-4 custom-scrollbar">
                    <table class="w-full text-sm text-left text-slate-300 border-collapse">
                        <thead class="text-xs text-slate-400 uppercase bg-slate-800 sticky top-0">
                            <tr id="data-table-head">
                                <!-- Headers populated by JS -->
                            </tr>
                        </thead>
                        <tbody id="data-table-body" class="divide-y divide-slate-800">
                            <!-- Rows populated by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="p-3 border-t border-slate-700 bg-slate-800 rounded-b-xl flex justify-between items-center">
                    <span class="text-xs text-slate-500 ml-2">Modifications apply immediately.</span>
                    <button id="btn-use-filtered"
                        class="px-4 py-2 text-xs font-bold bg-green-600 text-white rounded hover:bg-green-500 transition-colors shadow-lg">
                        <i class="fa-solid fa-check mr-2"></i>Use & Analyze Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- App Scripts -->
    <script src="js/backend-mock.js"></script>
    <script src="js/charts.js"></script>
    <script src="js/app.js"></script>
    <script src="js/pdraw.js?v=12"></script>
    <script src="js/nav.js?v=2"></script>
    <script src="js/tgdraw/tgdraw.js?v=12"></script>
    <script src="js/pyviz/pyviz.js?v=6"></script>
    <script src="js/dviz/dviz.js?v=7"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.switchPhase) {
                switchPhase('tgdraw');
                 const logo = document.querySelector('#sidebar h1');
                 if(logo) logo.innerHTML = '<i class="fa-solid fa-diagram-project mr-2 text-amber-500"></i>TGDraw';
            }
            // Hide other nav buttons
            ['nav-vdraw', 'nav-pdraw', 'nav-pyviz', 'nav-dviz'].forEach(id => {
                const btn = document.getElementById(id);
                if(btn) btn.style.display = 'none';
            });
            document.title = "TGDraw Lab";
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
              new AdManager({
                  appKey: 'tgdraw', // Note: This page (Graph/index.php) serves TGDraw AND PDraw and Stats actually?
                  // The sidebar has nav links to Stats/Linear/Graph/PyViz/DViz.
                  // But the code shows "PDraw Dashboard Wrapper" and "TGDraw Dashboard Wrapper" inside this file?
                  // Wait, looking at lines 788... "TGDraw Dashboard Wrapper".
                  // And Line 678 "PDraw Dashboard Wrapper".
                  // And Line 536 "VDraw Dashboard Wrapper" (Stats).
                  // This file seems to be a monolith if "frontend/Graph/" is confusingly named OR this IS a shared template?
                  // No, looking at file List... `frontend/Stats/`, `frontend/Linear/`, `frontend/Graph/`.
                  // The content viewed has multiple dashboards. 
                  // Ah, this is `frontend/Graph/index.php`. 
                  // It seems to contain hidden sections for TGDraw (Graph) and PDraw?
                  // Actually, TGDraw is Graph. PDraw is Linear. VDraw (Stats) is Stats.
                  // If this SINGLE file handles ALL, then appKey depends on active view.
                  // BUT, usually they are separate folders.
                  // Let's assume this is the Graph app.
                  // The `tgdraw` key is appropriate for Graph App.
                  // However, if the user requested placements for PDraw/TGDraw specifically...
                  // Let's just use 'tgdraw' for Graph/Tree app.
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