/**
 * Selection Sort Implementation
 */

export const SelectionSort = {
    metadata: {
        id: 'selection-sort',
        name: 'Selection Sort',
        category: 'Sorting',
        supportedTypes: ['numbers'],
        params: [
            {
                name: 'order',
                type: 'select',
                label: 'Sort Order',
                options: ['ascending', 'descending']
            }
        ]
    },

    complexity: {
        timeAvg: 'O(n²)',
        timeWorst: 'O(n²)',
        space: 'O(1)',
        stable: 'No',
        notes: 'Repeatedly finds the minimum element and moves it to the sorted part.'
    },

    codeTemplate: {
        python: {
            for: `def selection_sort(arr, order):
    n = len(arr)
    for i in range(n):
        min_idx = i
        for j in range(i + 1, n):
            if compare(arr[j], arr[min_idx], order):
                min_idx = j
        arr[i], arr[min_idx] = arr[min_idx], arr[i]`,

            // While loop version if user toggles
            while: `def selection_sort(arr, order):
    n = len(arr)
    i = 0
    while i < n:
        min_idx = i
        j = i + 1
        while j < n:
            if compare(arr[j], arr[min_idx], order):
                min_idx = j
            j += 1
        arr[i], arr[min_idx] = arr[min_idx], arr[i]
        i += 1`
        }
    },

    validate: (inputStr, type) => {
        if (!inputStr.trim()) return [];
        if (type !== 'numbers') throw new Error("Selection Sort only supports Numbers.");

        const rawValues = inputStr.split(/[\s,]+/).filter(v => v !== '');
        if (rawValues.length > 50) throw new Error("Max 50 values allowed.");

        return rawValues.map(v => {
            const n = Number(v);
            if (isNaN(n)) throw new Error(`Invalid number: "${v}"`);
            return n;
        });
    },

    generateSteps: (arr, params = { order: 'ascending' }, loopType = 'for') => {
        const steps = [];
        const n = arr.length;
        const isDesc = params.order === 'descending';

        // Compare helper
        const shouldSwap = (a, b) => isDesc ? a > b : a < b;

        // Line Mapping (For Loop)
        // 1: def...
        // 2: n = len
        // 3: for i...
        // 4:     min_idx = i
        // 5:     for j...
        // 6:         if compare...
        // 7:             min_idx = j
        // 8:     arr[i], arr[min_idx]...

        const lines = {
            start: 2,
            outerLoop: 3,
            setMin: 4,
            innerLoop: 5,
            compare: 6,
            setNewMin: 7,
            swap: 8
        };

        steps.push({
            arr: [...arr],
            highlights: [],
            pointers: {},
            message: "Starting Selection Sort...",
            codeLine: 1,
            cycle: 0,
            stepInCycle: 1
        });

        for (let i = 0; i < n; i++) {
            let min_idx = i;
            let stepInCycle = 1;

            steps.push({
                arr: [...arr],
                highlights: [
                    { index: i, type: 'active' }
                ],
                pointers: { i, min_idx },
                message: `Iteration ${i + 1}: Assuming min/max at index ${i}`,
                codeLine: lines.setMin,
                cycle: i + 1,
                stepInCycle: stepInCycle++
            });

            for (let j = i + 1; j < n; j++) {
                steps.push({
                    arr: [...arr],
                    highlights: [
                        { index: i, type: 'active' }, // Sorted boundary
                        { index: min_idx, type: 'pivot' }, // Current min
                        { index: j, type: 'compare' }  // Current compare
                    ],
                    pointers: { i, j, min_idx },
                    message: `Comparing ${arr[j]} with current ${isDesc ? 'max' : 'min'} (${arr[min_idx]})`,
                    codeLine: lines.compare,
                    cycle: i + 1,
                    stepInCycle: stepInCycle++
                });

                if (shouldSwap(arr[j], arr[min_idx])) {
                    min_idx = j;
                    steps.push({
                        arr: [...arr],
                        highlights: [
                            { index: i, type: 'active' },
                            { index: min_idx, type: 'pivot' } // New min
                        ],
                        pointers: { i, j, min_idx },
                        message: `New ${isDesc ? 'max' : 'min'} found at index ${j}`,
                        codeLine: lines.setNewMin,
                        cycle: i + 1,
                        stepInCycle: stepInCycle++
                    });
                }
            }

            // Swap Step
            if (min_idx !== i) {
                // Pre-swap highlight
                steps.push({
                    arr: [...arr],
                    highlights: [
                        { index: i, type: 'swap' },
                        { index: min_idx, type: 'swap' }
                    ],
                    pointers: { i, min_idx },
                    message: `Swapping ${arr[i]} and ${arr[min_idx]}`,
                    codeLine: lines.swap,
                    cycle: i + 1,
                    stepInCycle: stepInCycle++
                });

                [arr[i], arr[min_idx]] = [arr[min_idx], arr[i]];
            }

            // Sorted Step
            steps.push({
                arr: [...arr],
                highlights: [
                    { index: i, type: 'sorted' }
                ],
                pointers: { i },
                message: `Index ${i} is now sorted: ${arr[i]}`,
                codeLine: lines.outerLoop,
                cycle: i + 1,
                stepInCycle: stepInCycle++
            });
        }

        // Final State
        steps.push({
            arr: [...arr],
            highlights: arr.map((_, idx) => ({ index: idx, type: 'sorted' })),
            pointers: {},
            message: "Selection Sort Complete!",
            codeLine: lines.start,
            cycle: n + 1,
            stepInCycle: 1
        });

        return steps;
    }
};
