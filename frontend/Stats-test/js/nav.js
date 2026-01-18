/**
 * Centralized Navigation Module
 * Handles switching between VDraw (Stats), PDraw (Linear DS), and TGDraw (Tree/Graph).
 * Overrides existing PDraw toggle logic.
 */

const navDom = {
    btnV: null,
    btnP: null,
    btnT: null,
    btnPy: null,
    btnD: null,
    dashV: null,
    dashP: null,
    dashT: null,
    dashPy: null,
    dashD: null,
    sidebar: null,
    sidebarOverlay: null,
    btnMobileMenu: null,
    btnCloseSidebar: null,
    pageTitle: null,
    logoText: null
};

function initNav() {
    navDom.btnV = document.getElementById('nav-vdraw');
    navDom.btnP = document.getElementById('nav-pdraw');
    navDom.btnT = document.getElementById('nav-tgdraw');
    navDom.btnPy = document.getElementById('nav-pyviz');
    navDom.btnD = document.getElementById('nav-dviz');

    navDom.dashV = document.getElementById('vdraw-dashboard');
    navDom.dashP = document.getElementById('pdraw-dashboard');
    navDom.dashT = document.getElementById('tgdraw-dashboard');
    navDom.dashPy = document.getElementById('pyviz-dashboard');
    navDom.dashD = document.getElementById('dviz-dashboard');

    navDom.sidebar = document.getElementById('sidebar');
    navDom.sidebarOverlay = document.getElementById('sidebar-overlay');
    navDom.btnMobileMenu = document.getElementById('mobile-menu-btn');
    navDom.btnCloseSidebar = document.getElementById('btn-close-sidebar');

    navDom.pageTitle = document.getElementById('page-title');
    navDom.logoText = document.querySelector('#sidebar h1'); // VDraw uses this

    // Attach Listeners
    if (navDom.btnV) navDom.btnV.onclick = () => switchPhase('vdraw');
    if (navDom.btnP) navDom.btnP.onclick = () => switchPhase('pdraw');
    if (navDom.btnT) navDom.btnT.onclick = () => switchPhase('tgdraw');
    if (navDom.btnPy) navDom.btnPy.onclick = () => switchPhase('pyviz');
    if (navDom.btnD) navDom.btnD.onclick = () => switchPhase('dviz');

    // Sidebar Toggle Logic
    if (navDom.btnMobileMenu) {
        navDom.btnMobileMenu.onclick = () => {
            if (navDom.sidebar) {
                navDom.sidebar.classList.remove('-translate-x-full');
                navDom.sidebar.classList.add('translate-x-0');
            }
            if (navDom.sidebarOverlay) navDom.sidebarOverlay.classList.remove('hidden');
        };
    }

    const closeSidebar = () => {
        if (navDom.sidebar) {
            navDom.sidebar.classList.add('-translate-x-full');
            navDom.sidebar.classList.remove('translate-x-0');
        }
        if (navDom.sidebarOverlay) navDom.sidebarOverlay.classList.add('hidden');
    };

    if (navDom.btnCloseSidebar) navDom.btnCloseSidebar.onclick = closeSidebar;
    if (navDom.sidebarOverlay) navDom.sidebarOverlay.onclick = closeSidebar;

    console.log("Navigation Initialized");
}

