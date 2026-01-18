/**
 * Linear Search Implementation
 */

export const LinearSearch = {
    metadata: {
        id: 'linear-search',
        name: 'Linear Search',
        category: 'Searching',
        supportedTypes: ['numbers', 'text'],
        params: [
            { name: 'target', type: 'value', label: 'Search Value' }
        ]
    },

    complexity: {
        timeAvg: 'O(n)',
        timeWorst: 'O(n)',
        space: 'O(1)',
        stable: 'N/A',
        notes: 'Iterates through each element until a match is found or the end is reached.'
    },

    codeTemplate: {
        python: {
            for: `def linear_search(arr, target):
    for i in range(len(arr)):
        if arr[i] == target:
            return i
    return -1`,
            while: `def linear_search(arr, target):
    i = 0
    while i < len(arr):
        if arr[i] == target:
            return i
        i += 1
    return -1`
        }
    },

    /**
     * Parsing and Validation
     * @param {string} inputStr Raw input string
     * @param {string} type 'numbers' or 'text'
     * @returns {Array} Parsed array or throws error
     */
    validate: (inputStr, type) => {
        if (!inputStr.trim()) return [];

        // Split by comma, space, or newline
        const rawValues = inputStr.split(/[\s,]+/).filter(v => v !== '');

        if (rawValues.length > 50) {
            throw new Error("Max 50 values allowed.");
        }

        if (type === 'numbers') {
            const numbers = rawValues.map(v => {
                const n = Number(v);
                if (isNaN(n)) throw new Error(`Invalid number: "${v}"`);
                return n;
            });
            return numbers;
        } else {
            return rawValues;
        }
    },

    /**
     * Generate Visualization Steps
     * @param {Array} arr Input array
     * @param {Object} params { target }
     * @param {string} loopType 'for' or 'while'
     * @returns {Array} List of step objects
     */
    generateSteps: (arr, params, loopType = 'for') => {
        const steps = [];
        const target = params.target;
        const len = arr.length;

        // Line mapping for code highlighting
        // For Loop:
        // 1: def...
        // 2: for i...
        // 3:     if arr[i]...
        // 4:         return i
        // 5: return -1

        // While Loop:
        // 1: def...
        // 2: i = 0
        // 3: while i < len...
        // 4:     if arr[i]...
        // 5:         return i
        // 6:     i += 1
        // 7: return -1

        const lines = loopType === 'for'
            ? { loop: 2, compare: 3, found: 4, end: 5 }
            : { init: 2, loop: 3, compare: 4, found: 5, inc: 6, end: 7 };

        // Initial State
        steps.push({
            arr: [...arr],
            highlights: [],
            pointers: {},
            message: "Starting Linear Search...",
            codeLine: 1,
            cycle: 0,
            stepInCycle: 1
        });

        if (loopType === 'while') {
            steps.push({
                arr: [...arr],
                highlights: [],
                pointers: { i: 0 },
                message: "Initializing index i = 0",
                codeLine: lines.init,
                cycle: 0,
                stepInCycle: 2
            });
        }

        for (let i = 0; i < len; i++) {
            let stepInCycle = 1;

            // Loop Step
            steps.push({
                arr: [...arr],
                highlights: [{ index: i, type: 'active' }],
                pointers: { i: i },
                message: `Checking index ${i}...`,
                codeLine: lines.loop,
                cycle: i + 1,
                stepInCycle: stepInCycle++
            });

            // Compare Step
            const match = arr[i] == target; // Loose equality for mixed input potentially

            steps.push({
                arr: [...arr],
                highlights: [{ index: i, type: match ? 'active' : 'compare' }],
                pointers: { i: i },
                message: `Comparing ${arr[i]} with ${target}`,
                codeLine: lines.compare,
                cycle: i + 1,
                stepInCycle: stepInCycle++
            });

            if (match) {
                // Found Step
                steps.push({
                    arr: [...arr],
                    highlights: [{ index: i, type: 'sorted' }], // Green for found
                    pointers: { i: i },
                    message: `Found ${target} at index ${i}!`,
                    codeLine: lines.found,
                    cycle: i + 1,
                    stepInCycle: stepInCycle++
                });
                return steps;
            }

            if (loopType === 'while') {
                steps.push({
                    arr: [...arr],
                    highlights: [{ index: i, type: 'default' }],
                    pointers: { i: i },
                    message: "Incrementing i...",
                    codeLine: lines.inc,
                    cycle: i + 1,
                    stepInCycle: stepInCycle++
                });
            }
        }

        // Not Found
        steps.push({
            arr: [...arr],
            highlights: [],
            pointers: {},
            message: `${target} not found in the list.`,
            codeLine: lines.end,
            cycle: len + 1,
            stepInCycle: 1
        });

        return steps;
    }
};
