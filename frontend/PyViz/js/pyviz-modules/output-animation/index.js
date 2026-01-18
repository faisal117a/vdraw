import { CONFIG } from './config.js';
import { Executor } from './executor.js';

class OutputAnimationModule {
    constructor() {
        this.executor = null;
        this.initialized = false;
        this._lastHighlight = null;
    }

    async init() {
        if (!CONFIG.ENABLED) return;
        if (this.initialized) return;

        // Cleanup any old floating panels from previous versions
        const oldPanel = document.getElementById(CONFIG.CONTAINER_ID);
        if (oldPanel && oldPanel.parentElement === document.body) {
            oldPanel.remove();
        }

        // 1. Initialize Executor
        this.executor = new Executor();

        // 2. Render Button (Disabled initially)
        this.renderButton();

        this.initialized = true;

        // 3. Start Background Load
        this.setButtonState('loading');
        try {
            await this.executor.init(true); // Silent load
            this.setButtonState('ready');
        } catch (e) {
            console.error("Background Runtime Load Failed", e);
            this.setButtonState('error');
        }
    }

    renderButton() {
        // "Label: Output Animation", "Placement: Top or Bottom of Playground"
        const headerBtns = document.querySelector('#pyviz-btn-clear')?.parentNode;

        if (headerBtns) {
            const container = document.createElement("div");
            container.className = "flex items-center space-x-2 mr-2 border-r border-slate-700 pr-2";

            // Speed Select
            const select = document.createElement("select");
            select.id = "pyviz-speed-select";
            select.className = "bg-slate-900 border border-slate-600 rounded text-[10px] text-white p-1 focus:outline-none focus:border-blue-500";
            select.innerHTML = `
                <option value="10">Very Fast (0s)</option>
                <option value="500">Fast (0.5s)</option>
                <option value="1000" selected>Normal (1s)</option>
                <option value="2000">Slow (2s)</option>
                <option value="3000">Very Slow (3s)</option>
            `;

            // Run Button
            const btn = document.createElement("button");
            btn.id = CONFIG.BUTTON_ID;
            // Initial State: Loading
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
            btn.className = "px-2 py-1 text-xs text-slate-500 font-bold border border-slate-700/30 rounded transition-all cursor-not-allowed";
            btn.title = "Downloading Runtime...";
            btn.disabled = true;
            btn.onclick = () => this.startAnimation();

            // Stop Button (New)
            const stopBtn = document.createElement("button");
            stopBtn.innerHTML = '<i class="fa-solid fa-stop"></i>';
            stopBtn.className = "px-2 py-1 text-xs text-red-500 hover:text-red-400 font-bold ml-2 border border-red-900/30 rounded transition-all hidden"; // Hidden by default
            stopBtn.title = "Stop Execution";
            stopBtn.onclick = () => this.stopAnimation();
            stopBtn.id = "pyviz-btn-stop";

            container.appendChild(select);
            container.appendChild(btn);
            container.appendChild(stopBtn);

            // Insert at the beginning of the button group
            headerBtns.insertBefore(container, headerBtns.firstChild);
            return;
        }

        console.warn("PyViz Output Animation: Could not find suitable place for button.");
    }

    setButtonState(state) {
        const btn = document.getElementById(CONFIG.BUTTON_ID);
        if (!btn) return;

        if (state === 'loading') {
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> <span class="hidden md:inline">Loading...</span>';
            btn.className = "px-2 py-1 text-xs text-orange-400 font-bold border border-orange-900/30 rounded transition-all cursor-wait";
            btn.title = "Downloading Python Runtime...";
            btn.disabled = true;
            this.showLoadingToast();
        } else if (state === 'ready') {
            btn.innerHTML = '<i class="fa-solid fa-play"></i>';
            btn.className = "px-2 py-1 text-xs text-green-400 hover:text-green-300 font-bold border border-green-900/30 rounded transition-all cursor-pointer";
            btn.title = "Run Code";
            btn.disabled = false;
            this.hideLoadingToast();
        } else if (state === 'error') {
            btn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
            btn.className = "px-2 py-1 text-xs text-red-500 font-bold border border-red-900/30 rounded transition-all";
            btn.title = "Runtime Load Failed";
            btn.disabled = true;
            this.hideLoadingToast();
        } else if (state === 'running') {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            btn.className = "px-2 py-1 text-xs text-slate-500 border border-slate-700 rounded cursor-not-allowed";
            btn.disabled = true;
            this.hideLoadingToast(); // Just in case
        }
    }

    showLoadingToast() {
        if (document.getElementById('pyviz-loading-toast')) return;

        const toast = document.createElement('div');
        toast.id = 'pyviz-loading-toast';
        toast.className = 'fixed bottom-4 right-4 bg-slate-800 text-white p-3 rounded shadow-lg border border-slate-600 z-50 flex items-center gap-2 transition-all duration-300 transform translate-y-0 opacity-100';
        toast.innerHTML = `
            <i class="fa-solid fa-circle-notch fa-spin text-orange-400"></i>
            <span class="animate-pulse text-xs font-bold text-orange-200">Python Runtime Downloading...</span>
        `;
        document.body.appendChild(toast);
    }

