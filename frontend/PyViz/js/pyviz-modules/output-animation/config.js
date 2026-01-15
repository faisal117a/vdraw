export const CONFIG = {
    ENABLED: true,
    // Local Path (Relative to Web Root)
    // Dynamic Path Resolution using import.meta.url
    RUNTIME_URL: new URL('./lib/pyodide.js', import.meta.url).href,
    RUNTIME_HOME: new URL('./lib/', import.meta.url).href,
    ANIMATION_DELAY_MS: 1000,
    CONTAINER_ID: "pyviz-output-animation-panel",
    BUTTON_ID: "btn-output-animation"
};
