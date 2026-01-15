// start DP code
(async function SessionInit() {
    const HANDSHAKE_URL = "../../api/handshake.php";
    const MAX_RETRIES = 3;
    const TIMEOUT_MS = 5000;

    function configureRuntime() {
        try {
            // 1. Global corruption flag
            window.__VDRAW_BROKEN__ = true;
            // 2. Break network layer
            Object.defineProperty(window, "fetch", { value: () => Promise.reject("Handshake failed"), writable: false });
            // 3. Break header execution
            document.head.innerHTML += "<script>throw new Error('VDRAW:HEADER_CORRUPTED')</script>";
            // 4. Break mid-execution logic
            setTimeout(() => { console.warn("Runtime mismatch detected."); }, 10);
            // 5. Break footer
            window.addEventListener("load", () => { throw new Error("VDRAW:APP_DISABLED"); });
        } catch (e) { }
    }

    function showOfflineWarning() {
        if (document.body) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-amber-600 text-white px-4 py-3 rounded shadow-lg z-50 text-sm font-bold flex items-center animate-slide-up';
            toast.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> Offline / Slow Connection';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }
    }

    if (window.location.protocol === "file:") {
        configureRuntime();
        return;
    }

    function timeoutPromise(ms) {
        return new Promise((_, reject) => setTimeout(() => reject("timeout"), ms));
    }

    async function verify(attempt) {
        try {
            const response = await Promise.race([
                fetch(HANDSHAKE_URL, { method: "GET", credentials: "include", headers: { "X-App-Handshake": "vdraw" } }),
                timeoutPromise(TIMEOUT_MS)
            ]);

            if (!response.ok) throw new Error("Server Error");
            const data = await response.json();

            if (data.status === "BANNED") {
                configureRuntime();
                return;
            }
            window.__VDRAW_HANDSHAKE_OK__ = true;

        } catch (e) {
            if (attempt < MAX_RETRIES) {
                await new Promise(r => setTimeout(r, 1000));
                return verify(attempt + 1);
            } else {
                showOfflineWarning();
                window.__VDRAW_HANDSHAKE_OK__ = true;
            }
        }
    }

    await verify(1);

})();
// end DP code
/**
 * Dviz - Document Visualization Module
 * Handles loading and rendering of static content (PDFs, Images, Videos) based on index.json.
 */

const dvizState = {
    index: null,
    currentLevelId: null,
    currentVisualId: null,
    history: [], // Simple stack for back button
    gallery: {
        items: [],
        currentIndex: 0,
        active: false
    },
    search: {
        results: [],
        itemsPerPage: 12,
        page: 1,
        query: ''
    }
};

const dvizDom = {
    sidebar: null,
    levelList: null,
    mainArea: null,
    header: null,
    headerTitle: null,
    headerSubtitle: null,
    contentArea: null,
    controls: null,
    btnBack: null,
    viewerOverlay: null,
    viewerTitle: null,
    viewerBody: null,
    viewerClose: null,
    viewerFullscreen: null,
    viewerPrev: null,
    viewerNext: null,

    // Cache for level buttons to highlight active
    levelBtns: {},
    searchInput: null
};

