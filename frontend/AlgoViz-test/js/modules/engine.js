/**
 * AlgoViz Animation Engine
 * Handles step playback and state management.
 */

export const Engine = {
    steps: [],
    currentStepIndex: 0, // 0-based
    isPlaying: false,
    speedDelay: 600, // Default 3 (starts at 1000ms - 200 * 3 = 400ms?) let's define map
    timer: null,
    onRender: null,  // Callback functions
    onComplete: null,

    // Getter for state compatibility
    get state() {
        return {
            steps: Engine.steps,
            currentIndex: Engine.currentStepIndex
        };
    },

    // Speed mapping: 1 (Slow) -> 5 (Fast)
    // 1: 1500ms, 2: 1000ms, 3: 600ms, 4: 300ms, 5: 100ms
    speedMap: {
        1: 1500,
        2: 1000,
        3: 600,
        4: 300,
        5: 100
    },

    init: (renderCallback, completeCallback) => {
        Engine.onRender = renderCallback;
        Engine.onComplete = completeCallback;
    },

    load: (steps) => {
        Engine.pause();
        Engine.steps = steps;
        Engine.currentStepIndex = 0;
        Engine.render();
    },

    setSpeed: (level) => {
        Engine.speedDelay = Engine.speedMap[level] || 600;
        if (Engine.isPlaying) {
            // Restart timer with new speed
            Engine.pause();
            Engine.play();
        }
    },

    play: () => {
        if (Engine.isPlaying) return;
        if (Engine.currentStepIndex >= Engine.steps.length - 1) {
            // Restart if at end
            Engine.currentStepIndex = 0;
        }

        Engine.isPlaying = true;
        Engine.tick();
    },

    pause: () => {
        Engine.isPlaying = false;
        if (Engine.timer) {
            clearTimeout(Engine.timer);
            Engine.timer = null;
        }
    },

    toggle: () => {
        if (Engine.isPlaying) Engine.pause();
        else Engine.play();
    },

    tick: () => {
        if (!Engine.isPlaying) return;

        Engine.render();

        if (Engine.currentStepIndex >= Engine.steps.length - 1) {
            Engine.pause();
            if (Engine.onComplete) Engine.onComplete();
            return;
        }

        Engine.timer = setTimeout(() => {
            Engine.currentStepIndex++;
            Engine.tick();
        }, Engine.speedDelay);
    },

    stepNext: () => {
        Engine.pause();
        if (Engine.currentStepIndex < Engine.steps.length - 1) {
            Engine.currentStepIndex++;
            Engine.render();
        }
    },

    stepPrev: () => {
        Engine.pause();
        if (Engine.currentStepIndex > 0) {
            Engine.currentStepIndex--;
            Engine.render();
        }
    },

    reset: () => {
        Engine.pause();
        Engine.currentStepIndex = 0;
        Engine.render();
    },

    render: () => {
        if (Engine.steps.length > 0 && Engine.onRender) {
            const step = Engine.steps[Engine.currentStepIndex];
            Engine.onRender(step, Engine.currentStepIndex, Engine.steps.length);
        }
    }
};
