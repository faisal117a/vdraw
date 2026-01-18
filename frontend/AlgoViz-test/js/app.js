/**
 * AlgoViz Application Entry Point
 */

// Core modules can stay as static imports
import { UI } from './modules/ui.js';
import { Engine } from './modules/engine.js';
import { AlgorithmRegistry } from './modules/registry.js';

// Use dynamic imports with cache-busting for algorithms
const timestamp = Date.now();

const App = {
    init: async () => {
        console.log("AlgoViz initializing...");

        // 1. Load and Register Algorithms with cache-busting
        try {
            const [LinearSearch, BinarySearch, SelectionSort, BubbleSort, MergeSort, QuickSort] = await Promise.all([
                import(`../algorithms/linear_search.js?v=${timestamp}`).then(m => m.LinearSearch),
                import(`../algorithms/binary_search.js?v=${timestamp}`).then(m => m.BinarySearch),
                import(`../algorithms/selection_sort.js?v=${timestamp}`).then(m => m.SelectionSort),
                import(`../algorithms/bubble_sort.js?v=${timestamp}`).then(m => m.BubbleSort),
                import(`../algorithms/merge_sort.js?v=${timestamp}`).then(m => m.MergeSort),
                import(`../algorithms/quick_sort.js?v=${timestamp}`).then(m => m.QuickSort)
            ]);

            AlgorithmRegistry.register(LinearSearch);
            AlgorithmRegistry.register(BinarySearch);
            AlgorithmRegistry.register(SelectionSort);
            AlgorithmRegistry.register(BubbleSort);
            AlgorithmRegistry.register(MergeSort);
            AlgorithmRegistry.register(QuickSort);
        } catch (err) {
            console.error("Failed to load algorithms:", err);
        }

        // 2. Initialize Modules
        UI.init();

        Engine.init(
            // Render Callback
            (step, index, total) => {
                UI.renderStep(step, index, total);
            },
            // Complete Callback
            () => {
                console.log("Animation Complete");
            }
        );

        // 3. Populate UI
        App.updateAlgoList();

        // 4. Set Initial Empty State Mode
        const appContainer = document.getElementById('app-container');
        if (appContainer) {
            appContainer.classList.add('empty-mode');
        }

        // 5. Bind Events
        App.bindEvents();

        console.log("AlgoViz ready.");
    },

    // Reveal active UI and hide empty state
    revealActiveUI: () => {
        const emptyState = document.getElementById('empty-state');
        const activeState = document.getElementById('active-state');
        const rightPanel = document.getElementById('right-panel');
        const appContainer = document.getElementById('app-container');

        if (emptyState) emptyState.style.display = 'none';
        if (activeState) activeState.classList.remove('hidden-initial');
        if (rightPanel) rightPanel.classList.remove('hidden-initial');
        if (appContainer) appContainer.classList.remove('empty-mode');
    },

    updateAlgoList: () => {
        const type = UI.elements.inputType.value;
        const algos = AlgorithmRegistry.getByType(type);
        UI.populateAlgoList(algos);

        if (algos.length > 0) {
            UI.loadAlgorithm(algos[0]);
            Engine.reset();
        } else {
            UI.elements.algoSelect.innerHTML = '';
            UI.elements.codeDisplay.innerHTML = '';
            UI.elements.algoOptions.innerHTML = '';
        }
    },

    bindEvents: () => {
        // Data Type Change
        UI.elements.inputType.addEventListener('change', () => {
            App.updateAlgoList();
        });

        // Algorithm Selection
        UI.elements.algoSelect.addEventListener('change', (e) => {
            const algoId = e.target.value;
            const algo = AlgorithmRegistry.get(algoId);
            if (algo) {
                // Reset Engine and clear steps
                Engine.pause();
                Engine.steps = [];
                Engine.currentStepIndex = 0;

                // Clear visualization area
                UI.elements.vizCanvas.innerHTML = '<div class="placeholder-msg">Select an algorithm and click Simulate</div>';

                // Reset status bar
                UI.elements.statusStep.textContent = 'Step: 0 / 0';
                UI.elements.statusMsg.textContent = 'Ready';

                // Clear any previous cycle state and info banner
                UI.state.lastCycle = null;
                UI.hideInfo();

                // Load new algorithm (updates code and complexity)
                UI.loadAlgorithm(algo);
            }
        });

        // Simulate Button
        UI.elements.btnSimulate.addEventListener('click', App.runSimulation);

        // Playback Controls
        document.getElementById('btn-play-pause').addEventListener('click', () => {
            Engine.toggle();
            // Update icon could be done in UI module via a state listener, 
            // but for now simple toggle logic:
            // ( Ideally UI should react to Engine state )
        });

        document.getElementById('btn-step-next').addEventListener('click', Engine.stepNext);
        document.getElementById('btn-step-prev').addEventListener('click', Engine.stepPrev);

        document.getElementById('btn-reset-view').addEventListener('click', () => {
            Engine.reset();
        });

        document.getElementById('speed-control').addEventListener('input', (e) => {
            Engine.setSpeed(parseInt(e.target.value));
        });

        // Reset All Input
        document.getElementById('btn-reset-all').addEventListener('click', () => {
            UI.elements.inputData.value = '';
            UI.elements.inputError.textContent = '';
            Engine.reset();
            UI.elements.vizCanvas.innerHTML = '<div class="placeholder-msg">Select an algorithm and click Simulate</div><button id="btn-scroll-top" class="scroll-top-btn" title="Scroll to Top" style="display: none;"><i class="fa-solid fa-arrow-up"></i></button>';
        });

        // Scroll to Top Button
        const vizCanvas = document.getElementById('viz-canvas');
        const scrollTopBtn = document.getElementById('btn-scroll-top');

        if (vizCanvas && scrollTopBtn) {
            // Show/hide button based on scroll position
            vizCanvas.addEventListener('scroll', () => {
                if (vizCanvas.scrollTop > 200) {
                    scrollTopBtn.style.display = 'flex';
                } else {
                    scrollTopBtn.style.display = 'none';
                }
            });

            // Scroll to top when clicked
            scrollTopBtn.addEventListener('click', () => {
                vizCanvas.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Index Toggle
        document.getElementById('btn-show-index').addEventListener('click', (e) => {
            e.target.classList.toggle('active');
            UI.elements.vizCanvas.classList.toggle('hide-indexes');
        });

        // Text Size Toggle (cycle through 3 sizes)
        let textSizeLevel = 1; // 1 = normal, 2 = large, 3 = x-large
        document.getElementById('btn-text-size').addEventListener('click', (e) => {
            textSizeLevel = (textSizeLevel % 3) + 1;
            const canvas = UI.elements.vizCanvas;
            canvas.classList.remove('text-size-normal', 'text-size-large', 'text-size-xlarge');
            if (textSizeLevel === 1) {
                canvas.classList.add('text-size-normal');
                e.target.textContent = 'Aa';
            } else if (textSizeLevel === 2) {
                canvas.classList.add('text-size-large');
                e.target.textContent = 'AA';
            } else {
                canvas.classList.add('text-size-xlarge');
                e.target.textContent = 'AA+';
            }
        });

        // Fullscreen Toggle
        document.getElementById('btn-fullscreen').addEventListener('click', () => {
            const mainPlayground = document.getElementById('main-playground');
            if (!document.fullscreenElement) {
                mainPlayground.requestFullscreen().catch(err => {
                    console.error('Fullscreen failed:', err);
                });
            } else {
                document.exitFullscreen();
            }
        });

        // Export GIF - Captures visualization and creates animated GIF
        document.getElementById('btn-export-gif').addEventListener('click', async () => {
            const steps = Engine.state.steps;
            const currentIndex = Engine.state.currentIndex;

            if (!steps || steps.length === 0) {
                alert("Please run a simulation first before exporting GIF.");
                return;
            }

            // Show progress
            const btn = document.getElementById('btn-export-gif');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '⏳';
            btn.disabled = true;
            btn.title = 'Creating GIF...';

            try {
                // Check if html2canvas is loaded
                if (typeof html2canvas === 'undefined') {
                    throw new Error("html2canvas library not loaded. Please refresh the page.");
                }

                // Check if GIF library is loaded
                if (typeof GIF === 'undefined') {
                    throw new Error("GIF library not loaded. Please refresh the page.");
                }

                const vizCanvas = UI.elements.vizCanvas;
                const originalContent = vizCanvas.innerHTML;
                const maxFrames = Math.min(steps.length, 20); // Limit frames

                // Create GIF encoder
                const gif = new GIF({
                    workers: 2,
                    quality: 10,
                    workerScript: 'https://cdn.jsdelivr.net/npm/gif.js@0.2.0/dist/gif.worker.js'
                });

                // Add progress feedback
                let framesAdded = 0;

                for (let i = 0; i < maxFrames; i++) {
                    vizCanvas.innerHTML = '';
                    UI.state.lastCycle = null;
                    UI.renderStep(steps[i], i, steps.length);

                    // Wait for DOM to settle
                    await new Promise(r => setTimeout(r, 50));

                    // Capture frame
                    const canvas = await html2canvas(vizCanvas, {
                        backgroundColor: '#0f172a',
                        scale: 0.5,
                        logging: false
                    });

                    gif.addFrame(canvas, { delay: 600, copy: true });
                    framesAdded++;
                    btn.title = `Processing frame ${framesAdded}/${maxFrames}...`;
                }

                // Restore original visualization
                vizCanvas.innerHTML = originalContent;

                // Handle completion
                gif.on('finished', (blob) => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `algoviz_${UI.state.currentAlgo?.metadata?.id || 'export'}_${Date.now()}.gif`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    btn.title = 'Export as GIF';
                    alert('GIF exported successfully!');
                });

                gif.on('progress', (p) => {
                    btn.title = `Rendering: ${Math.round(p * 100)}%`;
                });

                // Start rendering
                gif.render();

            } catch (err) {
                console.error('GIF Export Error:', err);
                alert('GIF Export Failed: ' + err.message);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                btn.title = 'Export as GIF';
            }
        });

        // Export PDF
        document.getElementById('btn-export-pdf').addEventListener('click', () => {
            // Simple implementation using browser print
            const printContent = UI.elements.vizCanvas.innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>AlgoViz Export</title>
                    <style>
                        body { font-family: Inter, sans-serif; padding: 20px; }
                        .viz-node { display: inline-flex; width: 50px; height: 50px; border: 2px solid #333; 
                                   border-radius: 6px; align-items: center; justify-content: center; margin: 5px; }
                        .step-wrapper { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
                        .cycle-header { font-weight: bold; font-size: 1.2rem; margin: 20px 0 10px; color: #3b82f6; }
                        .step-label { font-size: 0.8rem; color: #666; margin-bottom: 8px; }
                        .active { background: #3b82f6 !important; color: white; }
                        .compare { background: #eab308 !important; }
                        .swap { background: #ef4444 !important; color: white; }
                        .sorted { background: #22c55e !important; color: white; }
                    </style>
                </head>
                <body>
                    <h1>AlgoViz - Algorithm Visualization</h1>
                    <p>Algorithm: ${UI.state.currentAlgo?.metadata?.name || 'Unknown'}</p>
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    <hr>
                    ${printContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        });
    },

    runSimulation: () => {
        // Reveal active UI (hide empty state)
        App.revealActiveUI();

        const algo = UI.state.currentAlgo;
        if (!algo) return;

        // 1. Get Input
        const rawInput = UI.elements.inputData.value;
        const type = UI.elements.inputType.value;
        const params = UI.getParams();
        params.recursion = document.getElementById('recursion-toggle').checked;

        try {
            // 2. Validate
            UI.setError('');
            const data = algo.validate(rawInput, type);

            // 3. Generate Steps
            // Cast params if needed (target handling)
            // For Linear Search, target is typed.
            // Cast params if needed (target handling)
            if (algo.metadata.id === 'linear-search' || algo.metadata.id === 'binary-search') {
                // Check if target is empty
                if (params.target === undefined || params.target === null || params.target.toString().trim() === '') {
                    throw new Error("Please enter a Search Value");
                }
                if (type === 'numbers') {
                    params.target = Number(params.target);
                    if (isNaN(params.target)) throw new Error("Search Value must be a number");
                }
            }

            // Special Handler for Binary Search (Auto-Sort)
            if (algo.metadata.id === 'binary-search') {
                if (algo.isSorted && !algo.isSorted(data)) {
                    // Auto-sort the data without asking
                    data.sort((a, b) => a - b);
                    UI.elements.inputData.value = data.join(', '); // Update input field

                    // Show info message that data was sorted
                    UI.showInfo('📋 Input was automatically sorted. Binary Search requires sorted data.');
                }
            }

            const steps = algo.generateSteps(data, params, UI.state.loopType);

            // 4. Load Engine
            if (steps.length === 0) {
                UI.setError("No steps generated. Check input.");
                return;
            }

            console.log(`Generated ${steps.length} steps.`);
            Engine.load(steps);
            Engine.play();

        } catch (err) {
            console.error(err);
            UI.setError(err.message);
        }
    }
};

document.addEventListener('DOMContentLoaded', App.init);