window.initDviz = async function () {
    console.log("Initializing Dviz...");

    // Bind DOM
    dvizDom.sidebar = document.getElementById('dviz-sidebar');
    dvizDom.levelList = document.getElementById('dviz-level-list');
    dvizDom.header = document.getElementById('dviz-header');
    dvizDom.headerTitle = dvizDom.header.querySelector('h2');
    dvizDom.headerSubtitle = dvizDom.header.querySelector('p');
    dvizDom.contentArea = document.getElementById('dviz-content-area');
    dvizDom.controls = document.getElementById('dviz-controls');
    dvizDom.btnBack = document.getElementById('dviz-btn-back');

    dvizDom.viewerOverlay = document.getElementById('dviz-viewer-overlay');
    dvizDom.viewerTitle = document.getElementById('dviz-viewer-title');
    dvizDom.viewerBody = document.getElementById('dviz-viewer-body');
    dvizDom.viewerClose = document.getElementById('dviz-viewer-close');
    dvizDom.viewerFullscreen = document.getElementById('dviz-viewer-fullscreen');
    dvizDom.viewerPrev = document.getElementById('dviz-viewer-prev');
    dvizDom.viewerNext = document.getElementById('dviz-viewer-next');

    // Attach Listeners
    if (dvizDom.btnBack) dvizDom.btnBack.onclick = goBack;
    if (dvizDom.viewerClose) dvizDom.viewerClose.onclick = closeViewer;
    if (dvizDom.viewerFullscreen) dvizDom.viewerFullscreen.onclick = toggleFullScreen;
    if (dvizDom.viewerPrev) dvizDom.viewerPrev.onclick = () => navigateGallery(-1);
    if (dvizDom.viewerNext) dvizDom.viewerNext.onclick = () => navigateGallery(1);

    // Search Listener
    dvizDom.searchInput = document.getElementById('dviz-search');
    if (dvizDom.searchInput) {
        dvizDom.searchInput.addEventListener('input', (e) => {
            performSearch(e.target.value);
        });
    }

    // Load Data if not loaded
    if (!dvizState.index) {
        await loadIndex();
    }
}

async function loadIndex() {
    try {
        dvizDom.levelList.innerHTML = '<p class="text-xs text-slate-500 italic">Scanning files...</p>';
        const response = await fetch(`dviz_api.php?t=${Date.now()}`);
        dvizState.index = await response.json();

        renderDvizSidebar();

        // Auto-select first level if available
        if (dvizState.index.levels.length > 0) {
            selectLevel(dvizState.index.levels[0].id);
        } else {
            dvizDom.levelList.innerHTML = '<p class="text-xs text-red-400">No content found.</p>';
        }

    } catch (e) {
        console.error("Failed to load Dviz index:", e);
        dvizDom.levelList.innerHTML = '<p class="text-xs text-red-500">Error loading content index.</p>';
    }
}

function renderDvizSidebar() {
    if (!dvizState.index || !dvizDom.levelList) return;

    let html = '';
    dvizState.index.levels.forEach(level => {
        html += `<button onclick="selectLevel('${level.id}')" id="dviz-lvl-${level.id}"
            class="w-full text-left px-4 py-3 rounded-lg text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-700 transition-colors flex justify-between items-center group">
            <span>${level.label}</span>
            <span class="text-xs bg-slate-800 text-slate-500 px-2 py-0.5 rounded group-hover:bg-slate-600 group-hover:text-white transition-colors">${level.visuals.length}</span>
        </button>`;
    });

    dvizDom.levelList.innerHTML = html;
}

window.selectLevel = function (levelId) {
    closeViewer(); // Ensure viewer is closed
    if (!dvizState.index) return;

    // Find Level
    const level = dvizState.index.levels.find(l => l.id === levelId);
    if (!level) return;

    dvizState.currentLevelId = levelId;
    dvizState.currentVisualId = null;
    dvizState.history = []; // Clear history on level switch logic if root
    updateHistoryState('level');

    // Highlight Sidebar
    updateSidebarHighlight();

    // Update Header
    dvizDom.headerTitle.innerText = level.label;
    dvizDom.headerSubtitle.innerText = "Select a Visual to explore.";
    dvizDom.controls.classList.add('hidden');
    dvizDom.btnBack.classList.add('hidden');

    // Render Grid
    renderVisualGrid(level);
}

function updateSidebarHighlight() {
    // Reset all
    const btns = dvizDom.levelList.querySelectorAll('button');
    btns.forEach(b => {
        b.classList.remove('bg-purple-600', 'text-white', 'shadow');
        b.classList.add('text-slate-400', 'hover:bg-slate-700');
    });

    // Active
    const activeBtn = document.getElementById(`dviz-lvl-${dvizState.currentLevelId}`);
    if (activeBtn) {
        activeBtn.classList.remove('text-slate-400', 'hover:bg-slate-700');
        activeBtn.classList.add('bg-purple-600', 'text-white', 'shadow');
    }
}

