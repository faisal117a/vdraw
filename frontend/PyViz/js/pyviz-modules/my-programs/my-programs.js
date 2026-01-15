/**
 * My Programs Module
 * Phase 9
 * Handles the Modal, Search, and Insertion of predefined programs.
 */

const MyPrograms = {
    // State
    currentPage: 1,
    itemsPerPage: 10,
    filteredData: [],
    searchQuery: "",

    init: function () {
        console.log("MyPrograms.init() called");
        // Wait for DOM to be ready if needed, or just run
        // Ensure data is loaded
        if (window.MY_PROGRAMS_DATA) {
            this.filteredData = [...window.MY_PROGRAMS_DATA];
            console.log("MyPrograms data loaded:", this.filteredData.length);
        } else {
            console.error("MY_PROGRAMS_DATA not found on window.");
            // Try to find it if it was declared as const/var in global scope (unlikely if not on window)
            if (typeof MY_PROGRAMS_DATA !== 'undefined') {
                this.filteredData = [...MY_PROGRAMS_DATA];
            } else {
                this.filteredData = [];
            }
        }

        // Setup Button Listener - Handled via onclick in HTML or ID
        // const btn = document.getElementById('pyviz-btn-my-programs');
        // if (btn) {
        //    btn.onclick = () => this.open();
        // }

        this.createModal();
    },

    createModal: function () {
        if (document.getElementById('my-programs-modal')) return;
        console.log("Creating MyPrograms modal...");

        try {
            const modal = document.createElement('div');
            modal.id = 'my-programs-modal';
            modal.className = 'fixed inset-0 z-[100] flex items-center justify-center bg-black/50 hidden backdrop-blur-sm animate-fade-in';
            modal.innerHTML = `
                <div class="bg-slate-800 rounded-lg shadow-2xl border border-slate-600 w-[800px] h-[700px] flex flex-col overflow-hidden animate-slide-up transform transition-all">
                    <!-- Header -->
                    <div class="p-4 border-b border-slate-700 flex justify-between items-center bg-slate-900">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-book-open text-blue-500"></i> My Programs
                        </h3>
                        <button onclick="MyPrograms.close()" class="text-slate-400 hover:text-white transition-colors">
                            <i class="fa-solid fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="p-4 bg-slate-800 border-b border-slate-700">
                        <div class="relative">
                            <i class="fa-solid fa-search absolute left-3 top-3.5 text-slate-500"></i>
                            <input type="text" id="mp-search" oninput="MyPrograms.onSearch(this.value)" 
                                class="w-full bg-slate-900 border border-slate-600 rounded-lg py-2 pl-10 pr-4 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none transition-colors" 
                                placeholder="Search programs by title, keywords, or code...">
                        </div>
                    </div>

                    <!-- List -->
                    <div class="flex-1 overflow-auto p-4 custom-scrollbar bg-slate-900/50 space-y-3" id="mp-list">
                        <!-- Programs injected here -->
                    </div>

                    <!-- Footer / Pagination -->
                    <div class="p-3 border-t border-slate-700 bg-slate-900 flex justify-between items-center text-xs text-slate-400">
                        <span id="mp-count">Showing 0-0 of 0</span>
                        <div class="flex gap-2">
                            <button onclick="MyPrograms.prevPage()" id="mp-btn-prev" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 rounded border border-slate-600 text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors">Previous</button>
                            <span id="mp-page-num" class="px-3 py-1 bg-slate-800 rounded border border-slate-700 font-mono text-white">1</span>
                            <button onclick="MyPrograms.nextPage()" id="mp-btn-next" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 rounded border border-slate-600 text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors">Next</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            console.log("Modal created and appended to body");
        } catch (e) {
            console.error("Error creating modal:", e);
        }
    },

    open: function () {
        console.log("MyPrograms.open() called");
        const modal = document.getElementById('my-programs-modal');
        if (modal) {
            modal.classList.remove('hidden');
            this.reset();
        } else {
            console.error("Modal not found in DOM! Re-creating...");
            this.createModal();
            const retry = document.getElementById('my-programs-modal');
            if (retry) {
                retry.classList.remove('hidden');
                this.reset();
            } else {
                alert("Failed to load My Programs Interface.");
            }
        }
    },

    close: function () {
        const modal = document.getElementById('my-programs-modal');
        if (modal) modal.classList.add('hidden');
    },

    reset: function () {
        this.currentPage = 1;
        this.searchQuery = "";
        this.filteredData = [...(window.MY_PROGRAMS_DATA || [])].sort((a, b) => {
            // Extract number from ID for sorting prog_01 -> 1
            const nA = parseInt(a.id.replace('prog_', ''));
            const nB = parseInt(b.id.replace('prog_', ''));
            return nA - nB;
        });
        const searchInput = document.getElementById('mp-search');
        if (searchInput) searchInput.value = "";
        this.render();
    },

    onSearch: function (query) {
        this.searchQuery = query.toLowerCase();
        const allData = window.MY_PROGRAMS_DATA || [];

        if (!this.searchQuery) {
            this.filteredData = [...allData];
        } else {
            this.filteredData = allData.filter(p => {
                const content = (p.title + " " + p.keywords.join(" ") + " " + p.code).toLowerCase();
                return content.includes(this.searchQuery);
            });
        }
        this.currentPage = 1;
        this.render();
    },

    render: function () {
        const list = document.getElementById('mp-list');
        const countSpan = document.getElementById('mp-count');
        const pageSpan = document.getElementById('mp-page-num');
        const btnPrev = document.getElementById('mp-btn-prev');
        const btnNext = document.getElementById('mp-btn-next');

        list.innerHTML = "";

        const total = this.filteredData.length;
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = Math.min(start + this.itemsPerPage, total);
        const pageData = this.filteredData.slice(start, end);

        if (total === 0) {
            list.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-slate-500">
                    <i class="fa-solid fa-ghost text-4xl mb-2 opacity-50"></i>
                    <p>No programs found matching "${this.searchQuery}"</p>
                </div>
            `;
            countSpan.textContent = "0 items";
            pageSpan.textContent = "1";
            btnPrev.disabled = true;
            btnNext.disabled = true;
            return;
        }

        pageData.forEach(p => {
            const item = document.createElement('div');
            item.className = "bg-slate-800 border border-slate-700/50 rounded-lg p-4 hover:border-blue-500/50 hover:bg-slate-800/80 transition-all group shadow-sm";

            // Highlight matching logic could be added but simpler is fine

            item.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                             <span class="text-xs font-mono text-slate-500">#${p.id.replace('prog_', '')}</span>
                             <h4 class="font-bold text-white text-sm group-hover:text-blue-400 transition-colors">${p.title}</h4>
                        </div>
                        <div class="flex flex-wrap gap-1.5 align-center">
                            <span class="px-1.5 py-0.5 bg-blue-900/40 text-blue-300 text-[10px] rounded border border-blue-800/50 uppercase font-bold tracking-wide">${p.category}</span>
                            ${p.keywords.map(k => `<span class="px-1.5 py-0.5 bg-slate-700/50 text-slate-400 text-[10px] rounded border border-slate-600/30">${k}</span>`).join('')}
                        </div>
                    </div>
                    <button onclick="MyPrograms.insert('${p.id}')" class="px-3 py-1.5 bg-green-600 hover:bg-green-500 text-white text-xs font-bold rounded shadow-lg transition-transform active:scale-95 flex items-center">
                        <i class="fa-solid fa-plus mr-1.5"></i> Insert
                    </button>
                </div>
                <!-- Mini Code Preview -->
                <div class="bg-slate-950 p-2.5 rounded border border-slate-800 overflow-x-auto relative group-hover:border-slate-700 transition-colors">
                    <pre class="text-[10px] text-slate-300 font-mono leading-tight">${this.escapeHtml(p.code)}</pre>
                </div>
            `;
            list.appendChild(item);
        });

        // Update Pagination Info
        countSpan.textContent = `Showing ${start + 1}-${end} of ${total}`;
        pageSpan.textContent = this.currentPage;

        btnPrev.disabled = this.currentPage === 1;
        btnNext.disabled = end >= total;
    },

    prevPage: function () {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.render();
        }
    },

    nextPage: function () {
        const total = this.filteredData.length;
        if (this.currentPage * this.itemsPerPage < total) {
            this.currentPage++;
            this.render();
        }
    },

    insert: function (id) {
        const program = window.MY_PROGRAMS_DATA.find(p => p.id === id);
        if (!program) return;

        // Ensure window.pyvizState exists
        if (!window.pyvizState) return;

        const currentLines = window.pyvizState.lines;

        // Add blank line if content exists
        if (currentLines.length > 0) {
            // Check if last line is already empty?
            // "if playground already has content, insert one blank line before adding code"
            // Usually means adding a spacer.
            // Let's assume just adding an empty line is safe.
            this.addLineToPyViz("", "logic");
        }

        // Split code
        const lines = program.code.split('\n');
        lines.forEach(lineStr => {
            // Trim right only, keep leading space for indent detection
            const content = lineStr.trimRight ? lineStr.trimRight() : lineStr.trimEnd();

            let type = 'logic'; // Default
            const trimmed = content.trim();

            // Simple type inference
            if (trimmed.startsWith('#')) type = 'comment';
            else if (trimmed.includes('=') && !trimmed.startsWith('if') && !trimmed.startsWith('while')) type = 'var';
            else if (trimmed.startsWith('print') || trimmed.startsWith('input')) type = 'func';

            this.addLineToPyViz(content, type);
        });

        this.close();

        // Scroll to bottom
        setTimeout(() => {
            const area = document.getElementById('pyviz-code-area');
            if (area) area.scrollTop = area.scrollHeight;
            // Trigger AI check or render update?
            if (window.renderPyViz) window.renderPyViz();
        }, 100);
    },

    addLineToPyViz: function (codeStr, type) {
        if (typeof window.addLine === 'function') {
            // Indent detection
            // Assume 4 spaces = 1 indent
            const match = codeStr.match(/^(\s*)(.*)/);
            const spaces = match[1].length;
            const content = match[2];
            const indentLevel = Math.floor(spaces / 4);

            window.addLine({
                code: content,
                type: type,
                indent: indentLevel
            });
        } else {
            console.error("PyViz addLine function not found.");
        }
    },

    escapeHtml: function (text) {
        if (!text) return text;
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
};

// Explicitly expose to window immediately
window.MyPrograms = MyPrograms;
console.log("MyPrograms module loaded and exposed to window.");
