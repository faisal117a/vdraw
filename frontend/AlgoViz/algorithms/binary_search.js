/**
 * Binary Search Implementation
 */

export const BinarySearch = {
    metadata: {
        id: 'binary-search',
        name: 'Binary Search',
        category: 'Searching',
        supportedTypes: ['numbers'], // Text not supported per spec
        params: [
            { name: 'target', type: 'value', label: 'Search Value' }
        ]
    },

    complexity: {
        timeAvg: 'O(log n)',
        timeWorst: 'O(log n)',
        space: 'O(1)',
        stable: 'N/A',
        notes: 'Requires a sorted array. Divides search interval in half at each step.'
    },

    codeTemplate: {
        python: {
            for: `def binary_search(arr, target):
    low, high = 0, len(arr) - 1
    
    for _ in range(len(arr)):
        if low > high:
            break
        mid = (low + high) // 2
        if arr[mid] == target:
            return mid
        elif arr[mid] < target:
            low = mid + 1
        else:
            high = mid - 1
            
    return -1`,
            while: `def binary_search(arr, target):
    low = 0
    high = len(arr) - 1
    
    while low <= high:
        mid = (low + high) // 2
        if arr[mid] == target:
            return mid
        elif arr[mid] < target:
            low = mid + 1
        else:
            high = mid - 1
            
    return -1`
        }
    },

    validate: (inputStr, type) => {
        if (!inputStr.trim()) return [];
        if (type !== 'numbers') throw new Error("Binary Search only supports Numbers.");

        const rawValues = inputStr.split(/[\s,]+/).filter(v => v !== '');
        if (rawValues.length > 50) throw new Error("Max 50 values allowed.");

        const numbers = rawValues.map(v => {
            const n = Number(v);
            if (isNaN(n)) throw new Error(`Invalid number: "${v}"`);
            return n;
        });
        return numbers;
    },

    isSorted: (arr) => {
        for (let i = 0; i < arr.length - 1; i++) {
            if (arr[i] > arr[i + 1]) return false;
        }
        return true;
    },

    generateSteps: (arr, params, loopType = 'while') => {
        // Force loopType to 'while' since Binary Search is standard 'while'
        // If we really need 'for', we'd need a weird implementation.
        // Let's assume 'while' for the code mapping.

        const steps = [];
        const target = params.target;

        // Lines for While Loop (matches the template above)
        // 1: def...
        // 2: low = 0
        // 3: high = len - 1
        // 4: 
        // 5: while low <= high:
        // 6:     mid = ...
        // 7:     if arr[mid] == target:
        // 8:         return mid
        // 9:     elif arr[mid] < target:
        // 10:        low = mid + 1
        // 11:    else:
        // 12:        high = mid - 1
        // 13: return -1

        const lines = {
            init: [2, 3],
            loop: 5,
            mid: 6,
            checkMatch: 7,
            found: 8,
            checkRight: 9,
            moveRight: 10,
            moveLeft: 12,
            end: 14 // fail return
        };

        let low = 0;
        let high = arr.length - 1;

        // Init
        steps.push({
            arr: [...arr],
            highlights: [],
            pointers: { low, high },
            message: "Initializing search range [low, high]",
            codeLine: lines.init[0] // approximation
        });

        while (low <= high) {
            steps.push({
                arr: [...arr],
                highlights: [
                    { index: low, type: 'active' },
                    { index: high, type: 'active' }
                ],
                pointers: { low, high },
                message: `Checking range [${low}, ${high}]`,
                codeLine: lines.loop
            });

            const mid = Math.floor((low + high) / 2);

            steps.push({
                arr: [...arr],
                highlights: [
                    { index: low, type: 'active' },
                    { index: high, type: 'active' },
                    { index: mid, type: 'pivot' } // purple for mid
                ],
                pointers: { low, high, mid },
                message: `Calculating mid index: ${mid} (Value: ${arr[mid]})`,
                codeLine: lines.mid
            });

            // Match Check
            steps.push({
                arr: [...arr],
                highlights: [
                    { index: mid, type: 'compare' }
                ],
                pointers: { low, high, mid },
                message: `Comparing ${arr[mid]} with ${target}`,
                codeLine: lines.checkMatch
            });

            if (arr[mid] === target) {
                steps.push({
                    arr: [...arr],
                    highlights: [{ index: mid, type: 'sorted' }],
                    pointers: { mid },
                    message: `Found ${target} at index ${mid}!`,
                    codeLine: lines.found
                });
                return steps;
            }

            // Direction Check
            steps.push({
                arr: [...arr],
                highlights: [{ index: mid, type: 'compare' }],
                pointers: { low, high, mid },
                message: `${arr[mid]} < ${target}? ${arr[mid] < target}`,
                codeLine: lines.checkRight
            });

            if (arr[mid] < target) {
                low = mid + 1;
                steps.push({
                    arr: [...arr],
                    highlights: [],
                    pointers: { low, high },
                    message: "Value is larger, moving to right half.",
                    codeLine: lines.moveRight
                });
            } else {
                high = mid - 1;
                steps.push({
                    arr: [...arr],
                    highlights: [],
                    pointers: { low, high },
                    message: "Value is smaller, moving to left half.",
                    codeLine: lines.moveLeft
                });
            }
        }

        steps.push({
            arr: [...arr],
            highlights: [],
            pointers: {},
            message: `${target} not found.`,
            codeLine: lines.end
        });

        return steps;
    }
};
