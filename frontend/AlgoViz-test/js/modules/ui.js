/**
 * AlgoViz UI Controller
 * Handles DOM rendering and events.
 */

export const UI = {
    elements: {
        vizCanvas: document.getElementById('viz-canvas'),
        codeDisplay: document.getElementById('code-display'),
        statusStep: document.getElementById('step-counter'),
        statusMsg: document.getElementById('status-message'),
        algoSelect: document.getElementById('algorithm-select'),
        inputData: document.getElementById('input-data'),
        inputError: document.getElementById('input-error'),
        inputType: document.getElementById('data-type'),
        loopToggles: document.querySelectorAll('.toggle-btn'),
        btnSimulate: document.getElementById('btn-simulate'),
        statTimeAvg: document.getElementById('stat-time-avg'),
        statTimeWorst: document.getElementById('stat-time-worst'),
        statSpace: document.getElementById('stat-space'),
        statStable: document.getElementById('stat-stable'),
        statNote: document.getElementById('stat-note'),
        algoOptions: document.getElementById('algo-options'),
    },

    state: {
        currentAlgo: null,
        loopType: 'for',
        lastCycle: null
    },

    init: () => {
        // Toggle Buttons Logic
        UI.elements.loopToggles.forEach(btn => {
            btn.addEventListener('click', (e) => {
                UI.elements.loopToggles.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                UI.state.loopType = e.target.getAttribute('data-value');
                // Refresh code view if algo selected
                if (UI.state.currentAlgo) {
                    const template = UI.state.currentAlgo.codeTemplate?.python?.[UI.state.loopType] || '';
                    UI.renderCode(template);
                }
            });
        });
    },

    populateAlgoList: (algos) => {
        const select = UI.elements.algoSelect;
        select.innerHTML = '';
        algos.forEach(algo => {
            const opt = document.createElement('option');
            opt.value = algo.metadata.id;
            opt.textContent = algo.metadata.name;
            select.appendChild(opt);
        });
    },

    renderOptions: (algo) => {
        const container = UI.elements.algoOptions;
        container.innerHTML = ''; // Clear previous

        if (!algo || !algo.metadata.params) return;

        algo.metadata.params.forEach(param => {
            const div = document.createElement('div');
            div.className = 'control-group';

            const label = document.createElement('label');
            label.textContent = param.label;
            div.appendChild(label);

            if (param.type === 'value') {
                const input = document.createElement('input');
                input.type = 'text';
                input.id = `param-${param.name}`;
                input.placeholder = "Enter value";
                div.appendChild(input);
            } else if (param.type === 'select') {
                const select = document.createElement('select');
                select.id = `param-${param.name}`;
                param.options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt;
                    option.textContent = opt.charAt(0).toUpperCase() + opt.slice(1);
                    select.appendChild(option);
                });
                div.appendChild(select);
            }

            container.appendChild(div);
        });
    },

    loadAlgorithm: (algo) => {
        UI.state.currentAlgo = algo;
        // Update Code - safely access template
        const template = algo.codeTemplate?.python?.[UI.state.loopType] || algo.codeTemplate?.python?.for || '';
        UI.renderCode(template);
        // Update Params UI
        UI.renderOptions(algo);

        // Toggle Recursion Option
        const recGroup = document.getElementById('recursion-group');
        if (algo.metadata.supportsRecursion) {
            recGroup.classList.remove('hidden');
        } else {
            recGroup.classList.add('hidden');
        }

        // Update Stats
        UI.elements.statTimeAvg.textContent = algo.complexity.timeAvg;
        UI.elements.statTimeWorst.textContent = algo.complexity.timeWorst;
        UI.elements.statSpace.textContent = algo.complexity.space;
        UI.elements.statStable.textContent = algo.complexity.stable;
        UI.elements.statNote.textContent = algo.complexity.notes;
    },

    renderCode: (code) => {
        // Split code into lines for highlighting support
        const codeLines = code.split('\n');
        const html = codeLines.map((line, idx) => {
            const lineNum = idx + 1;
            const highlightedLine = UI.highlightPythonSyntax(line);
            return `<div class="code-line" id="code-line-${lineNum}"><span class="line-num">${lineNum}</span><span class="line-text">${highlightedLine}</span></div>`;
        }).join('');
        UI.elements.codeDisplay.innerHTML = html;
    },

    highlightPythonSyntax: (line) => {
        // Python keywords and functions
        const keywords = new Set(['def', 'if', 'elif', 'else', 'for', 'while', 'return', 'in', 'and', 'or', 'not', 'True', 'False', 'None', 'break', 'continue', 'pass', 'import', 'from', 'as', 'class', 'try', 'except', 'finally', 'with', 'lambda', 'yield']);
        const functions = new Set(['print', 'input', 'int', 'str', 'float', 'list', 'dict', 'set', 'tuple', 'len', 'range', 'append', 'extend', 'insert', 'remove', 'pop', 'sort', 'sorted', 'reverse', 'split', 'join', 'replace', 'find', 'index', 'count']);

        // Tokenize and highlight
        let result = '';
        let i = 0;

        while (i < line.length) {
            const char = line[i];

            // Handle strings
            if (char === '"' || char === "'") {
                const quote = char;
                let str = char;
                i++;
                while (i < line.length && line[i] !== quote) {
                    if (line[i] === '\\' && i + 1 < line.length) {
                        str += line[i] + line[i + 1];
                        i += 2;
                    } else {
                        str += line[i];
                        i++;
                    }
                }
                if (i < line.length) {
                    str += line[i];
                    i++;
                }
                result += `<span class="string">${UI.escapeHtml(str)}</span>`;
                continue;
            }

            // Handle comments
            if (char === '#') {
                const comment = line.slice(i);
                result += `<span class="comment">${UI.escapeHtml(comment)}</span>`;
                break;
            }

            // Handle identifiers/keywords
            if (/[a-zA-Z_]/.test(char)) {
                let word = '';
                while (i < line.length && /[a-zA-Z0-9_]/.test(line[i])) {
                    word += line[i];
                    i++;
                }

                if (keywords.has(word)) {
                    result += `<span class="keyword">${word}</span>`;
                } else if (functions.has(word)) {
                    result += `<span class="function">${word}</span>`;
                } else {
                    result += word;
                }
                continue;
            }

            // Handle numbers
            if (/\d/.test(char)) {
                let num = '';
                while (i < line.length && /\d/.test(line[i])) {
                    num += line[i];
                    i++;
                }
                result += `<span class="number">${num}</span>`;
                continue;
            }

            // Handle operators
            if (/[+\-*/%=<>!&|]/.test(char)) {
                let op = '';
                while (i < line.length && /[+\-*/%=<>!&|]/.test(line[i])) {
                    op += line[i];
                    i++;
                }
                result += `<span class="operator">${UI.escapeHtml(op)}</span>`;
                continue;
            }

            // Default: append as-is (escape HTML entities)
            result += UI.escapeHtml(char);
            i++;
        }

        return result;
    },

    escapeHtml: (text) => {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    },

    highlightCode: (lineNum) => {
        // Clear previous
        document.querySelectorAll('.code-line.active').forEach(el => el.classList.remove('active'));
        if (lineNum) {
            const el = document.getElementById(`code-line-${lineNum}`);
            if (el) el.classList.add('active');
        }
    },

    renderStep: (step, index, total) => {
        // 1. Text Status
        UI.elements.statusStep.textContent = `Step: ${index + 1} / ${total}`;
        UI.elements.statusMsg.textContent = step.message;

        // 2. Code Highlight
        UI.highlightCode(step.codeLine);

        // 3. Array Visualization with Cycle Support
        const container = UI.elements.vizCanvas;

        // Always clear on first step OR if starting new simulation
        if (index === 0) {
            container.innerHTML = ''; // Clear placeholder and any previous content
            UI.state.lastCycle = null;
        }

        if (!step.arr) {
            return;
        }

        // Check if cycle data exists
        const hasCycleData = step.cycle !== undefined && step.cycle !== null;

        // Check if this is a new cycle or first step
        const isNewCycle = hasCycleData && (index === 0 || step.cycle !== UI.state.lastCycle);

        if (isNewCycle) {
            UI.state.lastCycle = step.cycle;

            // Create cycle header (skip for cycle 0 which is just initialization)
            if (step.cycle > 0) {
                const cycleHeader = document.createElement('div');
                cycleHeader.className = 'cycle-header';
                cycleHeader.innerHTML = `<span class="cycle-label">Cycle ${step.cycle}</span>`;
                container.appendChild(cycleHeader);
            }

            // Create cycle container for this cycle's steps
            const cycleContainer = document.createElement('div');
            cycleContainer.className = 'cycle-container';
            cycleContainer.id = `cycle-${step.cycle}`;
            container.appendChild(cycleContainer);
        }

        // Get or create the current cycle container
        let currentCycleContainer = container;
        if (hasCycleData) {
            currentCycleContainer = document.getElementById(`cycle-${step.cycle}`) || container;
        }

        // Create step wrapper
        const stepWrapper = document.createElement('div');
        stepWrapper.className = 'step-wrapper';

        // Show step label - use stepInCycle if available
        const stepLabel = hasCycleData && step.stepInCycle
            ? `Step ${step.stepInCycle}`
            : `Step ${index + 1}`;
        stepWrapper.innerHTML = `<div class="step-label">${stepLabel}</div>`;

        // Create array visualization
        const arrayWrapper = document.createElement('div');
        arrayWrapper.className = 'viz-array-wrapper';

        step.arr.forEach((val, idx) => {
            const node = document.createElement('div');
            node.className = 'viz-node';

            // Check highlights
            const highlight = step.highlights.find(h => h.index === idx);
            if (highlight) {
                node.classList.add(highlight.type);
            }

            const valDiv = document.createElement('div');
            valDiv.className = 'node-value';
            valDiv.textContent = val;

            const idxDiv = document.createElement('div');
            idxDiv.className = 'node-index';
            idxDiv.textContent = idx;

            node.appendChild(valDiv);
            node.appendChild(idxDiv);

            // Check pointers
            Object.keys(step.pointers).forEach(ptrKey => {
                if (step.pointers[ptrKey] === idx) {
                    const ptrLabel = document.createElement('div');
                    ptrLabel.className = 'ptr-label';
                    ptrLabel.textContent = ptrKey;
                    ptrLabel.style.color = 'var(--text-accent)';
                    ptrLabel.style.fontSize = '0.75rem';
                    ptrLabel.style.fontWeight = 'bold';
                    node.appendChild(ptrLabel);
                }
            });

            arrayWrapper.appendChild(node);
        });

        stepWrapper.appendChild(arrayWrapper);

        // Add message for this step
        const stepMsg = document.createElement('div');
        stepMsg.className = 'step-message';
        stepMsg.textContent = step.message;
        stepWrapper.appendChild(stepMsg);

        currentCycleContainer.appendChild(stepWrapper);

        // Auto-scroll to show the current step (smooth scroll into view)
        stepWrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    },

    setError: (msg) => {
        UI.elements.inputError.textContent = msg;
    },

    showInfo: (msg) => {
        // Show info message in the dedicated info banner
        const banner = document.getElementById('info-banner');
        const bannerText = document.getElementById('info-banner-text');

        if (banner && bannerText) {
            bannerText.textContent = msg;
            banner.style.display = 'block';

            // Auto-hide after 8 seconds
            setTimeout(() => {
                banner.style.display = 'none';
            }, 8000);
        }
    },

    hideInfo: () => {
        const banner = document.getElementById('info-banner');
        if (banner) {
            banner.style.display = 'none';
        }
    },

    getParams: () => {
        // Dynamic param fetch
        const params = {};
        const container = UI.elements.algoOptions;
        const inputs = container.querySelectorAll('input, select');
        inputs.forEach(input => {
            const name = input.id.replace('param-', '');
            params[name] = input.value;
        });
        return params;
    }
};
