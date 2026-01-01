export class OutputPanel {
    constructor(containerId) {
        this.containerId = containerId;
        this.panel = null;
        this.contentArea = null;
        this.isVisible = false;
    }

    create() {
        if (document.getElementById(this.containerId)) return;

        // Create the panel container
        this.panel = document.createElement("div");
        this.panel.id = this.containerId;
        this.panel.style.cssText = `
            display: none;
            flex-direction: column;
            background: #1e1e1e;
            color: #d4d4d4;
            border: 1px solid #333;
            border-radius: 4px;
            margin-bottom: 10px;
            font-family: 'Consolas', 'Monaco', monospace;
            height: 200px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            position: relative; /* For z-index context */
            z-index: 1000;
        `;

        // Title bar
        const titleBar = document.createElement("div");
        titleBar.style.cssText = `
            background: #252526;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: bold;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;
        titleBar.innerHTML = `<span>Output Results</span> <span id="pyviz-exec-status" style="color: #007acc; font-size: 10px;">READY</span>`;
        this.panel.appendChild(titleBar);

        // Content area
        this.contentArea = document.createElement("div");
        this.contentArea.id = `${this.containerId}-content`;
        this.contentArea.style.cssText = `
            flex-grow: 1;
            padding: 10px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-size: 13px;
        `;
        this.panel.appendChild(this.contentArea);

        // Insert into DOM - "New panel added above the Inspector"
        // I need to find the inspector or a suitable place in the layout.
        // Assuming there is a container for the inspector.
        // For now, I'll attempt to find '.inspector-container' or just append to 'body' and move it later if I can't find it.
        // Better strategy: The index.js will handle the specific injection point, this just returns the element or appends to a target.
    }

    mount(targetElement, position = "before") {
        if (!this.panel) this.create();

        if (position === "before" && targetElement) {
            targetElement.parentNode.insertBefore(this.panel, targetElement);
        } else if (position === "after" && targetElement) {
            targetElement.parentNode.insertBefore(this.panel, targetElement.nextSibling);
        } else if (targetElement) {
            targetElement.appendChild(this.panel);
        }
    }

    show() {
        if (this.panel) {
            this.panel.style.display = "flex";
            this.isVisible = true;
        }
    }

    hide() {
        if (this.panel) {
            this.panel.style.display = "none";
            this.isVisible = false;
        }
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
