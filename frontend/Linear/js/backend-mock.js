
(function () {
    console.log("%c VDraw In-Browser Backend Initialized ", "background: #4f46e5; color: white; padding: 4px; border-radius: 4px;");

    // ==========================================
    // STATE STORAGE
    // ==========================================
    const DB = {
        tree: {
            sessions: { "default": { root_id: null, nodes: {}, stats: null } },
            configs: { "default": { type: 'bst', root_value: 'Root' } }
        },
        graph: {
            sessions: { "default": { nodes: {}, adjacency_list: {}, config: { directed: true, weighted: false }, stats: null } }
        }
    };

    function uuidv4() {
        return 'xxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // ==========================================
    // STATS ENGINE
    // ==========================================
    const StatsEngine = {
        calculate: function (payload) {
            const originalData = [...payload.data]; // Keep original order for Mean display
            const data = payload.data.sort((a, b) => a - b);
            const n = data.length;
            if (n === 0) throw new Error("No data");

            // Basic
            const sum = data.reduce((a, b) => a + b, 0);
            const mean = sum / n;
            const mid = Math.floor(n / 2);
            const median = n % 2 !== 0 ? data[mid] : (data[mid - 1] + data[mid]) / 2;
            const min = data[0];
            const max = data[n - 1];
            const range = max - min;

            // Mode
            const freq = {};
            let maxFreq = 0;
            data.forEach(v => { freq[v] = (freq[v] || 0) + 1; if (freq[v] > maxFreq) maxFreq = freq[v]; });
            const mode = Object.keys(freq).filter(k => freq[k] === maxFreq && maxFreq > 1).map(Number);

            // Variance
            const variance = data.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / (payload.is_sample ? n - 1 : n);
            const std_dev = Math.sqrt(variance);

            // Quartiles
            // Quartiles (Interpolated)
            const getQuartile = (p, method) => {
                let idx;
                if (method === 'inclusive') idx = p * (n - 1);
                else idx = p * (n + 1) - 1; // exclusive

                if (idx < 0) return data[0];
                if (idx >= n - 1) return data[n - 1];

                const lower = Math.floor(idx);
                const upper = Math.ceil(idx);
                const weight = idx - lower;
                return data[lower] * (1 - weight) + data[upper] * weight;
            };

            const qMethod = (payload.quartile_method || 'exclusive').toLowerCase();
            const q1 = getQuartile(0.25, qMethod);
            const q3 = getQuartile(0.75, qMethod);
            const iqr = q3 - q1;

            // Outliers
            const low = q1 - 1.5 * iqr;
            const high = q3 + 1.5 * iqr;
            const outliers = data.filter(x => x < low || x > high);

            // Regression
            let regression = null;
            if (payload.regression_type && payload.x_data && payload.y_data) {
                const X = payload.x_data;
                const Y = payload.y_data;
                if (X.length === Y.length && X.length > 1) {
                    if (payload.regression_type === 'linear') {
                        regression = this.linearRegression(X, Y);
                    } else if (payload.regression_type === 'logistic') {
                        regression = this.logisticRegression(X, Y);
                    }
                }
            }

            // --- EXPLANATIONS GENERATION ---
            const explanations = [];

            // Helper for python-like float format
            const pyFloat = (num) => Number.isInteger(num) ? `${num}.0` : String(num);

            // 1. Mean
            const sumStr = originalData.slice(0, 5).map(pyFloat).join(' + ') + (n > 5 ? ' + ...' : '');
            explanations.push({
                title: "Mean Calculation",
                result: mean.toFixed(4),
                steps: [
                    { text: "1. Sum all the values (Σx).", latex: "\\sum x" },
                    { text: `   ${sumStr} = ${sum.toFixed(1)}`, latex: "" }, // Python uses .0 for sum often if floats
                    { text: "2. Count the number of values (N).", latex: "N" },
                    { text: `   N = ${n}`, latex: "" },
                    { text: "3. Divide the sum by N.", latex: "\\bar{x} = \\frac{\\sum x}{N}" },
                    { text: `   ${sum.toFixed(1)} / ${n} = ${mean.toFixed(4)}`, latex: `${sum.toFixed(1)} / ${n}` }
                ]
            });

            // 2. Median
            const sortedStr = data.slice(0, 10).join(', ') + (n > 10 ? ", ..." : "");
            const medSteps = [{ text: "1. Sort the data in ascending order.", latex: "" }, { text: `   ${sortedStr}`, latex: "" }];
            if (n % 2 !== 0) {
                medSteps.push({ text: `2. Since N (${n}) is odd, the median is the middle value at position (N+1)/2.`, latex: "\\frac{N+1}{2}" });
                medSteps.push({ text: `   Position ${mid + 1}: ${median}`, latex: "" });
            } else {
                const v1 = data[mid - 1], v2 = data[mid];
                medSteps.push({ text: `2. Since N (${n}) is even, the median is the average of the two middle values.`, latex: "" });
                medSteps.push({ text: `   Values at positions ${mid} and ${mid + 1} are ${v1} and ${v2}.`, latex: "" });
                medSteps.push({ text: `   (${v1} + ${v2}) / 2 = ${median}`, latex: "\\frac{x_{n/2} + x_{n/2+1}}{2}" });
            }
            explanations.push({ title: "Median Calculation", result: String(median), steps: medSteps });

            // 3. Mode
            // ... (Skipping verbose mode steps for brevity, usually simple)

            // 4. Variance
            const varSteps = [{ text: `1. Calculate the mean (x̄).`, latex: "\\bar{x}" }, { text: `   x̄ = ${mean.toFixed(2)}`, latex: "" }];
            const diffs = data.slice(0, 3).map(x => `(${x} - ${mean.toFixed(2)})`).join(', ');
            varSteps.push({ text: "2. Calculate squared deviations from the mean.", latex: "(x - \\bar{x})^2" });
            varSteps.push({ text: `   e.g., ${diffs} ...`, latex: "" });
            const sumSq = data.reduce((a, b) => a + Math.pow(b - mean, 2), 0);
            varSteps.push({ text: "3. Sum all squared deviations.", latex: "\\sum (x - \\bar{x})^2" });
            varSteps.push({ text: `   Sum = ${sumSq.toFixed(2)}`, latex: "" });
            if (payload.is_sample) {
                varSteps.push({ text: "4. Divide by (n - 1) for sample variance.", latex: "s^2 = \\frac{\\sum (x - \\bar{x})^2}{n - 1}" });
                varSteps.push({ text: `   ${sumSq.toFixed(2)} / ${n - 1} = ${variance.toFixed(4)}`, latex: "" });
                explanations.push({ title: "Sample Variance", result: variance.toFixed(4), steps: varSteps });
            } else {
                varSteps.push({ text: "4. Divide by N for population variance.", latex: "\\sigma^2 = \\frac{\\sum (x - \\mu)^2}{N}" });
                varSteps.push({ text: `   ${sumSq.toFixed(2)} / ${n} = ${variance.toFixed(4)}`, latex: "" });
                explanations.push({ title: "Population Variance", result: variance.toFixed(4), steps: varSteps });
            }

            // 5. Standard Deviation
            const sym = payload.is_sample ? 's' : '\\sigma';
            explanations.push({
                title: "Standard Deviation",
                result: std_dev.toFixed(4),
                steps: [
                    { text: "1. Start with the calculated variance.", latex: `${sym}^2 = ${variance.toFixed(4)}` },
                    { text: "2. Take the square root.", latex: `${sym} = \\sqrt{${sym}^2}` },
                    { text: `   √${variance.toFixed(4)} = ${std_dev.toFixed(4)}`, latex: "" }
                ]
            });

            // 6. Quartiles
            const qSteps = [{ text: `1. Sort data (N=${n})`, latex: "" }];
            if (payload.quartile_method !== 'inclusive') {
                qSteps.push({ text: "Method: Exclusive (Excel PERCENTILE.EXC)", latex: "" });
                qSteps.push({ text: `Q1 Index = 0.25 * (${n}+1) - 1`, latex: "" });
                qSteps.push({ text: `Q3 Index = 0.75 * (${n}+1) - 1`, latex: "" });
            } else {
                qSteps.push({ text: "Method: Inclusive (Excel PERCENTILE.INC)", latex: "" });
                qSteps.push({ text: `Q1 Index = 0.25 * (${n}-1)`, latex: "" });
            }
            qSteps.push({ text: "Interpolate values at calculated indices.", latex: "" });
            explanations.push({ title: `Quartiles (${(payload.quartile_method || 'inclusive').toLowerCase()})`, result: `Q1=${q1.toFixed(2)}, Q3=${q3.toFixed(2)}`, steps: qSteps });

            // 7. IQR
            explanations.push({
                title: "Interquartile Range (IQR)",
                result: iqr.toFixed(2),
                steps: [
                    { text: "1. Subtract Q1 from Q3.", latex: "IQR = Q3 - Q1" },
                    { text: `   ${q3.toFixed(2)} - ${q1.toFixed(2)} = ${iqr.toFixed(2)}`, latex: "" }
                ]
            });

            // 8. Regression (if exists)
            if (regression) {
                if (payload.regression_type === 'linear') {
                    explanations.push({
                        title: "Linear Regression",
                        result: regression.formula,
                        steps: [
                            { text: "1. Fit line to minimize squared errors.", latex: "y = mx + b" },
                            { text: `2. Slope (m) = ${regression.slope.toFixed(4)}`, latex: "" },
                            { text: `3. Intercept (b) = ${regression.intercept.toFixed(4)}`, latex: "" },
                            { text: `4. Equation: ${regression.formula}`, latex: "" },
                            { text: `5. R^2: ${regression.r2_score.toFixed(4)}`, latex: "" }
                        ]
                    });
                } else if (payload.regression_type === 'logistic') {
                    explanations.push({
                        title: "Logistic Regression",
                        result: `Acc: ${(regression.accuracy * 100).toFixed(1)}%`,
                        steps: [
                            { text: "1. Model probability of class membership.", latex: "P(y=1) = \\sigma(mx+b)" },
                            { text: `2. Accuracy: ${(regression.accuracy * 100).toFixed(2)}%`, latex: "" },
                            { text: "3. Binary Model Parameters estimated via Gradient Descent.", latex: "" }
                        ]
                    });
                }
            }

            return {
                mean, median, mode, variance, std_dev, range,
                quartiles: { q1, q3 }, iqr, outliers,
                explanations,
                regression
            };
        },

        linearRegression: function (x, y) {
            const n = x.length;
            const sumX = x.reduce((a, b) => a + b, 0); const sumY = y.reduce((a, b) => a + b, 0);
            const sumXY = x.reduce((a, b, i) => a + b * y[i], 0); const sumXX = x.reduce((a, b) => a + b * b, 0);
            const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
            const intercept = (sumY - slope * sumX) / n;
            const yMean = sumY / n;
            const ssTot = y.reduce((a, b) => a + Math.pow(b - yMean, 2), 0);
            const ssRes = y.reduce((a, b, i) => a + Math.pow(b - (slope * x[i] + intercept), 2), 0);
            return { slope, intercept, r2_score: 1 - (ssRes / ssTot), formula: `y = ${slope.toFixed(4)}x + ${intercept.toFixed(4)}` };
        },

        logisticRegression: function (x, y) {
            // 1. Label Encoding (Handling Categorical Targets)
            const uniqueClasses = [...new Set(y)].sort();
            const classMap = {};
            uniqueClasses.forEach((c, i) => classMap[c] = i);
            const yEncoded = y.map(v => classMap[v]);

            // 2. Standardization (Z-Score) - Better for LR than MinMax
            const mean = x.reduce((a, b) => a + b, 0) / x.length;
            const variance = x.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / x.length;
            const std = Math.sqrt(variance) || 1;
            const xNorm = x.map(v => (v - mean) / std);

            // Helper: Sigmoid
            const sigmoid = (z) => 1 / (1 + Math.exp(-z));

            // Helper: Train Binary Model (Class k vs Rest) - Tuned for matching sklearn basic performance
            const trainBinary = (targetClassIdx) => {
                let m = 0, b = 0, lr = 0.05; // Tuned LR
                const epochs = 50000;
                for (let i = 0; i < epochs; i++) {
                    // Stochastic Gradient Descent
                    for (let j = 0; j < xNorm.length; j++) {
                        const yTrue = yEncoded[j] === targetClassIdx ? 1 : 0;
                        const z = m * xNorm[j] + b;
                        const pred = sigmoid(z);
                        const err = pred - yTrue;
                        m -= lr * err * xNorm[j];
                        b -= lr * err;
                    }
                }
                return { m, b };
            };

            let correct = 0;
            let models = [];

            if (uniqueClasses.length === 2) {
                // Binary
                const model = trainBinary(1);
                xNorm.forEach((xv, i) => {
                    const prob = sigmoid(model.m * xv + model.b);
                    if ((prob >= 0.5 ? 1 : 0) === yEncoded[i]) correct++;
                });

                // Denormalize
                const realM = model.m / std;
                const realB = model.b - (model.m * mean / std);

                regressionResult = {
                    accuracy: correct / x.length,
                    classes: uniqueClasses.map(String),
                    coefficients: [realM],
                    intercept: realB,
                    formula: `p(y=1) = sigmoid(${realM.toFixed(4)}x + ${realB.toFixed(4)})`
                };

            } else {
                // Multiclass
                uniqueClasses.forEach((_, idx) => models.push(trainBinary(idx)));
                xNorm.forEach((xv, i) => {
                    let bestIdx = 0, bestP = -1;
                    models.forEach((m, idx) => {
                        const p = sigmoid(m.m * xv + m.b);
                        if (p > bestP) { bestP = p; bestIdx = idx; }
                    });
                    if (bestIdx === yEncoded[i]) correct++;
                });

                const realCoeffs = models.map(mod => mod.m / std);
                const realIntercepts = models.map(mod => mod.b - (mod.m * mean / std));

                regressionResult = {
                    accuracy: correct / x.length,
                    classes: uniqueClasses.map(String),
                    coefficients: realCoeffs,
                    intercept: realIntercepts,
                    formula: `Classes: [${uniqueClasses.join(', ')}]`
                };
            }

            // Explanation (Match Python Exact Text)
            regressionResult.explanation = [];
            regressionResult.explanation.push({ text: "1. Logistic regression models the probability of class membership.", latex: uniqueClasses.length > 2 ? "P(y=k) = \\text{softmax}(z_k)" : "P(y=1) = \\sigma(mx+b)" });
            regressionResult.explanation.push({ text: `2. Model Accuracy on training data: ${(regressionResult.accuracy * 100).toFixed(2)}%`, latex: "" });

            if (uniqueClasses.length > 2) {
                regressionResult.explanation.push({ text: "3. Multiclass Model (One-vs-Rest or Multinomial).", latex: "" });
                regressionResult.explanation.push({ text: `   Number of classes: ${uniqueClasses.length}`, latex: "" });
                regressionResult.explanation.push({ text: "   Coefficients are calculated for each class relative to others.", latex: "" });
            } else {
                regressionResult.explanation.push({ text: "3. Binary Model Parameters estimated via Gradient Descent.", latex: "" });
            }

            return regressionResult;
        }
    };

    // ==========================================
    // TGDRAW ENGINE
    // ==========================================
    class TreeEngine {
        constructor(sid = 'default') { this.sid = sid; this.state = DB.tree.sessions[sid]; this.config = DB.tree.configs[sid]; }
        reset(config) {
            this.state.root_id = null; this.state.nodes = {}; DB.tree.configs[this.sid] = config; this.config = config;
            const rootId = uuidv4().substring(0, 8);
            this.state.nodes[rootId] = { id: rootId, value: config.root_value, children: [], left: null, right: null };
            this.state.root_id = rootId; this.calculateStats();
            return this.state;
        }
        addNode(parentId, value, direction) {
            const parent = this.state.nodes[parentId]; if (!parent) throw new Error("Parent not found");
            const type = this.config.type;
            if (['binary', 'bst', 'avl'].includes(type) && !direction) throw new Error("Direction required");
            if (direction === 'left' && parent.left) throw new Error("Left child exists");
            if (direction === 'right' && parent.right) throw new Error("Right child exists");

            if (['bst', 'avl'].includes(type)) {
                let pVal = parseFloat(parent.value);
                let nVal = parseFloat(value);
                if (isNaN(pVal)) pVal = parent.value; if (isNaN(nVal)) nVal = value;
                const err = type === 'avl' ? "AVL Constraint" : "BST Rule Violation";
                if (direction === 'left' && !(nVal < pVal)) throw new Error(`${err}: ${nVal} must be < ${pVal}`);
                if (direction === 'right' && !(nVal > pVal)) throw new Error(`${err}: ${nVal} must be > ${pVal}`);
            }

            const newId = uuidv4().substring(0, 8);
            this.state.nodes[newId] = { id: newId, value: value, children: [], left: null, right: null };
            if (type === 'generic') parent.children.push(newId);
            else { if (direction === 'left') parent.left = newId; else parent.right = newId; parent.children = [parent.left, parent.right].filter(x => x); }

            this.calculateStats();
            if (type === 'avl' && !this.state.stats.is_balanced) {
                delete this.state.nodes[newId];
                if (type === 'generic') parent.children.pop(); else { if (direction === 'left') parent.left = null; else parent.right = null; parent.children = [parent.left, parent.right].filter(x => x); }
                this.calculateStats();
                throw new Error("AVL Violation: Tree imbalance (Height Diff > 1).");
            }
            return this.state;
        }
        calculateStats() {
            if (!this.state.root_id) return;
            let q = [{ id: this.state.root_id, level: 0 }], maxH = 0, leaves = [];
            while (q.length) {
                const { id, level } = q.shift(); const n = this.state.nodes[id];
                if (level > maxH) maxH = level;
                const children = (this.config.type === 'generic') ? n.children : [n.left, n.right].filter(x => x);
                if (!children.length) leaves.push(n.value);
                children.forEach(c => q.push({ id: c, level: level + 1 }));
            }
            const balanced = (nid) => {
                if (!nid) return [0, true];
                const n = this.state.nodes[nid];
                const [lh, lb] = balanced(n.left), [rh, rb] = balanced(n.right);
                return [1 + Math.max(lh, rh), lb && rb && Math.abs(lh - rh) <= 1];
            };
            this.state.stats = { total_nodes: Object.keys(this.state.nodes).length, height: maxH, leaf_count: leaves.length, leaves, is_balanced: ['binary', 'bst', 'avl'].includes(this.config.type) ? balanced(this.state.root_id)[1] : true };
        }
        traverse(type) {
            const steps = []; const n = this.state.nodes; const rid = this.state.root_id;
            const log = (id, act, desc) => steps.push({ node_id: id, action: act, description: desc });
            if (!rid) return [];
            const visit = (id) => log(id, 'visit', `Visit ${n[id].value}`);
            const explore = (id, msg) => log(id, 'explore', msg);

            if (type === 'bfs') {
                let q = [rid]; log(rid, 'enqueue', 'Start');
                while (q.length) { const c = q.shift(); visit(c); const kids = (this.config.type === 'generic') ? n[c].children : [n[c].left, n[c].right].filter(x => x); kids.forEach(k => { q.push(k); log(k, 'enqueue', 'Queue'); }); }
            } else if (type === 'preorder' || type === 'dfs') {
                const pre = (id) => { if (!id) return; visit(id); if (this.config.type === 'generic') n[id].children.forEach(c => { explore(c, 'Child'); pre(c); }); else { if (n[id].left) { explore(n[id].left, 'Left'); pre(n[id].left); } if (n[id].right) { explore(n[id].right, 'Right'); pre(n[id].right); } } }; pre(rid);
            } else if (type === 'postorder') {
                const post = (id) => { if (!id) return; if (this.config.type === 'generic') n[id].children.forEach(c => { explore(c, 'Child'); post(c) }); else { if (n[id].left) { explore(n[id].left, 'Left'); post(n[id].left); } if (n[id].right) { explore(n[id].right, 'Right'); post(n[id].right); } } visit(id); }; post(rid);
            } else if (type === 'inorder') {
                const ino = (id) => { if (!id) return; const nd = n[id]; if (nd.left) { explore(nd.left, 'Left'); ino(nd.left); } visit(id); if (nd.right) { explore(nd.right, 'Right'); ino(nd.right); } }; ino(rid);
            }
            return steps;
        }
    }

    class GraphEngine {
        constructor(sid = 'default') { this.sid = sid; this.state = DB.graph.sessions[sid]; }
        create(cfg) { this.state.nodes = {}; this.state.adjacency_list = {}; this.state.config = cfg; this.calc(); return this.state; }
        addNode(id, v) { if (this.state.nodes[id]) throw new Error("Exists"); this.state.nodes[id] = { id, value: v }; this.state.adjacency_list[id] = []; this.calc(); return this.state; }
        addEdge(s, t, w) {
            const adj = this.state.adjacency_list; if (adj[s].find(e => e.target === t)) throw new Error("Exists");
            adj[s].push({ target: t, weight: w }); if (!this.state.config.directed && !adj[t].find(e => e.target === s)) adj[t].push({ target: s, weight: w, bidirectional: true });
            this.calc(); return this.state;
        }
        removeEdge(s, t) {
            this.state.adjacency_list[s] = this.state.adjacency_list[s].filter(e => e.target !== t);
            if (!this.state.config.directed) this.state.adjacency_list[t] = this.state.adjacency_list[t].filter(e => e.target !== s);
            this.calc(); return this.state;
        }
        calc() {
            const n = Object.keys(this.state.nodes), adj = this.state.adjacency_list, d = this.state.config.directed, deg = {}; let eCount = 0;
            n.forEach(i => { const out = (adj[i] || []).length; let inc = 0; if (d) n.forEach(o => { if ((adj[o] || []).find(e => e.target == i)) inc++; }); else inc = out; deg[i] = { total: out + (d ? inc : 0), in: inc, out }; eCount += out; });
            this.state.stats = { total_vertices: n.length, total_edges: d ? eCount : eCount / 2, degrees: deg };
        }
        runAlgo(algo, start) {
            const steps = [], n = this.state.nodes, adj = this.state.adjacency_list; const log = (i, a, d) => steps.push({ node_id: i, action: a, description: d });
            if (algo === 'bfs') {
                const q = [start], vis = new Set([start]); log(start, 'enqueue', 'Begin');
                while (q.length) { const c = q.shift(); log(c, 'visit', 'Visit'); (adj[c] || []).forEach(e => { if (!vis.has(e.target)) { vis.add(e.target); q.push(e.target); log(e.target, 'enqueue', 'Queue'); } }); }
            } else if (algo === 'dfs') {
                const vis = new Set(); const dfs = (curr) => { vis.add(curr); log(curr, 'visit', 'Visit'); (adj[curr] || []).forEach(e => { if (!vis.has(e.target)) { log(e.target, 'explore', 'To'); dfs(e.target); } }); }; dfs(start);
            } else if (algo === 'dijkstra') {
                const dist = {}, pq = [{ id: start, d: 0 }], vis = new Set(); Object.keys(n).forEach(k => dist[k] = Infinity); dist[start] = 0; log(start, 'enqueue', 'Init');
                while (pq.length) { pq.sort((a, b) => a.d - b.d); const { id, d } = pq.shift(); if (vis.has(id)) continue; vis.add(id); log(id, 'visit', `Dist: ${d}`); (adj[id] || []).forEach(e => { const w = e.weight || 1; if (dist[id] + w < dist[e.target]) { dist[e.target] = dist[id] + w; pq.push({ id: e.target, d: dist[e.target] }); log(e.target, 'enqueue', `Update ${dist[e.target]}`); } }); }
            }
            return steps;
        }
    }

    // ==========================================
    // PDRAW ENGINE
    // ==========================================
    const PDRAW_CATALOG = {
        structures: [
            {
                id: "stack", label: "Stack (LIFO)", implementations: ["list", "collections.deque"], operations: [
                    { id: "push", label: "Push", params: [{ name: "value", type: "any" }], complexity: "O(1)" },
                    { id: "pop", label: "Pop", params: [], complexity: "O(1)" },
                    { id: "peek", label: "Peek", params: [], complexity: "O(1)" },
                    { id: "is_empty", label: "Is Empty?", params: [], complexity: "O(1)" }
                ]
            },
            {
                id: "queue", label: "Queue (FIFO)", implementations: ["list", "collections.deque", "queue.Queue"], operations: [
                    { id: "enqueue", label: "Enqueue", params: [{ name: "value", type: "any" }], complexity: "O(1)" },
                    { id: "dequeue", label: "Dequeue", params: [], complexity: "O(1)" },
                    { id: "front", label: "Front", params: [], complexity: "O(1)" },
                    { id: "rear", label: "Rear", params: [], complexity: "O(1)" }
                ]
            },
            {
                id: "list", label: "List (Array)", implementations: ["list"], operations: [
                    { id: "append", label: "Append", params: [{ name: "value", type: "any" }], complexity: "O(1)" },
                    { id: "extend", label: "Extend", params: [{ name: "iterable", type: "any" }], complexity: "O(k)" },
                    { id: "insert", label: "Insert", params: [{ name: "index", type: "int" }, { name: "value", type: "any" }], complexity: "O(n)" },
                    { id: "pop", label: "Pop Index", params: [{ name: "index", type: "int", required: false }], complexity: "O(k)" },
                    { id: "remove", label: "Remove Value", params: [{ name: "value", type: "any" }], complexity: "O(n)" },
                    { id: "clear", label: "Clear", params: [], complexity: "O(1)" },
                    { id: "index", label: "Index", params: [{ name: "value", type: "any" }], complexity: "O(n)" },
                    { id: "count", label: "Count", params: [{ name: "value", type: "any" }], complexity: "O(n)" },
                    { id: "sort", label: "Sort", params: [], complexity: "O(n log n)" },
                    { id: "reverse", label: "Reverse", params: [], complexity: "O(n)" },
                    { id: "slice", label: "Slice", params: [{ name: "start", type: "int" }, { name: "stop", type: "int" }, { name: "step", type: "int" }], complexity: "O(k)" }
                ]
            },
            {
                id: "tuple", label: "Tuple", implementations: ["tuple"], operations: [
                    { id: "index", label: "Index", params: [{ name: "value", type: "any" }], complexity: "O(n)" },
                    { id: "count", label: "Count", params: [{ name: "value", type: "any" }], complexity: "O(n)" },
                    { id: "len", label: "Length", params: [], complexity: "O(1)" },
                    { id: "slice", label: "Slice", params: [{ name: "start", type: "int" }, { name: "stop", type: "int" }, { name: "step", type: "int" }], complexity: "O(k)" }
                ]
            }
        ]
    };

    const PDrawSimulator = {
        simulate: function (req) {
            const stype = req.structure;
            const impl = req.implementation;
            // Deep copy logic
            let state = [...req.initial_values];

            // Diagram helper
            const getDiag = (s) => ({ type: stype, items: [...(Array.isArray(s) ? s : [])], front: 0, rear: s.length - 1 });

            // Format initial state Pythonically
            let initialPrint = "[";
            initialPrint += state.map(item => {
                if (typeof item === 'string') return `'${item}'`;
                return String(item);
            }).join(', ');
            initialPrint += "]";

            const initial = { print: initialPrint, diagram: getDiag(state) };
            const steps = [];

            let currentState = [...state]; // mimic list

            req.operations.forEach((op, idx) => {
                const opName = op.op;
                const args = op.args || {};
                const val = args.value;
                let printState = "";
                let expl = "";
                let error = null;
                let status = "ok";
                let complexity = "O(1)";

                try {
                    const style = t => `<span class="font-bold text-yellow-300">${t}</span>`;

                    if (stype === 'stack') {
                        if (opName === 'push') {
                            currentState.push(val);
                            expl = `${style(`append(${val})`)}. Pushed ${val} onto top.`;
                        }
                        else if (opName === 'pop') {
                            if (!currentState.length) throw "Empty";
                            const p = currentState.pop();
                            expl = `${style(`pop()`)}. Popped ${p} from top.`;
                        }
                        else if (opName === 'peek') {
                            if (!currentState.length) throw "Empty";
                            expl = `${style(`[-1]`)}. Top element: ${currentState[currentState.length - 1]}`;
                        }
                        else if (opName === 'is_empty') {
                            expl = `${style(`len() == 0`)}. Empty? ${currentState.length === 0}`;
                        }
                    }
                    else if (stype === 'queue') {
                        if (opName === 'enqueue') {
                            currentState.push(val);
                            expl = `${style(`append(${val})`)}. Enqueued ${val} at rear.`;
                        }
                        else if (opName === 'dequeue') {
                            if (!currentState.length) throw "Empty";
                            const p = currentState.shift();
                            expl = `${style(`pop(0)`)}. Dequeued ${p} from front.`;
                        }
                        else if (opName === 'front') {
                            if (!currentState.length) throw "Empty";
                            expl = `${style(`[0]`)}. Front element: ${currentState[0]}`;
                        }
                        else if (opName === 'rear') {
                            if (!currentState.length) throw "Empty";
                            expl = `${style(`[-1]`)}. Rear element: ${currentState[currentState.length - 1]}`;
                        }
                    }
                    else if (stype === 'list') {
                        if (opName === 'append') { currentState.push(val); expl = `${style(`append(${val})`)}. Appended ${val} to end.`; }
                        else if (opName === 'insert') { currentState.splice(args.index, 0, val); expl = `${style(`insert(${args.index}, ${val})`)}. Inserted at ${args.index}.`; complexity = "O(n)"; }
                        else if (opName === 'remove') {
                            const i = currentState.indexOf(val);
                            if (i === -1) throw "Not found"; currentState.splice(i, 1); expl = `${style(`remove(${val})`)}. Removed first occurrence of ${val}.`; complexity = "O(n)";
                        }
                        else if (opName === 'pop') {
                            if (args.index !== undefined) {
                                const p = currentState[args.index];
                                currentState.splice(args.index, 1);
                                expl = `${style(`pop(${args.index})`)}. Popped ${p} at index ${args.index}.`;
                                complexity = "O(n)";
                            }
                            else {
                                const p = currentState.pop();
                                expl = `${style(`pop()`)}. Popped ${p} from end.`;
                            }
                        }
                        else if (opName === 'index') { const i = currentState.indexOf(val); if (i === -1) throw "Not found"; expl = `${style(`index(${val})`)}. Found at index ${i}.`; complexity = "O(n)"; }
                        else if (opName === 'count') { const c = currentState.filter(x => x == val).length; expl = `${style(`count(${val})`)}. Found ${c} occurrences.`; complexity = "O(n)"; }
                        else if (opName === 'sort') { currentState.sort((a, b) => (typeof a === 'number' ? a - b : String(a).localeCompare(String(b)))); expl = `${style(`sort()`)}. List sorted in-place.`; complexity = "O(n log n)"; }
                        else if (opName === 'reverse') { currentState.reverse(); expl = `${style(`reverse()`)}. List reversed in-place.`; complexity = "O(n)"; }
                        else if (opName === 'slice') {
                            const start = args.start || 0; const stop = args.stop !== undefined ? args.stop : currentState.length; const step = args.step || 1;
                            const res = [];
                            for (let i = start; (step > 0 ? i < stop : i > stop); i += step) { if (currentState[i] !== undefined) res.push(currentState[i]); }
                            expl = `${style(`[${start}:${stop}:${step}]`)}. Sliced: ${JSON.stringify(res)}`;
                        }
                        else if (opName === 'extend') {
                            const it = Array.isArray(args.iterable) ? args.iterable : [args.iterable];
                            currentState.push(...it); expl = `${style(`extend(${JSON.stringify(it)})`)}. Extended list.`; complexity = "O(k)";
                        }
                        else if (opName === 'clear') { currentState = []; expl = `${style(`clear()`)}. List cleared.`; }
                    }
                    else if (stype === 'tuple') {
                        if (opName === 'index') { const i = currentState.indexOf(val); if (i === -1) throw "Not found"; expl = `${style(`index(${val})`)}. Found at index ${i}.`; complexity = "O(n)"; }
                        else if (opName === 'count') { const c = currentState.filter(x => x == val).length; expl = `${style(`count(${val})`)}. Found ${c} occurrences.`; complexity = "O(n)"; }
                        else if (opName === 'len') { expl = `${style(`len()`)}. Length: ${currentState.length}`; }
                        else if (opName === 'slice') { expl = "Sliced (Immutable result)."; }
                    }

                    // Pythonic Formatting: Spaces after commas, Single quotes for strings
                    printState = "[";
                    printState += currentState.map(item => {
                        if (typeof item === 'string') return `'${item}'`;
                        return String(item);
                    }).join(', ');
                    printState += "]";

                } catch (e) {
                    error = String(e);
                    status = "error";
                    expl = `Error: ${error}`;
                }

                // Simplified Operation Name (Just the Op Name, matching Python backend UI)
                let opStr = opName;
                if (opName === 'is_empty') opStr = 'isEmpty'; // Aesthetics tweak if needed, or keep 'is_empty'

                steps.push({
                    index: idx + 1,
                    operation: opStr,
                    status,
                    print_output: printState || String(state),
                    explanation: expl,
                    complexity,
                    diagram: getDiag(currentState),
                    error_msg: error
                });
            });

            return { initial, steps };
        },
    };


    // ==========================================
    // DATA PARSER
    // ==========================================
    async function parseFileUpload(formData) {
        const file = formData.get('file');
        const fileName = file.name.toLowerCase();
        let headers = [];
        let cols = {};
        const lines = [];

        // Check for XLSX support
        if ((fileName.endsWith('.xlsx') || fileName.endsWith('.xls')) && window.XLSX) {
            const buffer = await file.arrayBuffer();
            const workbook = XLSX.read(buffer, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 }); // Array of arrays

            if (!jsonData || jsonData.length < 2) throw new Error("File empty or invalid XLSX");

            headers = jsonData[0].map(String);
            headers.forEach(h => cols[h] = []);

            for (let i = 1; i < jsonData.length; i++) {
                const row = jsonData[i];
                headers.forEach((h, j) => {
                    let val = row[j] !== undefined ? row[j] : null;
                    if (val !== null && !isNaN(Number(val))) val = Number(val);
                    cols[h].push(val);
                });
            }
            lines.length = jsonData.length; // Approximate for summary

        } else {
            // CSV / Text Fallback
            const text = await file.text();
            const textLines = text.trim().split(/\r?\n/);
            if (textLines.length < 2) throw new Error("File empty");
            const delim = textLines[0].includes('\t') ? '\t' : ',';

            headers = textLines[0].split(delim).map(s => s.trim().replace(/^"|"$/g, ''));
            headers.forEach(h => cols[h] = []);

            for (let i = 1; i < textLines.length; i++) {
                const row = textLines[i].split(delim);
                headers.forEach((h, j) => {
                    let val = row[j] ? row[j].trim().replace(/^"|"$/g, '') : null;
                    if (val && !isNaN(Number(val))) val = Number(val);
                    cols[h].push(val);
                });
            }
            lines.length = textLines.length;
        }

        const numCols = headers.filter(h => cols[h].filter(x => typeof x === 'number').length > cols[h].length * 0.5);
        return { full_data: cols, columns: headers, numeric_columns: numCols, summary: { rows: lines.length - 1 } };
    }

    // ==========================================
    // FETCH INTERCEPTOR
    // ==========================================
    const originalFetch = window.fetch;
    window.fetch = async function (url, options) {
        if ((typeof url === 'string') && (url.includes('127.0.0.1:8000') || url.startsWith('/api'))) {
            await new Promise(r => setTimeout(r, 100)); // Latency
            try {
                let responseData = null;
                // Parse Body safely
                let body = {};
                if (options && options.body) {
                    if (options.body instanceof FormData) {
                        // Pass FormData directly if expecting one (like in parseFileUpload)
                        body = options.body;
                    } else if (typeof options.body === 'string') {
                        try { body = JSON.parse(options.body); } catch (e) { body = {}; }
                    }
                }

                // ROUTING
                // STATS
                if (url.includes('/api/stats/calculate')) responseData = StatsEngine.calculate(body);
                // For parseFileUpload, use the raw FormData in options.body, NOT the parsed JSON empty object if it was a string
                else if (url.includes('/api/data/parse')) responseData = await parseFileUpload(options.body); // Use raw FormData

                // TGDRAW
                else if (url.includes('/api/tg/tree/create-root')) responseData = { success: true, data: new TreeEngine().reset({ type: body.type, root_value: body.value }) };
                else if (url.includes('/api/tg/tree/add-node')) responseData = { success: true, data: new TreeEngine().addNode(body.parent_id, body.value, body.direction) };
                else if (url.includes('/api/tg/tree/traverse')) responseData = { success: true, data: new TreeEngine().traverse(body.type) };
                else if (url.includes('/api/tg/graph/create')) responseData = { success: true, data: new GraphEngine().create(body) };
                else if (url.includes('/api/tg/graph/add-node')) responseData = { success: true, data: new GraphEngine().addNode(body.id, body.value) };
                else if (url.includes('/api/tg/graph/add-edge')) responseData = { success: true, data: new GraphEngine().addEdge(body.source, body.target, body.weight) };
                else if (url.includes('/api/tg/graph/remove-edge')) responseData = { success: true, data: new GraphEngine().removeEdge(body.source, body.target) };
                else if (url.includes('/api/tg/graph/run-algorithm')) responseData = { success: true, data: new GraphEngine().runAlgo(body.algorithm, body.start_node) };

                // PDRAW
                else if (url.includes('/api/pdraw/catalog')) responseData = PDRAW_CATALOG;
                else if (url.includes('/api/pdraw/simulate')) responseData = PDrawSimulator.simulate(body);

                if (responseData) return new Response(JSON.stringify(responseData), { status: 200, headers: { 'Content-Type': 'application/json' } });

            } catch (err) {
                console.error("[MockServer] Error", err);
                return new Response(JSON.stringify({ error: err.message, success: false }), { status: 400 });
            }
        }
        return originalFetch(url, options);
    };

})();