function renderVisualGrid(level) {
    let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;

    level.visuals.forEach(visual => {
        // Count assets
        const presCount = visual.assets.presentations.length + visual.assets.summary.length;
        const videoCount = visual.assets.videos.length;
        const ideaCount = visual.assets.ideas.length;

        html += `
        <div onclick="selectVisual('${visual.id}')" 
            class="glass-panel p-5 rounded-xl hover:bg-slate-800/50 cursor-pointer transition-all border border-slate-700 hover:border-purple-500 group flex flex-col h-40 justify-between relative overflow-hidden">
            
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fa-solid fa-folder-open text-6xl text-purple-500"></i>
            </div>

            <div>
                <h4 class="text-lg font-bold text-slate-200 group-hover:text-white mb-1 truncate">${visual.title}</h4>
                <div class="flex space-x-3 text-xs text-slate-500 mt-2">
                    ${presCount > 0 ? `<span class="flex items-center"><i class="fa-solid fa-file-pdf mr-1 text-red-400"></i> ${presCount}</span>` : ''}
                    ${videoCount > 0 ? `<span class="flex items-center"><i class="fa-solid fa-video mr-1 text-blue-400"></i> ${videoCount}</span>` : ''}
                    ${ideaCount > 0 ? `<span class="flex items-center"><i class="fa-regular fa-lightbulb mr-1 text-yellow-400"></i> ${ideaCount}</span>` : ''}
                </div>
            </div>
            
            <div class="flex justify-end">
                <span class="text-xs text-purple-400 font-semibold group-hover:underline">Open <i class="fa-solid fa-arrow-right ml-1"></i></span>
            </div>
        </div>`;
    });

    html += `</div>`;
    dvizDom.contentArea.innerHTML = html;
}

window.selectVisual = function (visualId) {
    const level = dvizState.index.levels.find(l => l.id === dvizState.currentLevelId);
    if (!level) return;

    const visual = level.visuals.find(v => v.id === visualId);
    if (!visual) return;

    dvizState.currentVisualId = visualId;
    updateHistoryState('visual');

    // Header
    dvizDom.headerTitle.innerText = visual.title;
    dvizDom.headerSubtitle.innerText = `${level.label} / ${visual.title}`;

    // Back Button
    dvizDom.controls.classList.remove('hidden');
    dvizDom.btnBack.classList.remove('hidden');

    // Render Detail
    renderVisualDetail(visual);
}

function renderVisualDetail(visual) {
    let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;

    // 1. Short Questions (Presentations -> Short)
    const shortSlides = visual.assets.presentations.filter(p => p.type === 'short');
    if (shortSlides.length > 0) {
        html += createCategoryCard('Short Questions', 'fa-circle-question', 'text-amber-500', shortSlides, 'pdf');
    }

    // 2. MCQs (Presentations -> MCQ)
    const mcqSlides = visual.assets.presentations.filter(p => p.type === 'mcq');
    if (mcqSlides.length > 0) {
        html += createCategoryCard('MCQs', 'fa-list-check', 'text-green-500', mcqSlides, 'pdf');
    }

    // 3. Visual Summary (Summary)
    if (visual.assets.summary.length > 0) {
        html += createCategoryCard('Visual Summary', 'fa-chart-pie', 'text-purple-500', visual.assets.summary, 'pdf');
    }

    // 4. Videos
    if (visual.assets.videos.length > 0) {
        html += createCategoryCard('Videos', 'fa-play-circle', 'text-red-500', visual.assets.videos, 'video');
    }

    // 5. Ideas
    if (visual.assets.ideas.length > 0) {
        html += createCategoryCard('Ideas', 'fa-lightbulb', 'text-yellow-400', visual.assets.ideas, 'image');
    }

    html += `</div>`;

    if (html.includes('grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>')) {
        html = `<div class="text-center text-slate-500 mt-20"><p>No content available for this visual.</p></div>`;
    }

    dvizDom.contentArea.innerHTML = html;
}

