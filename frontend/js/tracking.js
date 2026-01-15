/**
 * Phase 15 - Universal Tracking API
 * Handles usage analytics, live session heartbeats, and rewards tracking.
 * Fail-safe: No errors will block the UI.
 */
const VDrawTracking = (function () {
    let _appName = 'Unknown';
    let _heartbeatInterval = null;
    let _apiEndpoint = 'api/track.php'; // Default, will be overridden

    function getOS() {
        var userAgent = window.navigator.userAgent,
            platform = window.navigator.platform,
            macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'],
            windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'],
            iosPlatforms = ['iPhone', 'iPad', 'iPod'],
            os = null;

        if (macosPlatforms.indexOf(platform) !== -1) {
            os = 'Mac OS';
        } else if (iosPlatforms.indexOf(platform) !== -1) {
            os = 'iOS';
        } else if (windowsPlatforms.indexOf(platform) !== -1) {
            os = 'Windows';
        } else if (/Android/.test(userAgent)) {
            os = 'Android';
        } else if (!os && /Linux/.test(platform)) {
            os = 'Linux';
        }

        return os || 'Unknown';
    }

    /**
     * @param {string} type - 'visit', 'button', 'document', 'session'
     * @param {string} name - Event Name (e.g. 'Calculate')
     * @param {object} meta - Additional data
     */
    async function track(type, name, meta = {}) {
        const payload = {
            app_name: _appName,
            event_type: type,
            event_name: name,
            meta: {
                ...meta,
                os: getOS(),
                url: window.location.href,
                timestamp: new Date().toISOString()
            }
        };

        try {
            await fetch(_apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload),
                keepalive: true // Ensure request survives page unload
            });
        } catch (e) {
            // SILENT FAIL
        }
    }

    function init(appName, apiPath) {
        _appName = appName;
        if (apiPath) _apiEndpoint = apiPath;

        // 1. Track Visit
        // start tracking Visit api code
        track('visit', 'Page Load');
        // end tracking Visit api code

        // 2. Start Session Heartbeat (60s)
        if (_heartbeatInterval) clearInterval(_heartbeatInterval);

        // start tracking Session api code
        track('session', 'Session Start'); // Immediate first ping
        _heartbeatInterval = setInterval(() => {
            track('session', 'Heartbeat');
        }, 60000);
        // end tracking Session api code
    }

    return {
        init: init,
        track: track,
        appName: () => _appName
    };
})();

// Expose globally
window.track = VDrawTracking.track;
window.initTracking = VDrawTracking.init;
