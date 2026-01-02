import { CONFIG } from './config.js';
import { Executor } from './executor.js';

class OutputAnimationModule {
    constructor() {
        this.executor = null;
        this.initialized = false;
        this._lastHighlight = null;
    }

    init() {
        if (!CONFIG.ENABLED) return;
        if (this.initialized) return;

        // Cleanup any old floating panels from previous versions
        const oldPanel = document.getElementById(CONFIG.CONTAINER_ID);
        if (oldPanel && oldPanel.parentElement === document.body) {
            oldPanel.remove();
        }

        // 1. Render Button
        this.renderButton();

        // 2. Initialize Executor (but don't load runtime yet)
        this.executor = new Executor();

        this.initialized = true;
        console.log("PyViz Output Animation Module Initialized");
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
                <option value="500">Fast (0.5s)</option>
                <option value="1000" selected>Normal (1s)</option>
                <option value="2000">Slow (2s)</option>
                <option value="3000">Very Slow (3s)</option>
            `;

            // Run Button
            const btn = document.createElement("button");
            btn.id = CONFIG.BUTTON_ID;
            btn.innerHTML = '<i class="fa-solid fa-play mr-1"></i> Run';
            btn.className = "text-xs text-green-400 hover:text-green-300 font-bold";
            btn.onclick = () => this.startAnimation();

            container.appendChild(select);
            container.appendChild(btn);

            // Insert at the beginning of the button group
            headerBtns.insertBefore(container, headerBtns.firstChild);
            return;
        }

        console.warn("PyViz Output Animation: Could not find suitable place for button.");
    }

    async startAnimation() {
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
            alert("No code to execute. Please build some code first.");
            return;
        }

        // Construct code with proper indentation
        const code = lines.map(l => "    ".repeat(l.indent) + l.code).join("\n");

        if (!code.trim()) {
            alert("Code is empty.");
            return;
        }

        // 2. Initialize runtime if needed
        await this.executor.init();

        // 3. Run
        const delaySelect = document.getElementById('pyviz-speed-select');
        const delay = delaySelect ? parseInt(delaySelect.value) : 1000;

        this.executor.run(code, delay, this.highlightLine.bind(this));
    }

    highlightLine(lineno) {
        // lineno is 1-based from Python trace
        const index = lineno - 1;
        const codeArea = document.getElementById('pyviz-code-area');

        if (!codeArea) return;

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
