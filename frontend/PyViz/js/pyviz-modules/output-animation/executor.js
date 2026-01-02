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

    async init() {
        // Initialize UI in the new Tab
        this.outputPanel.create();

        // Show panel immediately so user sees "LOADING"
        this.outputPanel.show();
        this.outputPanel.setStatus("LOADING KERNEL...");

        try {
            await this.loader.load();
            this.outputPanel.setStatus("READY");
        } catch (e) {
            console.error(e);
            this.outputPanel.setStatus("ERROR");
            this.outputPanel.appendLine("Failed to load Python Runtime: " + e.message, "error");
        }
    }

    async run(code, delay, highlightCallback) {
        if (this.isExecuting) return;

        // Auto-switch to output tab
        if (window.switchPyVizTab) {
            window.switchPyVizTab('output');
        }

        this.isExecuting = true;
        this.outputPanel.show();
        this.outputPanel.clear();
        this.outputPanel.setStatus("RUNNING...");

        this.loader.run(code, {
            delay: delay,
            onLine: (lineno) => {
                // Determine highlighting strategy.
                // Assuming `highlightCallback` is provided by index.js which knows about the editor.
                if (highlightCallback) highlightCallback(lineno);
            },
            onPrint: (text) => {
                this.outputPanel.appendLine(text);
            },
            onError: (err) => {
                this.outputPanel.appendLine(err, "error");
                this.outputPanel.setStatus("ERROR");
                this.isExecuting = false;
            },
            onInput: (prompt) => {
                // Handling input is tricky with the constraints (Blocking Worker).
                // If we succeeded in the worker strategy, we would:
                // 1. Show modal
                // 2. on submit -> send value back to worker
                this.inputHandler.requestInput(prompt).then(val => {
                    // Send to worker (not yet implemented in loader fully)
                    console.log("Input received:", val);
                });
            },
            onFinished: () => {
                this.isExecuting = false;
                this.outputPanel.setStatus("FINISHED");
            }
        });
    }
}
