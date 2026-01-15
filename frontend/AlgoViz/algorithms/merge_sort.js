/**
 * Merge Sort Implementation
 */

export const MergeSort = {
    metadata: {
        id: 'merge-sort',
        name: 'Merge Sort',
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
        timeWorst: 'O(n log n)',
        space: 'O(n)',
        stable: 'Yes',
        notes: 'Divides the array into halves, sorts them and then merges the sorted halves.'
    },

    codeTemplate: {
        python: {
            // Standard Recursive Implementation
            for: `def merge_sort(arr):
    if len(arr) <= 1:
        return arr
    
    mid = len(arr) // 2
    left = merge_sort(arr[:mid])
    right = merge_sort(arr[mid:])
    
    return merge(left, right)

def merge(left, right):
    result = []
    i = j = 0
    while i < len(left) and j < len(right):
        if left[i] < right[j]:
            result.append(left[i])
            i += 1
        else:
            result.append(right[j])
            j += 1
    result.extend(left[i:])
    result.extend(right[j:])
    return result`,

            while: `def merge_sort(arr):
    if len(arr) > 1:
        mid = len(arr) // 2
        L = arr[:mid]
        R = arr[mid:]

        merge_sort(L)
        merge_sort(R)

        i = j = k = 0
        while i < len(L) and j < len(R):
            if L[i] < R[j]:
                arr[k] = L[i]
                i += 1
            else:
                arr[k] = R[j]
                j += 1
            k += 1
        
        while i < len(L):
            arr[k] = L[i]
            i += 1
            k += 1

        while j < len(R):
            arr[k] = R[j]
            j += 1
            k += 1`
        }
    },

    validate: (inputStr, type) => {
        if (!inputStr.trim()) return [];
        if (type !== 'numbers') throw new Error("Merge Sort only supports Numbers.");

        return inputStr.split(/[\s,]+/).filter(v => v !== '').map(v => {
            const n = Number(v);
            if (isNaN(n)) throw new Error(`Invalid number: "${v}"`);
            return n;
        });
    },

    generateSteps: (arr, params = { order: 'ascending', recursion: false }, loopType = 'for') => {
        const steps = [];
        // We work on a copy, but to visualize correctly on the original array structure, 
        // we usually simulate the In-Place version or map the auxiliary array back to main indices.
        // For visualization simplicity on a single array row, we'll simulate the "In-Place" behavior logic 
        // or just copy back to the main array at each merge step.

        let mainArray = [...arr];
        const isDesc = params.order === 'descending';
        const showRecursion = params.recursion;
        const useKPointer = loopType === 'while'; // Only show 'k' pointer for While loop template

        // Code Line Mapping (using While template for in-place-ish visualization)
        // 1: def merge_sort
        // 2: if len > 1
        // 3:    mid = ...
        // 6:    merge_sort(L)
        // 7:    merge_sort(R)
        // 9:    while i < len(L)... (merge start)

        const lines = {
            start: 1,
            split: 3,
            recurseLeft: 6,
            recurseRight: 7,
            mergeStart: 9,
            compare: 10,
            overwrite: 11
        };

        steps.push({
            arr: [...mainArray],
            highlights: [],
            pointers: {},
            message: "Starting Merge Sort...",
            codeLine: lines.start
        });

        function mergeSort(start, end, depth = 0) {
            if (start >= end) return;

            const mid = Math.floor((start + end) / 2);

            if (showRecursion) {
                steps.push({
                    arr: [...mainArray],
                    highlights: [
                        { index: start, type: 'pivot' },
                        { index: end, type: 'pivot' },
                        ...Array.from({ length: end - start + 1 }, (_, i) => ({ index: start + i, type: 'active' })) // Range
                    ],
                    pointers: { low: start, high: end, mid },
                    message: `[Depth ${depth}] Splitting range [${start}, ${end}] at ${mid}`,
                    codeLine: lines.split
                });
            }

            // Left
            if (showRecursion) steps.push({
                arr: [...mainArray],
                highlights: [], pointers: {}, message: `[Depth ${depth}] Recursing Left...`, codeLine: lines.recurseLeft
            });
            mergeSort(start, mid, depth + 1);

            // Right
            if (showRecursion) steps.push({
                arr: [...mainArray],
                highlights: [], pointers: {}, message: `[Depth ${depth}] Recursing Right...`, codeLine: lines.recurseRight
            });
            mergeSort(mid + 1, end, depth + 1);

            // Merge
            merge(start, mid, end);
        }

        function merge(start, mid, end) {
            steps.push({
                arr: [...mainArray],
                highlights: [
                    ...Array.from({ length: end - start + 1 }, (_, i) => ({ index: start + i, type: 'active' }))
                ],
                pointers: { i: start, j: mid + 1 },
                message: `Merging ranges [${start}-${mid}] and [${mid + 1}-${end}]`,
                codeLine: lines.mergeStart
            });

            // Create temp arrays
            let leftArr = mainArray.slice(start, mid + 1);
            let rightArr = mainArray.slice(mid + 1, end + 1);

            let i = 0, j = 0, k = start;

            while (i < leftArr.length && j < rightArr.length) {
                // Visualization of comparison
                // Note: Indicies in temp arrays don't map directly to main array visual easily
                // We'll show the target index 'k' in yellow
                steps.push({
                    arr: [...mainArray],
                    highlights: [
                        { index: k, type: 'compare' }
                    ],
                    pointers: { i: start + i, j: mid + 1 + j },
                    message: `Comparing left[${i}]:${leftArr[i]} and right[${j}]:${rightArr[j]}`,
                    codeLine: lines.compare
                });

                let valToTake;
                if (isDesc ? leftArr[i] > rightArr[j] : leftArr[i] <= rightArr[j]) {
                    valToTake = leftArr[i];
                    i++;
                } else {
                    valToTake = rightArr[j];
                    j++;
                }

                mainArray[k] = valToTake;
                steps.push({
                    arr: [...mainArray],
                    highlights: [
                        { index: k, type: 'overwrite' }
                    ],
                    pointers: { i: start + i, j: mid + 1 + j },
                    message: `Placed ${valToTake} at result position`,
                    codeLine: lines.overwrite
                });
                k++;
            }

            // Remaining
            while (i < leftArr.length) {
                mainArray[k] = leftArr[i];
                steps.push({
                    arr: [...mainArray],
                    highlights: [{ index: k, type: 'overwrite' }],
                    pointers: useKPointer ? { k } : { i: start + i, j: mid + 1 + j },
                    message: `Taking remaining L: ${leftArr[i]}`,
                    codeLine: lines.overwrite
                });
                i++; k++;
            }

            while (j < rightArr.length) {
                mainArray[k] = rightArr[j];
                steps.push({
                    arr: [...mainArray],
                    highlights: [{ index: k, type: 'overwrite' }],
                    pointers: useKPointer ? { k } : { i: start + i, j: mid + 1 + j },
                    message: `Taking remaining R: ${rightArr[j]}`,
                    codeLine: lines.overwrite
                });
                j++; k++;
            }

            // End of merge -> range is sorted
            steps.push({
                arr: [...mainArray],
                highlights: [
                    ...Array.from({ length: end - start + 1 }, (_, x) => ({ index: start + x, type: 'sorted' }))
                ],
                pointers: {},
                message: `Range [${start}-${end}] merged and sorted.`,
                codeLine: lines.mergeStart
            });
        }

        mergeSort(0, mainArray.length - 1);

        steps.push({
            arr: [...mainArray],
            highlights: mainArray.map((_, idx) => ({ index: idx, type: 'sorted' })),
            pointers: {},
            message: "Merge Sort Complete!",
            codeLine: lines.start
        });

        return steps;
    }
};
