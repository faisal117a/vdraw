<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | VDraw Apps</title>
    <?php
    require_once 'auth/Auth.php';
    $siteKey = Auth::getCaptchaSiteKey();
    $loginCaptcha = Auth::isCaptchaEnabled('login');
    $signupCaptcha = Auth::isCaptchaEnabled('signup');
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if(!empty($siteKey) && ($loginCaptcha || $signupCaptcha)): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0f172a; color: #f8fafc; }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-[url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2672&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat relative">
    <div class="absolute inset-0 bg-slate-900/90"></div>

    <div class="glass-panel w-full max-w-md rounded-xl p-8 relative z-10 shadow-2xl">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-brand-500/20 rounded-full flex items-center justify-center mx-auto mb-4 text-brand-400 text-3xl">
                <i class="fa-brands fa-python"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Welcome Back</h1>
            <p class="text-slate-400">Sign in to access PyViz, Linear, Stats, and more.</p>
        </div>

        <!-- Auth Tabs (Visual only since page is for login mostly, but can toggle) -->
        <div class="flex border-b border-slate-700 mb-6">
            <button id="tab-login" onclick="switchTab('login')" class="flex-1 pb-2 font-bold text-brand-400 border-b-2 border-brand-400 transition-colors">Login</button>
            <button id="tab-register" onclick="switchTab('register')" class="flex-1 pb-2 font-bold text-slate-500 hover:text-white border-b-2 border-transparent transition-colors">Register</button>
        </div>

        <!-- Login Form -->
        <form id="form-login" onsubmit="handleAuth(event, 'login')" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1 uppercase">Email</label>
                <input type="email" name="email" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-white focus:border-brand-500 focus:outline-none placeholder-slate-600 transition-colors" placeholder="you@example.com">
            </div>
            <div>
                <div class="flex justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-400 uppercase">Password</label>
                   <!-- <a href="#" class="text-xs text-brand-400 hover:text-brand-300">Forgot?</a> -->
                </div>
                <input type="password" name="password" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-white focus:border-brand-500 focus:outline-none placeholder-slate-600 transition-colors" placeholder="••••••••">
            </div>
            
            <?php if($loginCaptcha && !empty($siteKey)): ?>
            <div class="g-recaptcha" data-sitekey="<?php echo $siteKey; ?>" data-theme="dark"></div>
            <?php endif; ?>

            <div id="err-login" class="text-red-400 text-xs text-center font-bold hidden"></div>
            <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded shadow-lg transition-transform active:scale-95">Sign In</button>
        </form>

        <!-- Register Form -->
        <form id="form-register" onsubmit="handleAuth(event, 'register')" class="space-y-4 hidden">
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1 uppercase">Full Name</label>
                <input type="text" name="full_name" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-white focus:border-brand-500 focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1 uppercase">Email</label>
                <input type="email" name="email" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-white focus:border-brand-500 focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1 uppercase">Password</label>
                <input type="password" name="password" required class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-white focus:border-brand-500 focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1 uppercase">Role</label>
                <select name="role" class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-white focus:border-brand-500 focus:outline-none transition-colors">
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                </select>
            </div>
            
            <div class="flex items-start gap-2">
                 <input type="checkbox" name="terms_agreed" id="terms_agreed" required class="mt-1">
                 <label for="terms_agreed" class="text-xs text-slate-400">
                     I agree to the Terms of Use
                 </label>
            </div>

            <?php if($signupCaptcha && !empty($siteKey)): ?>
            <div class="g-recaptcha" data-sitekey="<?php echo $siteKey; ?>" data-theme="dark"></div>
            <?php endif; ?>

            <div id="err-register" class="text-red-400 text-xs text-center font-bold hidden"></div>
            <button type="submit" class="w-full py-3 bg-green-600 hover:bg-green-500 text-white font-bold rounded shadow-lg transition-transform active:scale-95">Create Account</button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="text-slate-400 hover:text-white text-sm font-semibold transition-colors flex items-center justify-center gap-2 group">
                <i class="fa-solid fa-arrow-left transform group-hover:-translate-x-1 transition-transform"></i> Back to Home
            </a>
        </div>

        <div class="mt-6 text-center text-xs text-slate-500">
            &copy; <?php echo date('Y'); ?> VDraw Apps. Protected Area.
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const fL = document.getElementById('form-login');
            const fR = document.getElementById('form-register');
            const tL = document.getElementById('tab-login');
            const tR = document.getElementById('tab-register');
            const errL = document.getElementById('err-login');
            const errR = document.getElementById('err-register');

            errL.classList.add('hidden');
            errR.classList.add('hidden');

            if (tab === 'login') {
                fL.classList.remove('hidden');
                fR.classList.add('hidden');
                tL.className = 'flex-1 pb-2 font-bold text-brand-400 border-b-2 border-brand-400 transition-colors';
                tR.className = 'flex-1 pb-2 font-bold text-slate-500 hover:text-white border-b-2 border-transparent transition-colors';
            } else {
                fL.classList.add('hidden');
                fR.classList.remove('hidden');
                tL.className = 'flex-1 pb-2 font-bold text-slate-500 hover:text-white border-b-2 border-transparent transition-colors';
                tR.className = 'flex-1 pb-2 font-bold text-green-400 border-b-2 border-green-400 transition-colors';
            }
        }

        async function handleAuth(e, type) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            const err = document.getElementById('err-' + type);
            // Captcha Check on client side (optional but good for UX)
            // if captcha exists, check if filled
            
            // Loading state
            const origText = btn.innerText;
            btn.innerText = 'Processing...';
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            err.classList.add('hidden');

            const fd = new FormData(form);
            fd.append('action', type);

            try {
                // Point to Central API
                const res = await fetch('auth/api.php', {
                    method: 'POST',
                    body: fd
                });
                
                let data;
                try {
                     data = await res.json();
                } catch(parseErr) {
                     console.error('Parse Error', parseErr);
                     throw new Error('Server returned invalid response');
                }

                if (data.status === 'success') {
                    if (type === 'register') {
                        // Phase 14: Redirect to verification page
                        const email = fd.get('email');
                        window.location.href = 'verify_email.php?email=' + encodeURIComponent(email);
                    } else {
                         // Check URL params for redirect
                         const urlParams = new URLSearchParams(window.location.search);
                         const red = urlParams.get('redirect');
                         if (red) {
                             window.location.href = red;
                         } else {
                             window.location.href = data.redirect || 'index.php';
                         }
                    }
                } else {
                    err.innerText = data.message || 'Error occurred';
                    err.classList.remove('hidden');
                    // Reset Captcha if error
                    if(typeof grecaptcha !== 'undefined') grecaptcha.reset();
                }
            } catch (error) {
                console.error(error);
                err.innerText = 'Connection Failed: ' + error.message;
                err.classList.remove('hidden');
                if(typeof grecaptcha !== 'undefined') grecaptcha.reset();
            } finally {
                btn.innerText = origText;
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }
    </script>
</body>
</html>
