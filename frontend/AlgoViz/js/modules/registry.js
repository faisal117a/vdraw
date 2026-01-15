/**
 * Algorithm Registry
 * Manages the available algorithms and their metadata.
 */

const algorithms = new Map();

export const AlgorithmRegistry = {
    /**
     * Register a new algorithm module
     * @param {Object} algoModule 
     */
    register: (algoModule) => {
        if (!algoModule.metadata || !algoModule.metadata.id) {
            console.error("Algorithm module missing metadata or ID");
            return;
        }
        algorithms.set(algoModule.metadata.id, algoModule);
        console.log(`Registered algorithm: ${algoModule.metadata.name}`);
    },

    /**
     * Get an algorithm by ID
     * @param {string} id 
     * @returns {Object}
     */
    get: (id) => {
        return algorithms.get(id);
    },

    /**
     * Get all algorithms, optionally filtered
     * @returns {Array}
     */
    getAll: () => {
        return Array.from(algorithms.values());
    },

    /**
     * Get algorithms compatible with a specific data type
     * @param {string} type 'numbers' or 'text'
     * @returns {Array}
     */
    getByType: (type) => {
        return Array.from(algorithms.values()).filter(algo =>
            algo.metadata.supportedTypes.includes(type)
        );
    }
};