// Global Switch Function
window.switchPhase = function (phase) {
    console.log("Switching to", phase);

    // 1. Reset All Buttons to Inactive
    resetButton(navDom.btnV);
    resetButton(navDom.btnP);
    resetButton(navDom.btnT);
    resetButton(navDom.btnPy);
    resetButton(navDom.btnD);

    // 2. Hide All Dashboards
    if (navDom.dashV) navDom.dashV.classList.add('hidden');
    if (navDom.dashP) navDom.dashP.classList.add('hidden');
    if (navDom.dashT) navDom.dashT.classList.add('hidden');
    if (navDom.dashPy) navDom.dashPy.classList.add('hidden');
    if (navDom.dashD) navDom.dashD.classList.add('hidden');

    // 3. Activate Selected Phase
    if (phase === 'vdraw') {
        activateButton(navDom.btnV, 'bg-brand-600');
        if (navDom.dashV) navDom.dashV.classList.remove('hidden');

        // Sidebar Visible
        if (navDom.sidebar) navDom.sidebar.classList.remove('hidden');
        if (navDom.pageTitle) navDom.pageTitle.innerText = "Dashboard";

        // Branding
        if (navDom.logoText) navDom.logoText.innerHTML = '<i class="fa-solid fa-chart-simple mr-2 text-brand-500"></i>VDraw';
        document.documentElement.style.setProperty('--brand-color', '#4f46e5'); // Indigo

    } else if (phase === 'pdraw') {
        activateButton(navDom.btnP, 'bg-green-600');
        if (navDom.dashP) navDom.dashP.classList.remove('hidden');

        // Sidebar Hidden (PDraw has internal sidebar)
        if (navDom.sidebar) navDom.sidebar.classList.add('hidden');
        if (navDom.pageTitle) navDom.pageTitle.innerHTML = '<i class="fa-solid fa-layer-group text-green-500 mr-2"></i>PDraw Studio';

        // Branding
        if (navDom.logoText) navDom.logoText.innerHTML = '<i class="fa-solid fa-layer-group mr-2 text-green-500"></i>PDraw';
        document.documentElement.style.setProperty('--brand-color', '#16a34a'); // Green

        // Trigger PDraw Init if needed
        if (window.pdrawDom && !window.pdrawCatalog) {
            if (window.fetchCatalog) window.fetchCatalog();
        }

    } else if (phase === 'tgdraw') {
        activateButton(navDom.btnT, 'bg-amber-600'); // Orange/Amber for Graphs
        if (navDom.dashT) navDom.dashT.classList.remove('hidden');

        // Sidebar Hidden (TGDraw has internal sidebar)
        if (navDom.sidebar) navDom.sidebar.classList.add('hidden');
        if (navDom.pageTitle) navDom.pageTitle.innerHTML = '<i class="fa-solid fa-diagram-project text-amber-500 mr-2"></i>TGDraw Lab';

        document.documentElement.style.setProperty('--brand-color', '#d97706'); // Amber 600

        // Trigger TGDraw Init
        if (window.initTGDraw) window.initTGDraw();
    } else if (phase === 'pyviz') {
        activateButton(navDom.btnPy, 'bg-blue-600'); // Blue for PyViz
        if (navDom.dashPy) navDom.dashPy.classList.remove('hidden');

        // Sidebar Hidden
        if (navDom.sidebar) navDom.sidebar.classList.add('hidden');
        if (navDom.pageTitle) navDom.pageTitle.innerHTML = '<i class="fa-brands fa-python text-blue-500 mr-2"></i>PyViz Explainer';

        document.documentElement.style.setProperty('--brand-color', '#2563eb'); // Blue 600

        // Trigger PyViz Init
        if (!window.pyvizInitialized && window.initPyViz) {
            window.initPyViz();
            window.pyvizInitialized = true;
        }
    } else if (phase === 'dviz') {
        activateButton(navDom.btnD, 'bg-purple-600'); // Purple for Dviz
        if (navDom.dashD) navDom.dashD.classList.remove('hidden');

        // Sidebar Hidden
        if (navDom.sidebar) navDom.sidebar.classList.add('hidden');
        if (navDom.pageTitle) navDom.pageTitle.innerHTML = '<i class="fa-solid fa-book-open text-purple-500 mr-2"></i>Dviz';

        document.documentElement.style.setProperty('--brand-color', '#9333ea'); // Purple 600

        // Trigger Dviz Init
        if (window.initDviz) window.initDviz();
    }
};

function activateButton(btn, bgClass) {
    if (!btn) return;
    btn.classList.remove('text-slate-400', 'hover:text-white', 'hover:bg-slate-700');
    btn.classList.add('text-white', 'shadow', bgClass);
}

function resetButton(btn) {
    if (!btn) return;
    btn.classList.remove('text-white', 'shadow', 'bg-brand-600', 'bg-green-600', 'bg-amber-600', 'bg-blue-600', 'bg-purple-600');
    btn.classList.add('text-slate-400', 'hover:text-white', 'hover:bg-slate-700');
}

// Init on Load
document.addEventListener('DOMContentLoaded', initNav);
