<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | VDraw Apps</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4 text-green-400 text-3xl">
                <i class="fa-solid fa-envelope-circle-check"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Verify Email</h1>
            <p class="text-slate-400">Enter the 6-digit code sent to your email.</p>
        </div>

        <form id="form-verify" onsubmit="handleVerify(event)" class="space-y-4">
            <input type="hidden" name="action" value="verify_email">
            
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1 uppercase">Email Address</label>
                <input type="email" name="email" id="email-input" required readonly 
                       class="w-full bg-slate-800/50 border border-slate-700 rounded p-3 text-slate-300 focus:outline-none cursor-not-allowed">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1 uppercase">Verification Code</label>
                <input type="text" name="code" required maxlength="6" pattern="\d{6}"
                       class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-white text-center text-2xl tracking-[0.5em] font-mono focus:border-brand-500 focus:outline-none placeholder-slate-600 transition-colors" 
                       placeholder="000000">
            </div>

            <div id="err-verify" class="text-red-400 text-xs text-center font-bold hidden"></div>
            <div id="success-verify" class="text-green-400 text-xs text-center font-bold hidden">Email verified successfully!</div>

            <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded shadow-lg transition-transform active:scale-95">
                Verify Account
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="login.php" class="text-slate-400 hover:text-white text-sm font-semibold transition-colors flex items-center justify-center gap-2 group">
                <i class="fa-solid fa-arrow-left transform group-hover:-translate-x-1 transition-transform"></i> Back to Login
            </a>
        </div>
    </div>

    <script>
        // Populate Email from URL
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const email = params.get('email');
            if (email) {
                document.getElementById('email-input').value = email;
            }
        });

        async function handleVerify(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button');
            const err = document.getElementById('err-verify');
            const suc = document.getElementById('success-verify');

            btn.innerText = 'Verifying...';
            btn.disabled = true;
            btn.classList.add('opacity-75');
            err.classList.add('hidden');
            suc.classList.add('hidden');

            const fd = new FormData(form);

            try {
                const res = await fetch('auth/api.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.status === 'success') {
                    suc.classList.remove('hidden');
                    btn.classList.add('hidden'); // Hide button
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    err.innerText = data.message;
                    err.classList.remove('hidden');
                    btn.innerText = 'Verify Account';
                    btn.disabled = false;
                    btn.classList.remove('opacity-75');
                }
            } catch (error) {
                err.innerText = 'Connection Failed';
                err.classList.remove('hidden');
                btn.innerText = 'Verify Account';
                btn.disabled = false;
                btn.classList.remove('opacity-75');
            }
        }
    </script>
</body>
</html>