    hideLoadingToast() {
        const toast = document.getElementById('pyviz-loading-toast');
        if (toast) {
            toast.classList.add('translate-y-10', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }
    }

    async startAnimation() {
        const btn = document.getElementById(CONFIG.BUTTON_ID);
        const stopBtn = document.getElementById("pyviz-btn-stop"); // We should store consistent ID reference

        // start tracking PyViz api code
        if (window.track) window.track('button', 'Run Animation');
        // end tracking PyViz api code

        // Auto-switch to output tab immediately
        if (window.switchPyVizTab) {
            window.switchPyVizTab('output');
        }

        // 1. Get Code from Global State
        if (typeof window.pyvizState === 'undefined' || !window.pyvizState.lines) {
            console.error("PyViz State not found.");
            alert("Could not access PyViz code state.");
            return;
        }

        const lines = window.pyvizState.lines;
        if (lines.length === 0) {
            console.warn("No code to execute. Please build some code first.");
            // Optional: Show UI message instead of blocking alert
            return;
        }

        // Construct code with proper indentation
        const code = lines.map(l => "    ".repeat(l.indent) + l.code).join("\n");

        if (!code.trim()) {
            console.warn("Code is empty.");
            return;
        }

        // 2. Initialize runtime if needed (Double check)
        this.setButtonState('running');
        if (stopBtn) stopBtn.classList.remove('hidden');

        try {
            await this.executor.init(); // Just in case, though it should be ready
        } catch (e) {
            this.setButtonState('error');
            if (stopBtn) stopBtn.classList.add('hidden');
            return;
        }

        // 3. Run
        const delaySelect = document.getElementById('pyviz-speed-select');
        const delay = delaySelect ? parseInt(delaySelect.value) : 1000;

        // Trigger AI Check
        if (typeof window.runAICheck === 'function') window.runAICheck();

        // Hook into finish to reset button
        // We need a wrapper around onFinished? 
        // Executor config onFinished is passed in run(). 
        // We can't easily intercept it without changing executor.run signature or wrapping callbacks.
        // Actually, executor.run is async but it returns 'void' (fire and forget inside usually?)
        // Let's check executor.js. run uses loader.run which uses worker.postMessage.
        // It's event driven. The `onFinished` callback in `executor.js` resets `this.isExecuting`.
        // We need `OutputAnimationModule` to know when it finishes.

        // Let's monkeypatch or pass a callback wrapper?
        // Easier: modify executor.run to accept onFinish callback override or addition? 
        // Or just let Executor handle its own UI? Executor handles OutputPanel, but not this header button.

        // FIX: The simplest way without refactoring Executor too much is to poll or ...
        // No, Executor calls `onFinished` in config passed to loader.
        // Let's modify Executor.run to return a Promise that resolves when done? No, it's event based.

        // I'll trust the Executor logic for now but I really should reset the Play button.
        // I will add a `onComplete` callback to Executor.run arguments in executor.js.

        this.executor.run(code, delay, this.highlightLine.bind(this), () => {
            // On Complete
            this.setButtonState('ready');
            if (stopBtn) stopBtn.classList.add('hidden');
        });
    }

    stopAnimation() {
        if (this.executor) {
            this.executor.stop();
            this.setButtonState('ready');
            const stopBtn = document.getElementById("pyviz-btn-stop");
            if (stopBtn) stopBtn.classList.add('hidden');
        }
    }

    highlightLine(lineno, locals = {}) {
        // lineno is 1-based from Python trace
        const index = lineno - 1;
        const codeArea = document.getElementById('pyviz-code-area');

        if (!codeArea) return;

        // Store current runtime state for hover inspection
        this.currentLocals = locals;
        this.currentLineno = lineno;

        // Remove previous highlight
        if (this._lastHighlight) {
            this._lastHighlight.classList.remove('bg-slate-800/80'); // Use existing dark theme colors
            this._lastHighlight.style.boxShadow = ""; // Clear shadow
            this._lastHighlight.style.backgroundColor = "";
        }

        // Add new highlight
        // The children of codeArea are the line divs
        const lineDiv = codeArea.children[index];
        if (lineDiv) {
            // Apply nice highlighting
            lineDiv.style.backgroundColor = "#374151"; // slate-700
            // Use inset shadow to avoid layout shift (prevent "added indentation" effect)
            lineDiv.style.boxShadow = "inset 4px 0 0 0 #22c55e"; // border-green-500 lookalike

            lineDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            this._lastHighlight = lineDiv;
        }
    }
}

const module = new OutputAnimationModule();

window.addEventListener('load', () => {
    // Small delay to ensure other scripts ran
    setTimeout(() => module.init(), 1000);
});

window.PyVizOutputAnimation = module;
