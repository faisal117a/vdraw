export class OutputPanel {
    constructor(containerId) {
        this.containerId = containerId;
        this.panel = null;
        this.contentArea = null;
        this.isVisible = false;
        this.currentFontSize = 18;
    }

    create() {
        // In the new Tabbed layout, the container provided by Executor might be the tab ID itself or we check if panel exists
        let container = document.getElementById('tab-content-output');

        if (!container) {
            console.warn("PyViz: Tab Output container not found, creating fallback.");
            container = document.body;
        } else {
            // Clear placeholder text
            container.innerHTML = "";
        }

        // Remove existing panel if it exists (cleanup old floating windows)
        const existing = document.getElementById(this.containerId);
        if (existing) {
            existing.remove();
        }

        // Create the panel container - Fills the tab
        this.panel = document.createElement("div");
        this.panel.id = this.containerId;
        this.panel.style.cssText = `
            display: flex;
            flex-direction: column;
            background: transparent;
            width: 100%;
            height: 100%;
            overflow: hidden;
            flex: 1;
        `;

        // Title bar with Zoom Controls
        const titleBar = document.createElement("div");
        titleBar.style.cssText = `
            padding: 5px 10px;
            font-size: 13px;
            font-weight: bold;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e293b;
            flex-shrink: 0;
            border-radius: 4px;
            margin-bottom: 5px;
        `;

        titleBar.innerHTML = `
            <div class="flex items-center gap-2">
                <span>Output</span>
                <div class="flex bg-slate-800 rounded border border-slate-600 ml-2">
                    <button id="pv-out-zoom-out" title="Decrease Font" class="px-2 py-0.5 text-xs text-slate-300 hover:text-white border-r border-slate-600 hover:bg-slate-700 transition-colors rounded-l"><i class="fa-solid fa-minus"></i></button>
                    <button id="pv-out-zoom-in" title="Increase Font" class="px-2 py-0.5 text-xs text-slate-300 hover:text-white hover:bg-slate-700 transition-colors rounded-r"><i class="fa-solid fa-plus"></i></button>
                </div>
            </div>
            <span id="pyviz-exec-status" style="color: #007acc; font-size: 11px;">READY</span>
        `;
        this.panel.appendChild(titleBar);

        // Content area
        this.contentArea = document.createElement("div");
        this.contentArea.id = `${this.containerId}-content`;
        this.contentArea.style.cssText = `
            flex-grow: 1;
            padding: 10px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-size: ${this.currentFontSize}px; 
            font-family: 'Consolas', 'Monaco', monospace;
            color: #d4d4d4;
            background: #0f172a; /* darker background for terminal feel */
            border-radius: 4px;
        `;
        this.panel.appendChild(this.contentArea);

        // Append to the TAB container
        container.appendChild(this.panel);

        // Event Listeners for Zoom
        setTimeout(() => {
            const btnIn = this.panel.querySelector('#pv-out-zoom-in');
            const btnOut = this.panel.querySelector('#pv-out-zoom-out');

            if (btnIn) btnIn.onclick = () => this.changeFontSize(2);
            if (btnOut) btnOut.onclick = () => this.changeFontSize(-2);
        }, 0);
    }

    changeFontSize(delta) {
        this.currentFontSize = Math.max(10, Math.min(40, this.currentFontSize + delta));
        if (this.contentArea) {
            this.contentArea.style.fontSize = `${this.currentFontSize}px`;
        }
    }

    show() {
        if (this.panel) {
            this.panel.style.display = "flex";
            this.isVisible = true;
        }
    }

    // Hide is slightly redundant in tab mode but we keep it for clearing
    hide() {
        // In tab mode we don't really hide the panel, the tab hides.
        // But we can reset if needed.
        this.isVisible = false;
    }

    clear() {
        if (this.contentArea) {
            this.contentArea.innerHTML = "";
        }
        this.setStatus("READY");
    }

    appendLine(text, type = "normal") {
        if (!this.contentArea) return;

        const line = document.createElement("div");
        line.textContent = text;

        if (type === "error") {
            line.style.color = "#f48771";
        } else if (type === "system") {
            line.style.color = "#6a9955";
            line.style.fontStyle = "italic";
        }

        this.contentArea.appendChild(line);
        this.contentArea.scrollTop = this.contentArea.scrollHeight;
    }

    setStatus(status) {
        const statusEl = this.panel.querySelector("#pyviz-exec-status");
        if (statusEl) {
            statusEl.textContent = status;
        }
    }
}
