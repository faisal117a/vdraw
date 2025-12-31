<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PyViz | Python Visualizer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
    </style>
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
                <h2 class="text-lg font-semibold text-slate-200">PyViz Explainer</h2>
            </div>
        </header>

        <!-- PyViz Dashboard Wrapper -->
        <div id="pyviz-dashboard" class="flex-1 overflow-hidden relative flex flex-col p-4">
             <!-- Flex Layout: Left (30%) | Center (Auto) | Right (20%) -->
            <div class="flex-1 flex flex-col lg:flex-row gap-4 h-full overflow-y-auto lg:overflow-hidden">

                <!-- PyViz Toolbox (Left) -->
                <div class="lg:w-[30%] glass-panel rounded-xl p-4 flex flex-col h-auto lg:h-full min-h-[400px] min-w-0">
                    <div class="border-b border-slate-700 pb-2 mb-2">
                        <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider">Toolbox</h3>
                    </div>

                    <!-- Toolbox Tabs/Category Selector -->
                    <div class="flex flex-wrap gap-1 mb-4" id="pyviz-toolbox-cats">
                        <button data-cat="vars" class="px-3 py-2 text-sm font-bold rounded bg-blue-600 text-white">Vars</button>
                        <button data-cat="funcs" class="px-3 py-2 text-sm font-bold rounded bg-slate-700 text-slate-300 hover:text-white">Funcs</button>
                        <button data-cat="logic" class="px-3 py-2 text-sm font-bold rounded bg-slate-700 text-slate-300 hover:text-white">Logic</button>
                        <button data-cat="ds" class="px-3 py-2 text-sm font-bold rounded bg-slate-700 text-slate-300 hover:text-white">Data</button>
                        <button data-cat="imports" class="px-3 py-2 text-sm font-bold rounded bg-slate-700 text-slate-300 hover:text-white">Imports</button>
                    </div>

                    <!-- Toolbox Content Area (Dynamic) -->
                    <div id="pyviz-toolbox-content" class="flex-1 overflow-y-auto custom-scrollbar space-y-2">
                        <p class="text-sm text-slate-500 italic text-center mt-4">Select a category to add blocks.</p>
                    </div>
                </div>

                <!-- PyViz Playground (Center) -->
                <div class="glass-panel rounded-xl p-0 flex flex-col relative overflow-hidden w-full h-[800px] shrink-0 lg:h-full lg:flex-1 bg-slate-900/50 border border-slate-700 min-w-0">
                    <!-- Playground Header -->
                    <div class="flex justify-between items-center p-3 border-b border-slate-700 bg-slate-800/50">
                        <h3 class="text-sm font-semibold text-slate-300 flex items-center">
                            <i class="fa-brands fa-python mr-2 text-blue-400"></i> Playground
                        </h3>
                        <div class="flex space-x-2 items-center">
                            <!-- Font Size Controls -->
                            <div class="flex items-center space-x-1 mr-2 bg-slate-800 rounded px-1 border border-slate-700">
                                <button onclick="changeFontSize(-1)" class="w-5 h-5 flex items-center justify-center text-slate-400 hover:text-white text-xs" title="Decrease Font"><i class="fa-solid fa-minus"></i></button>
                                <span class="text-[10px] text-slate-500 font-mono">Aa</span>
                                <button onclick="changeFontSize(1)" class="w-5 h-5 flex items-center justify-center text-slate-400 hover:text-white text-xs" title="Increase Font"><i class="fa-solid fa-plus"></i></button>
                            </div>

                            <button id="pyviz-btn-clear" class="text-xs text-red-400 hover:text-red-300"><i class="fa-solid fa-trash mr-1"></i>Clear</button>
                            <button id="pyviz-btn-download" class="text-xs text-blue-400 hover:text-blue-300"><i class="fa-solid fa-download mr-1"></i>.py</button>
                        </div>
                    </div>

                    <!-- Code Construction Area -->
                    <div id="pyviz-code-area" class="flex-1 bg-slate-950 p-6 overflow-y-auto font-mono text-sm text-slate-300 leading-relaxed shadow-inner min-h-0">
                        <!-- Code Lines will be injected here -->
                        <div class="text-slate-600 italic text-center mt-20 select-none pointer-events-none">
                            Drag and drop or click blocks to build your Python code.
                        </div>
                    </div>

                    <!-- AI Footer -->
                    <div id="pyviz-footer" class="h-32 border-t border-slate-700 bg-slate-900 p-3 overflow-y-auto">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-yellow-500 uppercase">Feedback</span>
                            <button id="pyviz-btn-check-ai" class="px-2 py-1 text-xs bg-purple-600 hover:bg-purple-500 text-white rounded shadow-sm">
                                <i class="fa-solid fa-check-double mr-1"></i> Check Logic
                            </button>
                        </div>
                        <div id="pyviz-ai-message" class="text-xs text-slate-300 font-mono">
                            <span class="text-slate-600">Waiting for validation...</span>
                        </div>
                    </div>
                </div>

                <!-- PyViz Inspector (Right) -->
                <div class="lg:w-[20%] glass-panel rounded-xl flex flex-col h-auto lg:h-full bg-slate-900/80 border border-slate-700 p-4 overflow-y-auto custom-scrollbar min-h-[200px] min-w-0">
                    <h3 class="text-sm font-bold text-yellow-500 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">Inspector</h3>

                    <!-- Stats Summary -->
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between items-center border-b border-slate-700/50 pb-2"><span class="text-sm font-bold text-slate-300">Lines</span><span id="pyviz-stat-lines" class="text-blue-400 font-bold font-mono text-lg">0</span></div>
                        <div class="flex justify-between items-center border-b border-slate-700/50 pb-2"><span class="text-sm font-bold text-slate-300">Variables</span><span id="pyviz-stat-vars" class="text-blue-400 font-bold font-mono text-lg">0</span></div>
                        <div class="flex justify-between items-center border-b border-slate-700/50 pb-2"><span class="text-sm font-bold text-slate-300">Functions</span><span id="pyviz-stat-funcs" class="text-blue-400 font-bold font-mono text-lg">0</span></div>
                         <div class="flex justify-between items-center border-b border-slate-700/50 pb-2"><span class="text-sm font-bold text-slate-300">Loops</span><span id="pyviz-stat-loops" class="text-blue-400 font-bold font-mono text-lg">0</span></div>
                        <div class="flex justify-between items-center border-b border-slate-700/50 pb-2"><span class="text-sm font-bold text-slate-300">Conditionals</span><span id="pyviz-stat-conds" class="text-blue-400 font-bold font-mono text-lg">0</span></div>
                        <div class="flex justify-between items-center border-b border-slate-700/50 pb-2"><span class="text-sm font-bold text-slate-300">Imports</span><span id="pyviz-stat-imports" class="text-blue-400 font-bold font-mono text-lg">0</span></div>
                    </div>

                    <h3 class="text-sm font-bold text-yellow-500 uppercase tracking-wider mb-2 mt-4 border-b border-slate-700 pb-2">Action Log</h3>
                    <ul id="pyviz-log-list" class="space-y-1 text-xs text-slate-400 font-mono">
                        <!-- Logs -->
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <!-- App Scripts -->
    <script src="js/pyviz/pyviz.js?v=6"></script>
    <script>
        // Force Init
        document.addEventListener('DOMContentLoaded', () => {
             if(typeof initPyViz === 'function') initPyViz();
        });
    </script>
</body>
</html>