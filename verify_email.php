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
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .animate-pulse-slow { animation: pulse-slow 2s ease-in-out infinite; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-[url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2672&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat relative">
    <div class="absolute inset-0 bg-slate-900/90"></div>

    <div class="glass-panel w-full max-w-md rounded-xl p-8 relative z-10 shadow-2xl">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4 text-green-400 text-3xl">
                <i class="fa-solid fa-envelope-circle-check"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Verify Email</h1>
            <p class="text-slate-400">Enter the 6-digit code sent to your email.</p>
        </div>

        <!-- Info Notice -->
        <div class="bg-blue-900/30 border border-blue-800/50 rounded-lg p-3 mb-6 text-sm">
            <div class="flex items-start gap-2">
                <i class="fa-solid fa-circle-info text-blue-400 mt-0.5"></i>
                <div>
                    <p class="text-blue-300">The verification email may take <strong>2-3 minutes</strong> to arrive. Please also check your spam/junk folder.</p>
                    <p class="text-slate-400 text-xs mt-1">Having trouble? Contact us at <a href="mailto:hello@vdraw.cc" class="text-blue-400 hover:underline">hello@vdraw.cc</a></p>
                </div>
            </div>
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
                <input type="text" name="code" id="code-input" required maxlength="6" pattern="\d{6}"
                       class="w-full bg-slate-800 border border-slate-700 rounded p-3 text-white text-center text-2xl tracking-[0.5em] font-mono focus:border-brand-500 focus:outline-none placeholder-slate-600 transition-colors" 
                       placeholder="000000">
            </div>

            <div id="err-verify" class="text-red-400 text-xs text-center font-bold hidden"></div>
            <div id="success-verify" class="text-green-400 text-xs text-center font-bold hidden">Email verified successfully!</div>

            <button type="submit" id="btn-verify" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-white font-bold rounded shadow-lg transition-transform active:scale-95">
                Verify Account
            </button>
        </form>

        <!-- Resend Code Section -->
        <div class="mt-4 pt-4 border-t border-slate-700/50">
            <div id="timer-section" class="text-center">
                <p class="text-slate-500 text-sm mb-2">Didn't receive the code?</p>
                <div class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-clock text-slate-600"></i>
                    <span id="timer-display" class="text-slate-400 font-mono text-lg">03:00</span>
                </div>
                <p class="text-slate-600 text-xs mt-1">Wait before requesting a new code</p>
            </div>
            
            <button id="btn-resend" onclick="handleResend()" disabled
                    class="hidden w-full py-2 mt-2 bg-slate-700 text-slate-400 font-bold rounded transition cursor-not-allowed opacity-50">
                <i class="fa-solid fa-rotate-right mr-2"></i>Send Code Again
            </button>
            
            <div id="resend-success" class="hidden text-center mt-2">
                <span class="text-green-400 text-sm"><i class="fa-solid fa-check mr-1"></i>New code sent! Check your email.</span>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="login.php" class="text-slate-400 hover:text-white text-sm font-semibold transition-colors flex items-center justify-center gap-2 group">
                <i class="fa-solid fa-arrow-left transform group-hover:-translate-x-1 transition-transform"></i> Back to Login
            </a>
        </div>
    </div>

    <script>
        let timerSeconds = 180; // 3 minutes
        let timerInterval = null;

        // Populate Email from URL
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const email = params.get('email');
            if (email) {
                document.getElementById('email-input').value = email;
            }
            
            // Start countdown timer
            startTimer();
        });

        function startTimer() {
            timerSeconds = 180;
            updateTimerDisplay();
            
            document.getElementById('timer-section').classList.remove('hidden');
            document.getElementById('btn-resend').classList.add('hidden');
            document.getElementById('resend-success').classList.add('hidden');
            
            if (timerInterval) clearInterval(timerInterval);
            
            timerInterval = setInterval(() => {
                timerSeconds--;
                updateTimerDisplay();
                
                if (timerSeconds <= 0) {
                    clearInterval(timerInterval);
                    enableResendButton();
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const mins = Math.floor(timerSeconds / 60);
            const secs = timerSeconds % 60;
            document.getElementById('timer-display').textContent = 
                `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        function enableResendButton() {
            document.getElementById('timer-section').classList.add('hidden');
            const btn = document.getElementById('btn-resend');
            btn.classList.remove('hidden', 'opacity-50', 'cursor-not-allowed');
            btn.classList.add('bg-yellow-600', 'hover:bg-yellow-500', 'text-white');
            btn.disabled = false;
        }

        async function handleResend() {
            const email = document.getElementById('email-input').value;
            if (!email) {
                alert('Email address is required');
                return;
            }
            
            const btn = document.getElementById('btn-resend');
            const origText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin mr-2"></i>Sending...';
            btn.disabled = true;
            
            try {
                const fd = new FormData();
                fd.append('action', 'resend_verification');
                fd.append('email', email);
                
                const res = await fetch('auth/api.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    document.getElementById('resend-success').classList.remove('hidden');
                    document.getElementById('btn-resend').classList.add('hidden');
                    // Restart timer
                    setTimeout(() => {
                        startTimer();
                    }, 3000);
                } else {
                    alert(data.message || 'Failed to resend code');
                    btn.innerHTML = origText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                alert('Connection failed. Please try again.');
                btn.innerHTML = origText;
                btn.disabled = false;
            }
        }

        async function handleVerify(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('btn-verify');
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
                    // Stop timer
                    if (timerInterval) clearInterval(timerInterval);
                    document.getElementById('timer-section').classList.add('hidden');
                    document.getElementById('btn-resend').classList.add('hidden');
                    
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
