import { RuntimeLoader } from './runtime-loader.js';
import { OutputPanel } from './output-panel.js';
import { InputHandler } from './input-handler.js';
import { CONFIG } from './config.js';

export class Executor {
    constructor() {
        this.loader = new RuntimeLoader(CONFIG);
        this.outputPanel = new OutputPanel(CONFIG.CONTAINER_ID);
        this.inputHandler = new InputHandler();
        this.isExecuting = false;

        // We need a way to reference the editor for highlighting. 
        // Since we cannot modify existing code, we look up the editor instance or DOM elements.
        // PyViz likely uses CodeMirror or Ace. I'll need to inspect the DOM to find how to highlight lines.
        // For now, I'll assume a generic highlight function that I can implement or hook up.
    }

    async init(silent = false) {
        // Initialize UI in the new Tab
        this.outputPanel.create();

        if (!silent) {
            // Show panel immediately so user sees "LOADING"
            this.outputPanel.show();
            this.outputPanel.setStatus("LOADING KERNEL...");
        }

        try {
            await this.loader.load();
            if (!silent) this.outputPanel.setStatus("READY");
        } catch (e) {
            console.error(e);
            if (!silent) {
                this.outputPanel.setStatus("ERROR");
                this.outputPanel.appendLine("Failed to load Python Runtime: " + e.message, "error");
            }
            throw e; // Re-throw so caller knows it failed
        }
    }

    async run(code, delay, highlightCallback, onComplete) {
        if (this.isExecuting) return;

        // Auto-switch to output tab
        if (window.switchPyVizTab) {
            window.switchPyVizTab('output');
        }

        this.isExecuting = true;
        this.outputPanel.show();
        this.outputPanel.clear();
        this.outputPanel.setStatus("RUNNING...");

        // Clear Dry Run Table
        if (window.clearDryRunTable) window.clearDryRunTable();

        // Small delay to allow UI to paint the clear operation before heavy worker usage
        setTimeout(() => {
            this.loader.run(code, {
                delay: delay,
                onLine: (lineno, locals, prev_lineno) => {
                    // Dry Run Update: Log the *Previous* Line using the *Current* State.
                    // This creates the "After Execution" effect.
                    if (prev_lineno && window.updateDryRunTable && locals) {
                        window.updateDryRunTable(prev_lineno, locals);
                    }

                    // Store current locals globally for runtime inspection
                    window.pyvizRuntimeLocals = locals || {};

                    // Determine highlighting strategy.
                    // Assuming `highlightCallback` is provided by index.js which knows about the editor.
                    if (highlightCallback) highlightCallback(lineno, locals);
                },
                onPrint: (text) => {
                    this.outputPanel.appendLine(text);
                },
                onError: (err) => {
                    this.outputPanel.appendLine(err, "error");
                    this.outputPanel.setStatus("ERROR");
                    this.isExecuting = false;
                    if (onComplete) onComplete();
                },
                onInput: (prompt) => {
                    const sid = this.loader.currentSessionId;
                    if (!sid) {
                        console.error("No session ID for input");
                        return;
                    }

                    this.inputHandler.requestInput(prompt).then(val => {
                        // Determine Backend URL
                        let baseUrl = window.location.origin + window.location.pathname;
                        if (baseUrl.includes('/frontend/')) {
                            baseUrl = baseUrl.split('/frontend/')[0];
                        } else if (baseUrl.endsWith('/')) {
                            baseUrl = baseUrl.slice(0, -1);
                        }
                        const ioUrl = `${baseUrl}/backend/io_proxy.php`;

                        // Write to Proxy
                        fetch(`${ioUrl}?action=write&id=${sid}&val=${encodeURIComponent(val)}`)
                            .then(() => console.log("Input submitted to proxy"))
                            .catch(e => console.error("Input proxy failed", e));
                    });
                },
                onFinished: () => {
                    this.isExecuting = false;
                    this.outputPanel.setStatus("FINISHED");
                    if (onComplete) onComplete();
                }
            });
        }, 50);
    }
    stop() {
        if (this.isExecuting) {
            this.loader.terminate();
            this.isExecuting = false;
            this.outputPanel.setStatus("STOPPED");
            this.outputPanel.clear(); // User asked to clear force
            this.outputPanel.appendLine("[System] Execution stopped by user.", "error");

            // Remove highlight
            const codeArea = document.getElementById('pyviz-code-area');
            if (codeArea) {
                Array.from(codeArea.children).forEach(child => {
                    child.style.backgroundColor = "";
                    child.style.boxShadow = "";
                });
            }
        }
    }
}
