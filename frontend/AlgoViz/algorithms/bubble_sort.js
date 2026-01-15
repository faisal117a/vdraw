/**
 * Bubble Sort Implementation
 */

export const BubbleSort = {
    metadata: {
        id: 'bubble-sort',
        name: 'Bubble Sort',
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
        stable: 'Yes',
        notes: 'Repeatedly steps through the list, compares adjacent elements and swaps them if they are in the wrong order.'
    },

    codeTemplate: {
        python: {
            for: `def bubble_sort(arr, order):
    n = len(arr)
    for i in range(n):
        swapped = False
        for j in range(0, n-i-1):
            if compare(arr[j], arr[j+1], order):
                arr[j], arr[j+1] = arr[j+1], arr[j]
                swapped = True
        if not swapped:
            break`,

            while: `def bubble_sort(arr, order):
    n = len(arr)
    swapped = True
    while swapped:
        swapped = False
        i = 0
        while i < n - 1:
            if compare(arr[i], arr[i+1], order):
                arr[i], arr[i+1] = arr[i+1], arr[i]
                swapped = True
            i += 1`
        }
    },

    validate: (inputStr, type) => {
        if (!inputStr.trim()) return [];
        if (type !== 'numbers') throw new Error("Bubble Sort only supports Numbers.");

        return inputStr.split(/[\s,]+/).filter(v => v !== '').map(v => {
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
        const shouldSwap = (a, b) => isDesc ? a < b : a > b; // Bubble sort swaps if left > right (asc)

        // Line Mapping (For Loop)
        // 1: def...
        // 2: n = len...
        // 3: for i...
        // 4:     swapped = False
        // 5:     for j...
        // 6:         if compare...
        // 7:             swap...
        // 8:             swapped = True
        // 9:     if not swapped...

        const lines = {
            outer: 3,
            initSwap: 4,
            inner: 5,
            compare: 6,
            swap: 7,
            setSwap: 8,
            checkEarly: 9
        };

        steps.push({
            arr: [...arr],
            highlights: [],
            pointers: {},
            message: "Starting Bubble Sort...",
            codeLine: 1,
            cycle: 0,
            stepInCycle: 1
        });

        for (let i = 0; i < n; i++) {
            let swapped = false;
            let stepInCycle = 1;

            steps.push({
                arr: [...arr],
                highlights: [],
                pointers: { i },
                message: `Pass ${i + 1}`,
                codeLine: lines.outer,
                cycle: i + 1,
                stepInCycle: stepInCycle++
            });

            for (let j = 0; j < n - i - 1; j++) {
                steps.push({
                    arr: [...arr],
                    highlights: [
                        { index: j, type: 'compare' },
                        { index: j + 1, type: 'compare' }
                    ],
                    pointers: { j },
                    message: `Comparing ${arr[j]} and ${arr[j + 1]}`,
                    codeLine: lines.compare,
                    cycle: i + 1,
                    stepInCycle: stepInCycle++
                });

                if (shouldSwap(arr[j], arr[j + 1])) {
                    // Pre-Swap
                    steps.push({
                        arr: [...arr],
                        highlights: [
                            { index: j, type: 'swap' },
                            { index: j + 1, type: 'swap' }
                        ],
                        pointers: { j },
                        message: `Swapping ${arr[j]} and ${arr[j + 1]}`,
                        codeLine: lines.swap,
                        cycle: i + 1,
                        stepInCycle: stepInCycle++
                    });

                    [arr[j], arr[j + 1]] = [arr[j + 1], arr[j]];
                    swapped = true;

                    // Post-Swap
                    steps.push({
                        arr: [...arr],
                        highlights: [
                            { index: j, type: 'active' },
                            { index: j + 1, type: 'active' }
                        ],
                        pointers: { j },
                        message: `Swapped.`,
                        codeLine: lines.setSwap,
                        cycle: i + 1,
                        stepInCycle: stepInCycle++
                    });
                }
            }

            // Mark end as sorted
            const sortedIdx = n - i - 1;
            steps.push({
                arr: [...arr],
                highlights: [{ index: sortedIdx, type: 'sorted' }],
                pointers: {},
                message: `Element at index ${sortedIdx} is sorted.`,
                codeLine: lines.initSwap, // loosely mapped
                cycle: i + 1,
                stepInCycle: stepInCycle++
            });

            if (!swapped) {
                steps.push({
                    arr: [...arr],
                    highlights: [],
                    pointers: {},
                    message: "No swaps in this pass. Array is sorted!",
                    codeLine: lines.checkEarly,
                    cycle: i + 1,
                    stepInCycle: stepInCycle++
                });
                break;
            }
        }

        steps.push({
            arr: [...arr],
            highlights: arr.map((_, idx) => ({ index: idx, type: 'sorted' })),
            pointers: {},
            message: "Bubble Sort Complete!",
            codeLine: 1
        });

        return steps;
    }
};