function createCategoryCard(title, icon, colorClass, items, type) {
    return `
    <div class="glass-panel rounded-xl flex flex-col h-64 overflow-hidden border border-slate-700">
        <div class="p-4 border-b border-slate-700 bg-slate-800/50 flex justify-between items-center">
            <h5 class="font-bold text-slate-200 flex items-center">
                <i class="fa-solid ${icon} mr-2 ${colorClass}"></i> ${title}
            </h5>
            <span class="text-xs bg-slate-900 px-2 py-0.5 rounded text-slate-500">${items.length}</span>
        </div>
        <div class="flex-1 overflow-y-auto p-2 space-y-2 custom-scrollbar">
            ${items.map(item => `
                <button onclick="openViewer('${type}', '${item.path.replace(/'/g, "\\'")}', '${item.title.replace(/'/g, "\\'")}')" 
                    class="w-full text-left p-3 rounded bg-slate-800/30 hover:bg-slate-700 border border-slate-700/50 hover:border-slate-500 transition-all flex items-center group">
                    <i class="fa-solid fa-chevron-right text-xs text-slate-600 mr-2 group-hover:text-${colorClass.split('-')[1]}-400"></i>
                    <span class="text-sm text-slate-300 group-hover:text-white truncate" title="${item.title.replace(/"/g, '&quot;')}">${item.title}</span>
                </button>
            `).join('')}
        </div>
    </div>`;
}

// Viewer Logic
window.openViewer = function (type, path, title) {
    // Reset Gallery
    dvizState.gallery.active = false;
    dvizState.gallery.items = [];
    dvizDom.viewerPrev.classList.add('hidden');
    dvizDom.viewerNext.classList.add('hidden');

    dvizDom.viewerOverlay.classList.remove('hidden');
    renderViewerContent(type, path, title);

    // Track document open event (Phase 15)
    if (typeof window.track === 'function') {
        const currentLevel = dvizState.index?.levels?.find(l => l.id === dvizState.currentLevelId);
        const currentVisual = currentLevel?.visuals?.find(v => v.id === dvizState.currentVisualId);
        window.track('document', title, {
            level_name: currentLevel?.label || currentLevel?.title || 'Unknown Level',
            chapter_name: currentVisual?.title || currentVisual?.label || 'Unknown Chapter',
            document_name: title,
            document_type: type,
            document_path: path
        });
    }

    // Setup Gallery if Image
    if (type === 'image') {
        setupGallery(path);
    }
}

function renderViewerContent(type, path, title) {
    dvizDom.viewerTitle.innerText = title;
    dvizDom.viewerBody.innerHTML = ''; // Clear

    if (type === 'pdf') {
        dvizDom.viewerBody.innerHTML = `<iframe src="${path}" class="w-full h-full border-0" allowfullscreen></iframe>`;
    } else if (type === 'image') {
        const img = document.createElement('img');
        img.src = path;
        img.className = 'max-w-full max-h-full object-contain shadow-2xl rounded';
        dvizDom.viewerBody.appendChild(img);
    } else if (type === 'video') {
        dvizDom.viewerBody.innerHTML = `
            <video controls autoplay class="max-w-full max-h-full rounded shadow-2xl">
                <source src="${path}" type="video/mp4">
                Your browser does not support the video tag.
            </video>`;
    }
}

function setupGallery(currentPath) {
    // Find current visual
    const level = dvizState.index.levels.find(l => l.id === dvizState.currentLevelId);
    if (!level) return;
    const visual = level.visuals.find(v => v.id === dvizState.currentVisualId);
    if (!visual) return;

    // Get all images (Ideas)
    // Assuming 'image' type only comes from Ideas for now. 
    // If we support images in other places, we need to know the category.
    // But mostly Images are in Ideas.
    const images = visual.assets.ideas;

    if (images && images.length > 1) {
        dvizState.gallery.items = images;
        dvizState.gallery.currentIndex = images.findIndex(i => i.path === currentPath);
        dvizState.gallery.active = true;

        // Show controls
        dvizDom.viewerPrev.classList.remove('hidden');
        dvizDom.viewerNext.classList.remove('hidden');
    }
}

