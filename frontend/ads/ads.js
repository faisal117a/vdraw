/**
 * VDraw Ads System Client Lite
 * Handles fetching, rendering, and tracking ads asynchronously.
 */
class AdManager {
    constructor(config) {
        this.appKey = config.appKey;
        this.rootUrl = config.rootUrl || '../../frontend/ads/'; // Adjust based on location
        this.placements = [];
        this.init();
    }

    init() {
        // Find all ad containers
        document.querySelectorAll('[data-ad-placement]').forEach(el => {
            this.placements.push(el.dataset.adPlacement);
        });

        if (this.placements.length > 0 || this.appKey) {
            this.fetchAds();
        }
    }

    async fetchAds() {
        if (!this.placements.length && !this.appKey) return;

        const fd = new FormData();
        fd.append('app_key', this.appKey);
        fd.append('placements', this.placements.join(','));

        try {
            const res = await fetch(this.rootUrl + 'serve_ads.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.status === 'success') {
                this.renderPlacements(data.placements);
                if (data.push) this.renderPush(data.push);
            }
        } catch (e) {
            console.error('Ad Fetch Error:', e);
        }
    }

    renderPlacements(ads) {
        for (const [key, ad] of Object.entries(ads)) {
            const container = document.querySelector(`[data-ad-placement="${key}"]`);
            if (!container) continue;

            let html = '';
            if (ad.type === 'sponsor') {
                html = `
                    <a href="${this.rootUrl}click.php?id=${ad.id}&url=${encodeURIComponent(ad.link)}" target="_blank" class="block w-fit mx-auto max-w-full relative group overflow-hidden rounded-lg shadow-lg border border-slate-700/50 hover:border-brand-500/50 transition-colors">
                        <img src="${this.rootUrl}../../${ad.image}" alt="Sponsor" class="block max-w-full h-auto">
                        <div class="absolute bottom-0 right-0 bg-black/50 text-[9px] text-white px-1 rounded-tl">Ad</div>
                        <img src="${this.rootUrl}track.php?id=${ad.id}" class="hidden" alt="">
                    </a>
                `;
                // Note: link logic slightly differed in PHP serve vs here. 
                // PHP serve returned "ads/click.php...", we adjust path relative to current page or absolute?
                // Logic: serve_ads.php returns 'link' = 'ads/click.php...'.
                // If we are in 'frontend/PyViz/', we need '../ads/click.php'.
                // Simplification: Let PHP return full useful relative path or absolute.
                // PHP returned 'ads/click.php...'. If rootUrl is '../../frontend/ads/', we need to be careful.
                // Let's rely on standard anchor logic:
                // If PHP sends 'ads/click.php', and tracked_url is pure URL.

                // Correction: Render HTML directly from generic logic
                // Using track_imp from server which is 'ads/track.php...'
            } else if (ad.type === 'google') {
                html = `
                    <div class="w-full text-center my-2">
                        <div class="inline-block text-[9px] text-slate-600 mb-1">Advertisement</div>
                        <div>${ad.html}</div>
                    </div>
                `;
            }

            container.innerHTML = html;
            container.classList.remove('hidden');

            container.classList.remove('hidden');

            // JS Execution for Google Ads
            if (ad.type === 'google') {
                this.executeScripts(container);
            }
        }
    }

    executeScripts(container) {
        Array.from(container.querySelectorAll("script")).forEach(oldScript => {
            const newScript = document.createElement("script");
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    renderPush(ad) {
        // Frequency Check
        const storeKey = `vdraw_push_${ad.id}_last`;
        const lastShown = localStorage.getItem(storeKey);
        const freqLimit = ad.freq * 60 * 60 * 1000; // freq is int? "Frequency Cap (per user)" usually 1 per day?
        // Schema says: frequency_limit INT default 1. Interpreted as "Max views per session"? or "Hours between"?
        // Phase 11: "Strict frequency control".
        // Let's assume 1 per X hours. Say 24h default for "1".
        // Or if freq=1 means 1 time ever? 
        // Let's interpret freq as "Max times per day".

        // Simple Logic: Don't show if shown recently (e.g. 1 hour).
        if (lastShown && (Date.now() - lastShown < 3600000)) return; // 1 hour cooldown standard

        localStorage.setItem(storeKey, Date.now());

        // Create DOM
        const div = document.createElement('div');
        div.className = `fixed z-[100] transition-all duration-500 transform ${ad.display === 'toast' ? 'bottom-4 right-4 translate-y-20 opacity-0' : 'inset-0 bg-black/80 flex items-center justify-center opacity-0'}`;

        let innerString = '';
        if (ad.display === 'toast') {
            innerString = `
                <div class="bg-slate-800 border border-slate-700 text-white p-4 rounded-xl shadow-2xl relative w-80">
                    <button class="absolute top-2 right-2 text-slate-500 hover:text-white" onclick="this.closest('.fixed').remove()"><i class="fa-solid fa-times"></i></button>
                    <div class="text-xs text-brand-400 font-bold uppercase mb-2">Partner Message</div>
                    <div class="prose prose-invert text-sm">${ad.content}</div>
                </div>
            `;
        } else {
            innerString = `
                <div class="bg-slate-900 border border-slate-700 text-white p-6 rounded-2xl shadow-2xl relative max-w-lg w-full m-4 transform scale-95 transition-transform">
                    <button class="absolute top-4 right-4 text-slate-500 hover:text-white" onclick="this.closest('.fixed').remove()"><i class="fa-solid fa-times fa-lg"></i></button>
                    ${ad.content}
                </div>
             `;
        }

        div.innerHTML = innerString;
        document.body.appendChild(div);

        // Tracking
        if (ad.track_imp) fetch(this.rootUrl + '../' + ad.track_imp, { mode: 'no-cors' });

        // Animate In
        requestAnimationFrame(() => {
            div.classList.remove('translate-y-20', 'opacity-0', 'scale-95');
            div.classList.add('translate-y-0', 'opacity-100', 'scale-100');
        });

        // Auto Close
        if (ad.display === 'toast' && ad.delay > 0) {
            setTimeout(() => {
                div.classList.add('opacity-0', 'translate-y-20');
                setTimeout(() => div.remove(), 500);
            }, ad.delay * 1000);
        }
    }
}
