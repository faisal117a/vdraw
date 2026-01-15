
// frontend/PyViz/auth/auth_client.js

window.AuthState = {
    isLoggedIn: false,
    user: null, // { full_name, role, etc. }

    init: function (userPayload) {
        if (userPayload) {
            this.isLoggedIn = true;
            this.user = userPayload;
            this.updateUI();
        }
    },

    updateUI: function () {
        const container = document.getElementById('auth-header-controls');
        if (!container) return;

        if (this.isLoggedIn) {
            const dashboardUrl = this.user.role === 'admin' ? 'admin/dashboard/' : 'user/dashboard/';
            const initials = this.user.full_name ? this.user.full_name.substring(0, 2).toUpperCase() : 'U';

            container.innerHTML = `
                <div class="relative group">
                    <button class="flex items-center space-x-2 text-sm font-medium text-slate-300 hover:text-white transition focus:outline-none">
                        <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-xs font-bold text-white shadow-lg border border-brand-400/50">
                            ${initials}
                        </div>
                        <span class="hidden md:inline">${this.user.full_name}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-500"></i>
                    </button>
                    <!-- Dropdown -->
                    <div class="absolute right-0 top-full pt-2 w-48 hidden group-hover:block z-50">
                        <div class="bg-slate-800 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-700/50 bg-slate-800/50">
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Signed in as</p>
                                <p class="text-xs text-white truncate font-medium mt-0.5">${this.user.email}</p>
                            </div>
                            <a href="${dashboardUrl}" class="block px-4 py-2 text-sm text-slate-200 hover:bg-brand-600/20 hover:text-brand-400 transition">
                                <i class="fa-solid fa-chart-line mr-2"></i> Dashboard
                            </a>
                            <button onclick="window.AuthState.logout()" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 transition">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = `
                 <button onclick="window.AuthState.showLoginModal('Login')" class="text-slate-400 hover:text-white transition font-medium">Login</button>
                 <button onclick="window.AuthState.showLoginModal('Register'); switchAuthTab('register')" class="bg-brand-600 hover:bg-brand-500 text-white px-4 py-1.5 rounded text-xs font-bold shadow-lg shadow-brand-500/20 transition-all active:scale-95">Sign Up</button>
            `;
        }
    },

    check: function (featureName) {
        if (this.isLoggedIn) {
            // Check if email verified if required
            if (!this.user.verified) {
                // Show toast or modal
                window.showToast?.("Verify Email", "You must verify your email to use " + featureName + ".", "warning");
                return false;
            }
            return true;
        } else {
            this.showLoginModal(featureName);
            return false;
        }
    },

    showLoginModal: function (contextMessage) {
        const modal = document.getElementById('pyviz-auth-modal');
        const msgEl = document.getElementById('auth-context-msg');
        if (msgEl && contextMessage) {
            msgEl.textContent = "Login to access " + contextMessage;
            msgEl.classList.remove('hidden');
        } else if (msgEl) {
            msgEl.classList.add('hidden');
        }

        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            // Reset to login tab
            switchAuthTab('login');
        }
    },

    hideModal: function () {
        const modal = document.getElementById('pyviz-auth-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },

    logout: async function () {
        const formData = new FormData();
        formData.append('action', 'logout');
        await fetch('auth/api.php', { method: 'POST', body: formData });
        window.location.reload();
    }
};

// Global helper for the modal
window.switchAuthTab = function (tab) {
    document.getElementById('auth-tab-login').classList.toggle('border-blue-500', tab === 'login');
    document.getElementById('auth-tab-login').classList.toggle('text-blue-500', tab === 'login');
    document.getElementById('auth-tab-login').classList.toggle('border-transparent', tab !== 'login');

    document.getElementById('auth-tab-register').classList.toggle('border-blue-500', tab === 'register');
    document.getElementById('auth-tab-register').classList.toggle('text-blue-500', tab === 'register');
    document.getElementById('auth-tab-register').classList.toggle('border-transparent', tab !== 'register');

    document.getElementById('auth-form-login').classList.toggle('hidden', tab !== 'login');
    document.getElementById('auth-form-register').classList.toggle('hidden', tab === 'login');
};

async function handleAuthSubmit(event, type) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', type);

    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    try {
        const res = await fetch('auth/api.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.status === 'success') {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else if (type === 'register') {
                // Switch to login or show verify token message
                alert("Registration successful! Verification Code (Dev Only): " + data.verification_code);
                switchAuthTab('login');
            }
        } else {
            // Show error
            const errEl = document.getElementById('auth-error-' + type);
            if (errEl) {
                errEl.textContent = data.message;
                errEl.classList.remove('hidden');
            }
        }
    } catch (e) {
        console.error(e);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