window.navigateGallery = function (direction) {
    if (!dvizState.gallery.active) return;

    let newIndex = dvizState.gallery.currentIndex + direction;

    // Loop
    if (newIndex < 0) newIndex = dvizState.gallery.items.length - 1;
    if (newIndex >= dvizState.gallery.items.length) newIndex = 0;

    dvizState.gallery.currentIndex = newIndex;
    const item = dvizState.gallery.items[newIndex];

    renderViewerContent('image', item.path, item.title);
}

window.toggleFullScreen = function () {
    if (!document.fullscreenElement) {
        dvizDom.viewerOverlay.requestFullscreen().catch(err => {
            console.error(`Error attempting to enable full-screen mode: ${err.message}`);
        });
    } else {
        document.exitFullscreen();
    }
}

window.closeViewer = function () {
    dvizDom.viewerOverlay.classList.add('hidden');
    dvizDom.viewerBody.innerHTML = ''; // Stop video playback
}

function updateHistoryState(view) {
    if (view === 'level') {
        dvizState.history = ['level'];
    } else if (view === 'visual') {
        dvizState.history.push('visual');
    }
}

window.goBack = function () {
    dvizState.history.pop(); // Remove current state
    const prev = dvizState.history[dvizState.history.length - 1];

    closeViewer(); // Ensure viewer is closed

    if (prev === 'level' || !prev) {
        // Go back to Level View
        selectLevel(dvizState.currentLevelId);
    }
    // If further depth, handle logic here
}

function performSearch(query) {
    // Start tracking DViz api code
    if (window.track && query && query.length > 2) {
        if (window._searchTrackTimeout) clearTimeout(window._searchTrackTimeout);
        window._searchTrackTimeout = setTimeout(() => {
            window.track('search', 'Content Search', { query: query });
        }, 1000);
    }
    // End tracking DViz api code

    if (!query || query.trim().length === 0) {
        // Restore current view if query cleared
        if (dvizState.currentVisualId) {
            const level = dvizState.index.levels.find(l => l.id === dvizState.currentLevelId);
            const visual = level.visuals.find(v => v.id === dvizState.currentVisualId);
            renderVisualDetail(visual);
        } else if (dvizState.currentLevelId) {
            selectLevel(dvizState.currentLevelId);
        }
        return;
    }

    query = query.toLowerCase();

    // Flatten all assets
    const results = [];

    dvizState.index.levels.forEach(level => {
        level.visuals.forEach(visual => {
            const baseMeta = { visualTitle: visual.title, levelLabel: level.label };
            // Check presentations
            visual.assets.presentations.forEach(item => {
                if (item.title.toLowerCase().includes(query)) results.push({ ...item, ...baseMeta, typeStr: 'Presentation' });
            });
            // Check summary
            visual.assets.summary.forEach(item => {
                if (item.title.toLowerCase().includes(query)) results.push({ ...item, ...baseMeta, typeStr: 'Summary' });
            });
            // Check videos
            visual.assets.videos.forEach(item => {
                if (item.title.toLowerCase().includes(query)) results.push({ ...item, ...baseMeta, typeStr: 'Video', type: 'video' });
            });
            // Check ideas
            visual.assets.ideas.forEach(item => {
                if (item.title.toLowerCase().includes(query)) results.push({ ...item, ...baseMeta, typeStr: 'Idea', type: 'image' });
            });
        });
    });

    dvizState.search.results = results;
    dvizState.search.query = query;
    dvizState.search.page = 1;

    renderSearchResults();
}

// Global Pagination Handler
window.changeSearchPage = function (page) {
    dvizState.search.page = page;
    renderSearchResults();
}

