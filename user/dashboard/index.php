<?php
require_once '../../auth/Auth.php';

if (!Auth::isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$user = Auth::user();
require_once '../../auth/PointsSystem.php';
$pointsBalance = PointsSystem::getBalance($user['id']);
$conn = DB::connect();

// Refresh User Data to ensure status updates (like verification) are reflected immediately
$stmt = DB::query("SELECT * FROM users WHERE id = ?", [$user['id']], "i");
if ($stmt && $fresh = $stmt->get_result()->fetch_assoc()) {
    $user = $fresh;
    $user['verified'] = !empty($fresh['email_verified_at']);
    if ($user['status'] !== 'active') {
        session_destroy();
        header("Location: ../../login.php?msg=" . urlencode("Access Denied: Account Blocked"));
        exit;
    }
}

// Fetch stats
$userId = $user['id'];
$today = date('Ymd');
$month = date('Ym');

// Daily Credits
$dailyUsed = 0;
$stmt = DB::query("SELECT credits_used FROM user_credit_daily WHERE user_id = ? AND yyyymmdd = ?", [$userId, $today], "is");
if ($stmt && $row = $stmt->get_result()->fetch_assoc()) {
    $dailyUsed = $row['credits_used'];
}

// Determine My Limit
$myLimit = (isset($user['quota_daily']) && $user['quota_daily'] > 0) ? (int)$user['quota_daily'] : 0;

if ($myLimit <= 0) {
    // Fallback if not set in user table (Should not happen after migration)
    // Fetch Limits check
    $limits = ['student' => 20, 'teacher' => 100, 'teacher_verified' => 500];
    $stmt = DB::query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'limit_daily_%'");
    if ($stmt && ($res = $stmt->get_result())) {
        while($row = $res->fetch_assoc()) {
            $key = str_replace('limit_daily_', '', $row['setting_key']);
            $val = (int)$row['setting_value'];
            if ($val > 0) $limits[$key] = $val;
        }
    }
    
    $myLimit = $limits['student'];
    if (($user['role'] ?? 'student') === 'teacher') {
        $isVerified = isset($user['teacher_verified']) && $user['teacher_verified'];
        $myLimit = $isVerified ? ($limits['teacher_verified'] ?? 500) : ($limits['teacher'] ?? 100);
    }
}
if ($myLimit <= 0) $myLimit = 20;

// Teacher status
$teacherStatus = 'Not Verified';
if (!empty($user['teacher_verified'])) {
    $teacherStatus = 'Verified Teacher';
} else {
    // Check pending
    $stmt = DB::query("SELECT status FROM teacher_verification_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$userId], "i");
    if ($stmt && $row = $stmt->get_result()->fetch_assoc()) {
        $st = $row['status'];
        $teacherStatus = ($st === 'approved') ? 'Unverified' : ucfirst($st);
    }
}

// Activity Log
$logs = [];
$stmt = DB::query("SELECT * FROM user_activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10", [$userId], "i");
if ($stmt) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | PyViz</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'], },
                    colors: {
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 900: '#1e3a8a', }
                    }
                }
            }
        }
    </script>
    <style>
        :root { --bg-color: #0f172a; --text-color: #e2e8f0; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-color); color: var(--text-color); }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.05); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
    </style>
</head>
<body class="h-screen flex overflow-hidden bg-slate-950">

    <?php if(isset($_GET['msg'])): 
        $msg = $_GET['msg'];
        $isError = stripos($msg, 'error') !== false || stripos($msg, 'fail') !== false || stripos($msg, 'invalid') !== false;
        $bgClass = $isError ? 'bg-red-500 border-red-600' : 'bg-green-500 border-green-600';
        $icon = $isError ? 'fa-circle-xmark' : 'fa-check';
    ?>
        <div id="toast-msg" onclick="this.remove()" class="fixed top-4 right-4 <?php echo $bgClass; ?> text-white px-4 py-2 rounded shadow-lg cursor-pointer z-50 animate-fade-in flex items-center border">
            <i class="fa-solid <?php echo $icon; ?> mr-2"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
        <script>
            if (history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('msg');
                window.history.replaceState(null, '', url);
            }
            setTimeout(() => {
                const el = document.getElementById('toast-msg');
                if(el) {
                    el.style.transition = 'opacity 0.5s';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 500);
                }
            }, 4000);
        </script>
    <?php endif; ?>

    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-10 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- Sidebar -->
    <aside id="user-sidebar" class="w-64 glass-panel border-r border-slate-800 flex flex-col fixed md:relative z-20 h-full transition-transform transform -translate-x-full md:translate-x-0 bg-slate-900">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <h1 class="text-xl font-bold text-brand-400"><i class="fa-brands fa-python mr-2"></i>PyViz User</h1>
            <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="#" class="block px-4 py-3 rounded bg-brand-900/40 text-brand-100 border border-brand-500/30"><i class="fa-solid fa-chart-line mr-3"></i> Dashboard</a>
            <button onclick="openChangePassword()" class="w-full text-left block px-4 py-3 rounded hover:bg-slate-800 transition text-slate-400 hover:text-white"><i class="fa-solid fa-lock mr-3"></i> Security</button>
            <a href="../../frontend/PyViz/index.php" class="block px-4 py-3 rounded hover:bg-slate-800 transition"><i class="fa-solid fa-code mr-3"></i> Playground</a>
        </nav>
        <div class="p-4 border-t border-slate-800">
            <button onclick="logout()" class="w-full py-2 bg-red-900/30 text-red-200 border border-red-800 rounded hover:bg-red-800/40 transition">
                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-4 md:p-8 relative">
        <div class="flex flex-col md:flex-row md:items-center gap-4 mb-8">
            <div class="flex items-center justify-between">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-white"><i class="fa-solid fa-bars text-xl"></i></button>
            </div>
            <header class="flex-1 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-white">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="text-slate-400 text-sm">Manage your profile and usage</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="md:text-right">
                        <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($user['email']); ?></div>
                        <div class="text-xs text-slate-500 uppercase"><?php echo htmlspecialchars($user['role']); ?></div>
                    </div>
                </div>
            </header>
        </div>

        <!-- Dashboard Content Stack -->
        <div class="max-w-7xl mx-auto space-y-8">
            
            <!-- Stats Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Card 1: Daily Quota -->
                <div class="glass-panel p-6 rounded-xl">
                    <div class="text-slate-400 text-sm mb-1">Daily Quota Usage</div>
                    <div class="text-3xl font-bold text-white mb-2 flex items-baseline gap-2">
                        <?php 
                            $normUsed = min($dailyUsed, $myLimit);
                            $rewUsed = max(0, $dailyUsed - $myLimit);
                        ?>
                        <?php echo $normUsed; ?> 
                        <span class="text-lg text-slate-500 font-normal">/ 
                            <?php echo $myLimit; ?>
                            <?php if(!empty($user['reward_credits']) && $user['reward_credits'] > 0): ?>
                            <span class="text-brand-400 text-base" title="Reward Balance">+<?php echo $user['reward_credits']; ?> Balance</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if($rewUsed > 0): ?>
                        <div class="text-xs text-green-400 font-bold mb-2"><i class="fa-solid fa-gift mr-1"></i> +<?php echo $rewUsed; ?> Reward Used Today</div>
                    <?php endif; ?>
                    <div class="w-full bg-slate-700 h-2 rounded-full overflow-hidden">
                        <?php 
                            $totalAvail = $myLimit + ($user['reward_credits']??0);
                            $pct = ($totalAvail > 0) ? ($dailyUsed / $totalAvail) * 100 : 0;
                        ?>
                        <div class="bg-brand-500 h-full" style="width: <?php echo min(100, $pct); ?>%"></div>
                    </div>
                </div>

                <!-- Card 2: Status/Upgrade -->
                <div class="glass-panel p-6 rounded-xl">
                    <?php if(($user['role'] ?? 'student') === 'student'): ?>
                        <div class="text-slate-400 text-sm mb-1">Account Type</div>
                        <div class="text-2xl font-bold text-white">Student</div>
                        <form action="update_profile.php" method="POST" onsubmit="return confirm('Upgrade to Teacher account to unlock verification?');">
                            <input type="hidden" name="action" value="upgrade_teacher">
                            <button type="submit" class="mt-3 text-xs px-3 py-1 bg-brand-600 rounded text-white hover:bg-brand-500">Upgrade to Teacher</button>
                        </form>
                    <?php else: ?>
                        <div class="text-slate-400 text-sm mb-1">Teacher Status</div>
                        <div class="text-2xl font-bold <?php echo $teacherStatus === 'Verified Teacher' ? 'text-green-400' : 'text-yellow-400'; ?>">
                            <?php echo $teacherStatus; ?>
                        </div>
                        <?php if ($teacherStatus === 'Not Verified' || $teacherStatus === 'Rejected' || $teacherStatus === 'Unverified'): ?>
                            <?php if(!empty($user['email_verified_at'])): ?>
                            <button onclick="openVerificationModal()" class="mt-3 text-xs px-3 py-1 bg-brand-600 rounded text-white hover:bg-brand-500">Apply Now</button>
                            <?php else: ?>
                            <button disabled class="mt-3 text-xs px-3 py-1 bg-slate-800 border border-slate-700 rounded text-slate-500 cursor-not-allowed opacity-75">Apply Now (Verify Email First)</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Card 3: Account Status -->
                <div class="glass-panel p-6 rounded-xl">
                    <div class="text-slate-400 text-sm mb-1">Account Status</div>
                    <div class="text-xl font-bold text-blue-400">
                        <?php echo $user['verified'] ? 'Email Verified' : 'Email Unverified'; ?>
                    </div>
                    <?php if (!$user['verified']): ?>
                    <div class="text-xs text-red-400 mt-1"><i class="fa-solid fa-triangle-exclamation"></i> Restricted Access</div>
                    <?php endif; ?>
                </div>

                <!-- Card 4: Rewards -->
                <div class="glass-panel p-6 rounded-xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fa-solid fa-trophy text-6xl text-amber-500"></i>
                    </div>
                    <div class="text-slate-400 text-sm mb-1">Rewards Balance</div>
                    <div class="text-3xl font-bold text-amber-400 mb-2">
                        <i class="fa-solid fa-coins mr-1 text-2xl"></i> <?php echo number_format($pointsBalance); ?>
                    </div>
                    <div class="text-xs text-slate-500 mb-3">Rate: 20 Pts = 1 Credit</div>
                    <button onclick="openRedeemModal()" class="w-full text-xs px-3 py-2 bg-amber-600 rounded text-white hover:bg-amber-500 font-bold shadow-lg shadow-amber-900/20 z-10 relative">
                        Redeem Credits
                    </button>
                </div>
            </div>

            <!-- Activity Log Section -->
            <div class="glass-panel rounded-xl overflow-hidden">
                <div class="p-4 border-b border-slate-700 bg-slate-900/50">
                    <h3 class="font-bold text-slate-200">Recent Activity</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-400">
                            <thead class="bg-slate-800/50 text-slate-200 uppercase text-xs sticky top-0">
                                <tr>
                                    <th class="px-3 py-2">Event</th>
                                    <th class="px-3 py-2">Details</th>
                                    <th class="px-3 py-2">Time</th>
                                </tr>
                            </thead>
                        <tbody class="divide-y divide-slate-800">
                            <?php foreach($logs as $log): ?>
                                <tr class="hover:bg-slate-800/30">
                                    <td class="px-3 py-2 font-medium text-white whitespace-nowrap"><?php echo htmlspecialchars($log['title']); ?></td>
                                    <td class="px-3 py-2 text-xs min-w-[150px]"><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td class="px-3 py-2 text-[10px] whitespace-nowrap"><?php echo date('m-d H:i', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($logs)): ?>
                            <tr><td colspan="3" class="px-6 py-4 text-center italic text-slate-600">No recent activity.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>


    </main>
    
    <!-- Verification Modal -->
    <div id="verify-modal" class="fixed inset-0 bg-black/60 z-50 hidden backdrop-blur-sm flex items-center justify-center">
        <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-full max-w-md shadow-2xl relative">
            <button onclick="document.getElementById('verify-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
            
            <h3 class="text-xl font-bold text-white mb-2">Teacher Verification</h3>
            <p class="text-slate-400 text-sm mb-6">Upload your Faculty ID Card or Employment Letter to verify your teacher status and unlock higher limits.</p>
            
            <form id="verify-form" onsubmit="submitVerification(event)" class="space-y-4">
                <div class="border-2 border-dashed border-slate-700 rounded-lg p-6 flex flex-col items-center justify-center hover:border-brand-500 transition-colors cursor-pointer relative">
                    <input type="file" name="id_document" id="id_document" class="absolute inset-0 opacity-0 cursor-pointer" accept=".pdf,.jpg,.jpeg,.png" required onchange="updateFileName(this)">
                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-500 mb-2"></i>
                    <p class="text-sm text-slate-300 font-medium">Click to upload document</p>
                    <p class="text-xs text-slate-500">(PDF, JPG, PNG - Max 3MB)</p>
                    <p id="file-name" class="text-xs text-brand-400 mt-2 font-mono hidden"></p>
                </div>
                
                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 rounded transition shadow-lg shadow-brand-500/20">
                    Submit for Review
                </button>
            </form>
        </div>
    </div>
    
    <!-- Redeem Modal -->
    <div id="redeem-modal" class="fixed inset-0 bg-black/60 z-50 hidden backdrop-blur-sm flex items-center justify-center">
        <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-full max-w-sm shadow-2xl relative">
            <button onclick="document.getElementById('redeem-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 class="text-xl font-bold text-white mb-2"><i class="fa-solid fa-gift text-amber-500 mr-2"></i> Redeem Credits</h3>
            <p class="text-slate-400 text-sm mb-6">Convert your activity points into extra AI usage credits.</p>
            
            <div class="bg-slate-800/50 p-3 rounded mb-4 text-center">
                <div class="text-xs text-slate-500 uppercase font-bold">Current Balance</div>
                <div class="text-2xl font-bold text-amber-400"><?php echo number_format($pointsBalance); ?> Pts</div>
            </div>

            <form onsubmit="submitRedemption(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Credits to Buy (20 Pts/Credit)</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" id="redeem-qty" name="credits" min="1" value="1" required 
                            class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white placeholder-slate-600 text-center font-bold"
                            oninput="updateRedeemCost()">
                        <span class="text-white font-bold">=</span>
                        <div class="w-24 bg-slate-800 border border-slate-700 rounded p-2 text-center text-amber-400 font-bold text-sm">
                            <span id="redeem-cost">20</span> Pts
                        </div>
                    </div>
                </div>

                <div id="redeem-error" class="hidden text-xs text-red-400 text-center font-bold"></div>

                <button type="submit" id="redeem-btn" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 rounded transition shadow-lg flex justify-center items-center">
                    Confirm Redemption
                </button>
            </form>
        </div>
    </div>

    <!-- Password Modal -->
    <div id="password-modal" class="fixed inset-0 bg-black/60 z-50 hidden backdrop-blur-sm flex items-center justify-center">
        <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-full max-w-md shadow-2xl relative">
            <button onclick="document.getElementById('password-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 class="text-xl font-bold text-white mb-6">Change Password</h3>
            <form action="update_profile.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Current Password</label>
                    <input type="password" name="current_password" required class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white placeholder-slate-600" placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">New Password</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white placeholder-slate-600" placeholder="••••••••">
                </div>
                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 rounded transition shadow-lg">Update Password</button>
            </form>
        </div>
    </div>

    <script>
        async function logout() {
            const formData = new FormData();
            formData.append('action', 'logout');
            await fetch('../../auth/api.php', { method: 'POST', body: formData });
            window.location.href = '../../login.php';
        }
        
        function openVerificationModal() {
            document.getElementById('verify-modal').classList.remove('hidden');
        }

        function openChangePassword() {
            document.getElementById('password-modal').classList.remove('hidden');
        }

        function updateFileName(input) {
            const display = document.getElementById('file-name');
            if (input.files && input.files[0]) {
                display.textContent = input.files[0].name;
                display.classList.remove('hidden');
            } else {
                display.classList.add('hidden');
            }
        }

        async function submitVerification(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Uploading...';

            const formData = new FormData(e.target);
            
            try {
                const res = await fetch('upload_verification.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                console.error(err);
                alert('Upload failed. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        function openRedeemModal() {
            document.getElementById('redeem-qty').value = 1;
            updateRedeemCost();
            document.getElementById('redeem-error').classList.add('hidden');
            document.getElementById('redeem-modal').classList.remove('hidden');
        }

        function updateRedeemCost() {
            const qty = parseInt(document.getElementById('redeem-qty').value) || 0;
            const cost = qty * 20;
            document.getElementById('redeem-cost').innerText = cost;
            const balance = <?php echo $pointsBalance; ?>;
            const btn = document.getElementById('redeem-btn');
            
            if (cost > balance) {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                document.getElementById('redeem-error').innerText = "Insufficient Points";
                document.getElementById('redeem-error').classList.remove('hidden');
            } else {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                document.getElementById('redeem-error').classList.add('hidden');
            }
        }

        async function submitRedemption(e) {
            e.preventDefault();
            const btn = document.getElementById('redeem-btn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Processing...';
            
            const formData = new FormData(e.target);
            
            try {
                const res = await fetch('redeem_points.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    alert('Redemption Successful!');
                    location.reload();
                } else {
                    document.getElementById('redeem-error').innerText = data.message || "Redemption Failed";
                    document.getElementById('redeem-error').classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                console.error(err);
                document.getElementById('redeem-error').innerText = "Network Error";
                document.getElementById('redeem-error').classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        function toggleSidebar() {
            const sb = document.getElementById('user-sidebar');
            const over = document.getElementById('sidebar-overlay');
            sb.classList.toggle('-translate-x-full');
            over.classList.toggle('hidden');
        }
    </script>
</body>
</html>
