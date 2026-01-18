/**
 * Focus Magnifier - Global Header Feature
 * Classroom/Presentation support for distant viewing
 * v2.0 - In-Place Zoom Approach
 * 
 * When enabled, clicked input elements zoom up with a larger version
 * that syncs back to the original element
 */

(function () {
    'use strict';

    // ==========================================
    // CONFIGURATION
    // ==========================================
    const CONFIG = {
        STORAGE_KEY: 'vdraw_focus_magnifier_enabled',
        SUPPORTED_SELECTORS: [
            'input[type="text"]',
            'input[type="number"]',
            'input[type="email"]',
            'input[type="password"]',
            'input[type="search"]',
            'input[type="url"]',
            'input[type="tel"]',
            'textarea',
            'select'
        ]
    };

    // ==========================================
    // STATE
    // ==========================================
    let isEnabled = false;
    let currentElement = null;
    let magnifiedWrapper = null;
    let magnifiedInput = null;
    let backdropEl = null;

    // ==========================================
    // UTILITY FUNCTIONS
    // ==========================================

    /**
     * Check if element is a supported input element
     */
    function isSupportedElement(el) {
        if (!el || el.nodeType !== 1) return false;
        const selector = CONFIG.SUPPORTED_SELECTORS.join(',');
        return el.matches && el.matches(selector);
    }

    /**
     * Resolve the best available label for an element
     * Priority: actual labels > aria > title > name > placeholder (last resort)
     */
    function resolveLabel(el) {
        if (!el) return 'Field';

        // 1. Check for explicit label via 'for' attribute (highest priority)
        if (el.id) {
            const labelEl = document.querySelector(`label[for="${el.id}"]`);
            if (labelEl && labelEl.textContent.trim()) {
                return labelEl.textContent.trim();
            }
        }

        // 2. Check for previous sibling that is a label or label-like text
        const prevSibling = el.previousElementSibling;
        if (prevSibling) {
            // Direct label sibling
            if (prevSibling.tagName === 'LABEL' && prevSibling.textContent.trim()) {
                return prevSibling.textContent.trim();
            }
            // Any text element before input (span, div, p with text)
            if (['SPAN', 'DIV', 'P', 'STRONG', 'B'].includes(prevSibling.tagName)) {
                const text = prevSibling.textContent.trim();
                if (text && text.length < 100) { // Reasonable label length
                    return text;
                }
            }
        }

        // 3. Check parent element for label text before this input
        const parent = el.parentElement;
        if (parent) {
            // Look for any label-like element in parent before this input
            const children = Array.from(parent.children);
            const myIndex = children.indexOf(el);
            for (let i = myIndex - 1; i >= 0; i--) {
                const sibling = children[i];
                if (['LABEL', 'SPAN', 'DIV', 'P', 'STRONG', 'B'].includes(sibling.tagName)) {
                    const text = sibling.textContent.trim();
                    if (text && text.length < 100 && text.length > 0) {
                        return text;
                    }
                }
            }
        }

        // 4. Check for wrapping label
        const parentLabel = el.closest('label');
        if (parentLabel) {
            // Get text content excluding the input itself
            const clone = parentLabel.cloneNode(true);
            const inputs = clone.querySelectorAll('input, textarea, select');
            inputs.forEach(inp => inp.remove());
            const text = clone.textContent.trim();
            if (text) return text;
        }

        // 5. Check for aria-label
        if (el.getAttribute('aria-label')) {
            return el.getAttribute('aria-label');
        }

        // 6. Check for title attribute
        if (el.title) {
            return el.title;
        }

        // 7. Check for name attribute (formatted to readable text)
        if (el.name) {
            return el.name
                .replace(/[-_]/g, ' ')
                .replace(/([a-z])([A-Z])/g, '$1 $2')
                .replace(/\b\w/g, l => l.toUpperCase());
        }

        // 8. LAST RESORT: Check for placeholder (lowest priority)
        if (el.placeholder) {
            return el.placeholder;
        }

        return 'Input Field';
    }

    // ==========================================
    // CREATE UI ELEMENTS
    // ==========================================

    /**
     * Create the backdrop element
     */
    function createBackdrop() {
        backdropEl = document.createElement('div');
        backdropEl.className = 'fm-backdrop';
        backdropEl.addEventListener('click', closeMagnifier);
        document.body.appendChild(backdropEl);
    }

    /**
     * Create the toggle button for the header
     */
    function createToggleButton() {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'focus-magnifier-toggle';
        toggleBtn.id = 'focus-magnifier-toggle';
        toggleBtn.setAttribute('aria-pressed', 'false');
        toggleBtn.setAttribute('title', 'Focus Magnifier - Zoom inputs for classroom viewing');
        toggleBtn.innerHTML = `
            <i class="fa-solid fa-magnifying-glass-plus fm-icon"></i>
            <span class="fm-label">Zoom</span>
        `;
        toggleBtn.addEventListener('click', toggleMagnifier);
        return toggleBtn;
    }

    // ==========================================
    // MAGNIFIER LOGIC
    // ==========================================

    /**
     * Open the magnified view for an element
     */
    function openMagnifier(originalEl) {
        if (!originalEl || magnifiedWrapper) return;

        currentElement = originalEl;
        const label = resolveLabel(originalEl);
        const tagName = originalEl.tagName.toLowerCase();

        // Highlight original
        originalEl.classList.add('fm-original-highlighted');

        // Show backdrop
        backdropEl.classList.add('visible');

        // Create wrapper
        magnifiedWrapper = document.createElement('div');
        magnifiedWrapper.className = 'fm-magnified-wrapper';

        // Add label
        const labelEl = document.createElement('span');
        labelEl.className = 'fm-magnified-label';
        labelEl.textContent = label;
        magnifiedWrapper.appendChild(labelEl);

        // Create magnified input based on element type
        if (tagName === 'select') {
            magnifiedInput = document.createElement('select');
            magnifiedInput.className = 'fm-magnified-select';
            // Copy options
            Array.from(originalEl.options).forEach(opt => {
                const newOpt = document.createElement('option');
                newOpt.value = opt.value;
                newOpt.textContent = opt.textContent;
                newOpt.selected = opt.selected;
                magnifiedInput.appendChild(newOpt);
            });
            magnifiedInput.value = originalEl.value;
            magnifiedInput.addEventListener('change', syncToOriginal);
        } else if (tagName === 'textarea') {
            magnifiedInput = document.createElement('textarea');
            magnifiedInput.className = 'fm-magnified-textarea';
            magnifiedInput.value = originalEl.value;
            magnifiedInput.placeholder = originalEl.placeholder || '';
            magnifiedInput.addEventListener('input', syncToOriginal);
        } else {
            // Regular input
            magnifiedInput = document.createElement('input');
            magnifiedInput.type = originalEl.type || 'text';
            magnifiedInput.className = 'fm-magnified-input';
            magnifiedInput.value = originalEl.value;
            magnifiedInput.placeholder = originalEl.placeholder || '';
            magnifiedInput.addEventListener('input', syncToOriginal);
        }

        magnifiedWrapper.appendChild(magnifiedInput);

        // Add close hint
        const hintEl = document.createElement('span');
        hintEl.className = 'fm-close-hint';
        hintEl.innerHTML = 'Press <kbd>Esc</kbd> or <kbd>Enter</kbd> to close';
        magnifiedWrapper.appendChild(hintEl);

        // Add to body
        document.body.appendChild(magnifiedWrapper);

        // Position at the SAME location as the original element (in-place zoom effect)
        const originalRect = originalEl.getBoundingClientRect();
        const wrapperRect = magnifiedWrapper.getBoundingClientRect();

        // Start with the original element's position
        let left = originalRect.left;
        let top = originalRect.top;

        // Adjust if zoom box would go off the right edge
        if (left + wrapperRect.width > window.innerWidth - 20) {
            left = window.innerWidth - wrapperRect.width - 20;
        }

        // Adjust if zoom box would go off the left edge
        if (left < 20) {
            left = 20;
        }

        // Adjust if zoom box would go off the bottom edge
        if (top + wrapperRect.height > window.innerHeight - 20) {
            top = window.innerHeight - wrapperRect.height - 20;
        }

        // Adjust if zoom box would go off the top edge
        if (top < 20) {
            top = 20;
        }

        magnifiedWrapper.style.left = `${left}px`;
        magnifiedWrapper.style.top = `${top}px`;

        // Focus the magnified input
        requestAnimationFrame(() => {
            magnifiedInput.focus();
            // Place cursor at end
            if (magnifiedInput.setSelectionRange && magnifiedInput.value) {
                const len = magnifiedInput.value.length;
                magnifiedInput.setSelectionRange(len, len);
            }
        });

        // Add keyboard listener
        magnifiedInput.addEventListener('keydown', handleMagnifiedKeydown);
    }

    /**
     * Close the magnified view
     */
    function closeMagnifier() {
        if (!magnifiedWrapper) return;

        // Final sync
        if (currentElement && magnifiedInput) {
            currentElement.value = magnifiedInput.value;
            // Trigger change event on original
            currentElement.dispatchEvent(new Event('input', { bubbles: true }));
            currentElement.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Remove highlight from original
        if (currentElement) {
            currentElement.classList.remove('fm-original-highlighted');
        }

        // Hide backdrop
        backdropEl.classList.remove('visible');

        // Remove with animation
        magnifiedWrapper.style.animation = 'fm-zoom-out 0.2s ease forwards';
        setTimeout(() => {
            if (magnifiedWrapper && magnifiedWrapper.parentNode) {
                magnifiedWrapper.parentNode.removeChild(magnifiedWrapper);
            }
            magnifiedWrapper = null;
            magnifiedInput = null;

            // Focus back on original element
            if (currentElement) {
                currentElement.focus();
            }
            currentElement = null;
        }, 200);
    }

    /**
     * Sync magnified input value back to original
     */
    function syncToOriginal() {
        if (currentElement && magnifiedInput) {
            currentElement.value = magnifiedInput.value;
            currentElement.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    /**
     * Handle keyboard events in magnified input
     */
    function handleMagnifiedKeydown(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            closeMagnifier();
        } else if (e.key === 'Enter' && magnifiedInput.tagName !== 'TEXTAREA') {
            e.preventDefault();
            closeMagnifier();
        }
    }

    // ==========================================
    // EVENT HANDLERS
    // ==========================================

    /**
     * Handle click events when magnifier is enabled
     */
    function handleClick(e) {
        if (!isEnabled) return;
        if (magnifiedWrapper) return; // Already showing one

        const targetEl = e.target;

        if (isSupportedElement(targetEl)) {
            e.preventDefault();
            e.stopPropagation();
            openMagnifier(targetEl);
        }
    }

    /**
     * Handle global escape key
     */
    function handleGlobalKeydown(e) {
        if (e.key === 'Escape' && magnifiedWrapper) {
            e.preventDefault();
            closeMagnifier();
        }
    }

    // ==========================================
    // TOGGLE MAGNIFIER
    // ==========================================

    /**
     * Toggle the Focus Magnifier mode
     */
    function toggleMagnifier(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        isEnabled = !isEnabled;

        const toggleBtn = document.getElementById('focus-magnifier-toggle');
        if (toggleBtn) {
            toggleBtn.classList.toggle('active', isEnabled);
            toggleBtn.setAttribute('aria-pressed', isEnabled.toString());
        }

        // Save state to session storage
        try {
            sessionStorage.setItem(CONFIG.STORAGE_KEY, isEnabled.toString());
        } catch (err) {
            console.warn('Focus Magnifier: Could not save state');
        }

        // If disabled, close any open magnifier
        if (!isEnabled && magnifiedWrapper) {
            closeMagnifier();
        }

        console.log(`Focus Magnifier: ${isEnabled ? 'Enabled' : 'Disabled'}`);
    }

    /**
     * Restore state from session storage
     */
    function restoreState() {
        try {
            const saved = sessionStorage.getItem(CONFIG.STORAGE_KEY);
            if (saved === 'true') {
                isEnabled = true;
                const toggleBtn = document.getElementById('focus-magnifier-toggle');
                if (toggleBtn) {
                    toggleBtn.classList.add('active');
                    toggleBtn.setAttribute('aria-pressed', 'true');
                }
            }
        } catch (err) {
            console.warn('Focus Magnifier: Could not restore state');
        }
    }

    // ==========================================
    // INITIALIZATION
    // ==========================================

    /**
     * Initialize the Focus Magnifier feature
     */
    function init() {
        // Create backdrop
        createBackdrop();

        // Find header and add toggle button
        const header = document.querySelector('header');
        if (header) {
            const authControls = header.querySelector('.flex.items-center.space-x-2, .flex.items-center.gap-2');
            if (authControls) {
                const toggleBtn = createToggleButton();
                authControls.insertBefore(toggleBtn, authControls.firstChild);
            } else {
                const toggleBtn = createToggleButton();
                header.appendChild(toggleBtn);
            }
        }

        // Restore saved state
        restoreState();

        // Setup event listeners
        document.addEventListener('click', handleClick, true);
        document.addEventListener('keydown', handleGlobalKeydown, true);

        // Initialize Screen Zoom
        initScreenZoom();

        console.log('Focus Magnifier v2.0: Initialized (In-Place Zoom + Screen Zoom)');
    }

    // ==========================================
    // SCREEN ZOOM FEATURE - Real Magnifying Glass
    // ==========================================

    let screenZoomEnabled = false;
    let screenZoomLens = null;
    let screenZoomContent = null;
    let refreshIntervalId = null; // Track the interval so we can clear it
    const LENS_SIZE = 200;
    const ZOOM_LEVEL = 2;
    const STORAGE_KEY_SCREEN = 'vdraw_screen_zoom_enabled';

    /**
     * Create Screen Zoom toggle button
     */
    function createScreenZoomButton() {
        const btn = document.createElement('button');
        btn.className = 'screen-zoom-toggle';
        btn.id = 'screen-zoom-toggle';
        btn.setAttribute('aria-pressed', 'false');
        btn.setAttribute('title', 'Screen Zoom - Real magnifying glass');
        btn.innerHTML = `
            <i class="fa-solid fa-search-plus sz-icon"></i>
            <span class="sz-label">Lens</span>
        `;
        btn.addEventListener('click', toggleScreenZoom);
        return btn;
    }

    /**
     * Create the lens element with cloned page content
     */
    function createLens() {
        // Create lens container
        screenZoomLens = document.createElement('div');
        screenZoomLens.id = 'screen-zoom-lens';
        screenZoomLens.style.cssText = `
            position: fixed;
            width: ${LENS_SIZE}px;
            height: ${LENS_SIZE}px;
            border: 4px solid #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 30px rgba(34, 197, 94, 0.5), 0 0 60px rgba(0, 0, 0, 0.5), inset 0 0 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            pointer-events: none;
            z-index: 100000;
            opacity: 0;
            transition: opacity 0.15s ease;
            background: #0f172a;
        `;

        // Create inner content container that will hold the zoomed page
        screenZoomContent = document.createElement('div');
        screenZoomContent.id = 'screen-zoom-content';
        screenZoomContent.style.cssText = `
            position: absolute;
            width: ${LENS_SIZE}px;
            height: ${LENS_SIZE}px;
            border-radius: 50%;
            overflow: hidden;
            pointer-events: none;
        `;
        screenZoomLens.appendChild(screenZoomContent);

        // Add crosshair
        const crosshairV = document.createElement('div');
        crosshairV.style.cssText = `
            position: absolute;
            width: 2px;
            height: 20px;
            background: rgba(34, 197, 94, 0.7);
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            pointer-events: none;
        `;
        screenZoomLens.appendChild(crosshairV);

        const crosshairH = document.createElement('div');
        crosshairH.style.cssText = `
            position: absolute;
            width: 20px;
            height: 2px;
            background: rgba(34, 197, 94, 0.7);
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            pointer-events: none;
        `;
        screenZoomLens.appendChild(crosshairH);

        document.body.appendChild(screenZoomLens);
    }

    /**
     * Toggle Screen Zoom mode
     */
    function toggleScreenZoom(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        screenZoomEnabled = !screenZoomEnabled;

        const btn = document.getElementById('screen-zoom-toggle');
        if (btn) {
            btn.classList.toggle('active', screenZoomEnabled);
            btn.setAttribute('aria-pressed', screenZoomEnabled.toString());
        }

        if (screenZoomEnabled) {
            screenZoomLens.style.opacity = '1';
            document.addEventListener('mousemove', updateScreenZoom);

            // Start the refresh interval
            if (!refreshIntervalId) {
                refreshIntervalId = setInterval(() => {
                    if (screenZoomEnabled && screenZoomContent) {
                        screenZoomContent.innerHTML = '';
                    }
                }, 2000);
            }
        } else {
            // Properly clean up when disabling
            screenZoomLens.style.opacity = '0';
            document.removeEventListener('mousemove', updateScreenZoom);

            // IMPORTANT: Clear the cloned content to prevent DOM interference
            if (screenZoomContent) {
                screenZoomContent.innerHTML = '';
            }

            // Clear the refresh interval
            if (refreshIntervalId) {
                clearInterval(refreshIntervalId);
                refreshIntervalId = null;
            }
        }

        // Save state
        try {
            sessionStorage.setItem(STORAGE_KEY_SCREEN, screenZoomEnabled.toString());
        } catch (err) { }

        console.log(`Screen Zoom: ${screenZoomEnabled ? 'Enabled' : 'Disabled'}`);
    }

    /**
     * Update the lens position and content
     */
    function updateScreenZoom(e) {
        if (!screenZoomEnabled || !screenZoomLens) return;

        const mouseX = e.clientX;
        const mouseY = e.clientY;

        // Position lens near cursor (offset up and to the right)
        let lensX = mouseX + 20;
        let lensY = mouseY - LENS_SIZE - 20;

        // Keep lens on screen
        if (lensX + LENS_SIZE > window.innerWidth - 10) {
            lensX = mouseX - LENS_SIZE - 20;
        }
        if (lensY < 10) {
            lensY = mouseY + 20;
        }
        if (lensX < 10) {
            lensX = 10;
        }
        if (lensY + LENS_SIZE > window.innerHeight - 10) {
            lensY = window.innerHeight - LENS_SIZE - 10;
        }

        screenZoomLens.style.left = `${lensX}px`;
        screenZoomLens.style.top = `${lensY}px`;

        // Update the zoomed content position
        // The idea: we have a clone of the body that is scaled up
        // We position it so that the cursor position appears at the center of the lens
        if (screenZoomContent.firstChild) {
            // Update existing clone position
            const clone = screenZoomContent.firstChild;
            // Calculate offset: we want mouseX,mouseY to appear at center of lens
            // After scaling by ZOOM_LEVEL, a point at (x,y) appears at (x*ZOOM_LEVEL, y*ZOOM_LEVEL)
            // We want this to be at the center of the lens (LENS_SIZE/2, LENS_SIZE/2)
            // So: x*ZOOM_LEVEL + offsetX = LENS_SIZE/2
            // offsetX = LENS_SIZE/2 - x*ZOOM_LEVEL
            const offsetX = LENS_SIZE / 2 - mouseX * ZOOM_LEVEL;
            const offsetY = LENS_SIZE / 2 - mouseY * ZOOM_LEVEL;
            clone.style.left = `${offsetX}px`;
            clone.style.top = `${offsetY}px`;
        } else {
            // Create the clone for the first time
            createZoomedBodyClone(mouseX, mouseY);
        }
    }

    /**
     * Create a clone of the body for the zoomed view
     */
    function createZoomedBodyClone(mouseX, mouseY) {
        // Clone the body
        const bodyClone = document.body.cloneNode(true);

        // Remove the lens from the clone
        const lensClone = bodyClone.querySelector('#screen-zoom-lens');
        if (lensClone) lensClone.remove();

        // Also remove any other overlay elements that shouldn't be in the zoom
        const overlays = bodyClone.querySelectorAll('.fm-backdrop, .fm-magnified-wrapper');
        overlays.forEach(el => el.remove());

        // Calculate the offset
        const offsetX = LENS_SIZE / 2 - mouseX * ZOOM_LEVEL;
        const offsetY = LENS_SIZE / 2 - mouseY * ZOOM_LEVEL;

        // Style the clone
        bodyClone.style.cssText = `
            position: absolute;
            left: ${offsetX}px;
            top: ${offsetY}px;
            width: ${window.innerWidth}px;
            height: ${window.innerHeight}px;
            margin: 0;
            padding: 0;
            transform: scale(${ZOOM_LEVEL});
            transform-origin: 0 0;
            pointer-events: none;
            overflow: visible;
            background: #0f172a;
        `;

        // Clear and add
        screenZoomContent.innerHTML = '';
        screenZoomContent.appendChild(bodyClone);
    }

    // NOTE: setupCloneRefresh was removed - interval is now managed inline in toggleScreenZoom()

    /**
     * Restore Screen Zoom state
     */
    function restoreScreenZoomState() {
        try {
            const saved = sessionStorage.getItem(STORAGE_KEY_SCREEN);
            if (saved === 'true') {
                setTimeout(() => {
                    toggleScreenZoom();
                }, 100);
            }
        } catch (err) { }
    }

    /**
     * Initialize Screen Zoom
     */
    function initScreenZoom() {
        // Create lens element
        createLens();

        // Add toggle button to header
        const header = document.querySelector('header');
        if (header) {
            const authControls = header.querySelector('.flex.items-center.space-x-2, .flex.items-center.gap-2');
            if (authControls) {
                const btn = createScreenZoomButton();
                const focusBtn = document.getElementById('focus-magnifier-toggle');
                if (focusBtn && focusBtn.nextSibling) {
                    authControls.insertBefore(btn, focusBtn.nextSibling);
                } else {
                    authControls.insertBefore(btn, authControls.firstChild);
                }
            }
        }

        // Restore saved state
        restoreScreenZoomState();

        // Note: Refresh interval is now managed in toggleScreenZoom() to prevent memory leaks
    }

    // ==========================================
    // PUBLIC API
    // ==========================================

    window.FocusMagnifier = {
        init,
        toggle: toggleMagnifier,
        open: openMagnifier,
        close: closeMagnifier,
        isEnabled: () => isEnabled
    };

    window.ScreenZoom = {
        toggle: toggleScreenZoom,
        isEnabled: () => screenZoomEnabled
    };

    // Auto-initialize on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