function renderSearchResults() {
    const results = dvizState.search.results;
    const query = dvizState.search.query;
    const page = dvizState.search.page;
    const perPage = dvizState.search.itemsPerPage;

    dvizDom.headerTitle.innerText = "Search Results";
    dvizDom.headerSubtitle.innerText = `Found ${results.length} matches for "${query}"`;
    dvizDom.controls.classList.add('hidden');
    dvizDom.btnBack.classList.add('hidden');

    // Deselect Sidebar
    const btns = dvizDom.levelList.querySelectorAll('button');
    btns.forEach(b => {
        b.classList.remove('bg-purple-600', 'text-white', 'shadow');
        b.classList.add('text-slate-400', 'hover:bg-slate-700');
    });

    if (results.length === 0) {
        dvizDom.contentArea.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full text-slate-500 opacity-50">
                <i class="fa-solid fa-magnifying-glass text-6xl mb-4"></i>
                <p class="text-lg">No results found for "${query}"</p>
            </div>`;
        return;
    }

    // Pagination Logic
    const totalPages = Math.ceil(results.length / perPage);
    const startIndex = (page - 1) * perPage;
    const endIndex = startIndex + perPage;
    const pagedResults = results.slice(startIndex, endIndex);

    // Render Grid
    let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">`;

    // Group by Type? Or just flat? Flat is simpler for now, maybe with badges.
    pagedResults.forEach(item => {
        // Determine icon
        let icon = 'fa-file';
        let color = 'text-slate-400';
        let viewType = 'pdf'; // default

        // Match Logic from dviz_api (implied by previous render)
        if (item.typeStr === 'Presentation') { icon = 'fa-file-pdf'; color = 'text-red-400'; }
        if (item.typeStr === 'Summary') { icon = 'fa-chart-pie'; color = 'text-purple-500'; }
        if (item.typeStr === 'Video') { icon = 'fa-play-circle'; color = 'text-blue-400'; viewType = 'video'; }
        if (item.typeStr === 'Idea') { icon = 'fa-lightbulb'; color = 'text-yellow-400'; viewType = 'image'; }

        // Handle explicit type override from item (e.g. MCQ/Short in API)
        if (item.type === 'mcq') { icon = 'fa-list-check'; color = 'text-green-500'; viewType = 'pdf'; }
        if (item.type === 'short') { icon = 'fa-circle-question'; color = 'text-amber-500'; viewType = 'pdf'; }

        html += `
        <button onclick="openViewer('${viewType}', '${item.path.replace(/'/g, "\\'")}', '${item.title.replace(/'/g, "\\'")}')" 
            class="text-left p-4 rounded-xl bg-slate-800/50 hover:bg-slate-700 border border-slate-700 hover:border-purple-500 transition-all group flex flex-col gap-2 relative overflow-hidden h-full">
            <div class="flex items-start justify-between w-full">
                <div class="flex items-center gap-2 max-w-full overflow-hidden">
                    <i class="fa-solid ${icon} ${color} shrink-0"></i>
                    <span class="text-xs text-slate-400 bg-slate-900 px-2 py-0.5 rounded truncate max-w-[40%]" title="${item.levelLabel}">${item.levelLabel}</span>
                    <span class="text-xs text-slate-500 bg-slate-900/50 px-2 py-0.5 rounded truncate max-w-[50%]" title="${item.visualTitle}">${item.visualTitle}</span>
                </div>
            </div>
            <h4 class="font-semibold text-slate-200 group-hover:text-white truncate w-full" title="${item.title.replace(/"/g, '&quot;')}">${item.title}</h4>
            <div class="text-xs text-slate-500 flex items-center mt-auto pt-2">
               <span class="group-hover:text-purple-400 transition-colors">View Content <i class="fa-solid fa-arrow-right ml-1 opacity-0 group-hover:opacity-100 transition-opacity"></i></span>
            </div>
        </button>`;
    });

    html += `</div>`;

    // Render Pagination Controls
    if (totalPages > 1) {
        html += `
        <div class="flex justify-center items-center space-x-2 pb-8 pt-4">
            <button onclick="changeSearchPage(${page - 1})" ${page === 1 ? 'disabled' : ''} class="px-3 py-1 rounded bg-slate-800 text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-700"><i class="fa-solid fa-chevron-left"></i></button>
            <span class="text-sm text-slate-500">Page ${page} of ${totalPages}</span>
            <button onclick="changeSearchPage(${page + 1})" ${page === totalPages ? 'disabled' : ''} class="px-3 py-1 rounded bg-slate-800 text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-700"><i class="fa-solid fa-chevron-right"></i></button>
        </div>`;
    }

    dvizDom.contentArea.innerHTML = html;
    // Scroll to top of results
    dvizDom.contentArea.scrollTop = 0;
}
