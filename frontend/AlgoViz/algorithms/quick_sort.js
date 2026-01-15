/**
 * Quick Sort Implementation
 */

export const QuickSort = {
    metadata: {
        id: 'quick-sort',
        name: 'Quick Sort',
        category: 'Sorting',
        supportedTypes: ['numbers'],
        supportsRecursion: true,
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
        timeAvg: 'O(n log n)',
        timeWorst: 'O(n²)',
        space: 'O(log n)',
        stable: 'No',
        notes: 'Picks a pivot and partitions the array into two sub-arrays.'
    },

    codeTemplate: {
        python: {
            // Standard Recursive Implementation
            for: `def quick_sort(arr, low, high):
    if low < high:
        pi = partition(arr, low, high)
        quick_sort(arr, low, pi - 1)
        quick_sort(arr, pi + 1, high)

def partition(arr, low, high):
    pivot = arr[high]
    i = low - 1
    for j in range(low, high):
        if arr[j] < pivot:
            i += 1
            arr[i], arr[j] = arr[j], arr[i]
    arr[i + 1], arr[high] = arr[high], arr[i + 1]
    return i + 1`,

            while: `def quick_sort(arr):
    stack = [(0, len(arr) - 1)]
    while stack:
        low, high = stack.pop()
        if low < high:
            pi = partition(arr, low, high)
            stack.append((low, pi - 1))
            stack.append((pi + 1, high))

def partition(arr, low, high):
    pivot = arr[high]
    i = low - 1
    j = low
    while j < high:
        if arr[j] < pivot:
            i += 1
            arr[i], arr[j] = arr[j], arr[i]
        j += 1
    arr[i + 1], arr[high] = arr[high], arr[i + 1]
    return i + 1`
        }
    },

    validate: (inputStr, type) => {
        if (!inputStr.trim()) return [];
        if (type !== 'numbers') throw new Error("Quick Sort only supports Numbers.");

        return inputStr.split(/[\s,]+/).filter(v => v !== '').map(v => {
            const n = Number(v);
            if (isNaN(n)) throw new Error(`Invalid number: "${v}"`);
            return n;
        });
    },

    generateSteps: (arr, params = { order: 'ascending', recursion: false }) => {
        const steps = [];
        let mainArray = [...arr];
        const isDesc = params.order === 'descending';
        const showRecursion = params.recursion;

        const shouldSwap = (a, b) => isDesc ? a > b : a < b; // Pivot comparisons

        // Lines (Recursive Python template)
        // 1: def quick_sort
        // 2: if low < high
        // 3:    pi = partition...
        // 4:    quick_sort(low...)
        // 5:    quick_sort(high...)
        // 7: def partition...
        // 8:    pivot = arr[high]
        // 9:    for j in range...
        // 10:       if arr[j] < pivot
        // 11:          swap(i, j)
        // 12:    swap(i+1, high)

        const lines = {
            start: 1,
            checkBase: 2,
            callPartition: 3,
            recurseLeft: 4,
            recurseRight: 5,
            pivotPick: 8,
            loop: 9,
            compare: 10,
            swapInner: 11,
            swapPivot: 12
        };

        steps.push({
            arr: [...mainArray],
            highlights: [],
            pointers: {},
            message: "Starting Quick Sort...",
            codeLine: lines.start
        });

        function quickSort(low, high, depth = 0) {
            if (low < high) {
                if (showRecursion) {
                    steps.push({
                        arr: [...mainArray],
                        highlights: [
                            ...Array.from({ length: high - low + 1 }, (_, i) => ({ index: low + i, type: 'active' }))
                        ],
                        pointers: { low, high },
                        message: `[Depth ${depth}] Working on range [${low}, ${high}]`,
                        codeLine: lines.checkBase
                    });
                }

                steps.push({
                    arr: [...mainArray],
                    highlights: [], pointers: { low, high }, message: "Partitioning...", codeLine: lines.callPartition
                });

                const pi = partition(low, high);

                if (showRecursion) {
                    steps.push({
                        arr: [...mainArray],
                        highlights: [], pointers: { pi }, message: `[Depth ${depth}] Pivot placed at ${pi}. Recursing Left...`, codeLine: lines.recurseLeft
                    });
                }
                quickSort(low, pi - 1, depth + 1);

                if (showRecursion) {
                    steps.push({
                        arr: [...mainArray],
                        highlights: [], pointers: { pi }, message: `[Depth ${depth}] Recursing Right...`, codeLine: lines.recurseRight
                    });
                }
                quickSort(pi + 1, high, depth + 1);
            }
        }

        function partition(low, high) {
            const pivot = mainArray[high];
            let i = low - 1;

            steps.push({
                arr: [...mainArray],
                highlights: [
                    { index: high, type: 'pivot' }
                ],
                pointers: { low, high, pivot: high },
                message: `Chosen pivot: ${pivot} (at index ${high})`,
                codeLine: lines.pivotPick
            });

            for (let j = low; j < high; j++) {
                steps.push({
                    arr: [...mainArray],
                    highlights: [
                        { index: high, type: 'pivot' },
                        { index: j, type: 'compare' },
                        { index: i + 1, type: 'active' } // Potential swap target
                    ],
                    pointers: { j, i, high },
                    message: `Comparing ${mainArray[j]} with Pivot ${pivot}`,
                    codeLine: lines.compare
                });

                if (shouldSwap(mainArray[j], pivot)) { // In generic terms: is arr[j] on the "correct side" (left) of pivot?
                    // For ASC: arr[j] < pivot. For DESC: arr[j] > pivot.
                    i++;

                    steps.push({
                        arr: [...mainArray],
                        highlights: [
                            { index: i, type: 'swap' },
                            { index: j, type: 'swap' },
                            { index: high, type: 'pivot' }
                        ],
                        pointers: { i, j },
                        message: `Swapping ${mainArray[i]} and ${mainArray[j]}`,
                        codeLine: lines.swapInner
                    });

                    [mainArray[i], mainArray[j]] = [mainArray[j], mainArray[i]];
                }
            }

            // Swap pivot to correct position
            steps.push({
                arr: [...mainArray],
                highlights: [
                    { index: i + 1, type: 'swap' },
                    { index: high, type: 'swap' } // old pivot pos
                ],
                pointers: { pivotIdx: i + 1 },
                message: `Moving pivot to correct position ${i + 1}`,
                codeLine: lines.swapPivot
            });

            [mainArray[i + 1], mainArray[high]] = [mainArray[high], mainArray[i + 1]];

            steps.push({
                arr: [...mainArray],
                highlights: [
                    { index: i + 1, type: 'sorted' } // Pivot is fixed
                ],
                pointers: { pi: i + 1 },
                message: `Pivot ${pivot} fixed at ${i + 1}`,
                codeLine: lines.swapPivot
            });

            return i + 1;
        }

        quickSort(0, mainArray.length - 1);

        steps.push({
            arr: [...mainArray],
            highlights: mainArray.map((_, idx) => ({ index: idx, type: 'sorted' })),
            pointers: {},
            message: "Quick Sort Complete!",
            codeLine: lines.start
        });

        return steps;
    }
};
