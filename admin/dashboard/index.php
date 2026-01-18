<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../auth/Auth.php';
require_once '../../auth/Security.php'; // Load here to ensure availability

if (!Auth::isLoggedIn() || $_SESSION['role'] !== 'admin') {
    $role = $_SESSION['role'] ?? 'guest';
    die("Access Denied. Admins only. Your role is: $role");
}

$conn = DB::connect();

// Fetch summary stats
$totalUsers = 0;
$stmt = DB::query("SELECT COUNT(*) as c FROM users");
if ($stmt && $row = $stmt->get_result()->fetch_assoc()) $totalUsers = $row['c'];

$pendingTeachers = 0;
$stmt = DB::query("SELECT COUNT(*) as c FROM teacher_verification_requests WHERE status = 'pending'");
if ($stmt && $row = $stmt->get_result()->fetch_assoc()) $pendingTeachers = $row['c'];

$totalCreditsToday = 0;
$today = date('Ymd');
$stmt = DB::query("SELECT SUM(credits_used) as c FROM user_credit_daily WHERE yyyymmdd = ?", [$today], "s");
if ($stmt && $row = $stmt->get_result()->fetch_assoc()) $totalCreditsToday = $row['c'] ?? 0;

// Fetch pending verifications
$verifications = [];
$stmt = DB::query("SELECT t.*, u.full_name, u.email, u.status as user_status FROM teacher_verification_requests t JOIN users u ON t.user_id = u.id WHERE t.status = 'pending' ORDER BY t.submitted_at ASC");
if ($stmt) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $verifications[] = $row;
}


// Weekly Usage Chart Data
$chartLabels = [];
$chartData = [];
$chartCost = [];
$stmt = DB::query("SELECT DATE(created_at) as d, SUM(audio_tokens_in + text_tokens_in + text_tokens_out) as t, SUM(estimated_cost_total) as c FROM speech_token_cost_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");
if ($stmt && $res = $stmt->get_result()) {
    while ($r = $res->fetch_assoc()) {
        $chartLabels[] = date('M d', strtotime($r['d']));
        $chartData[] = (int)$r['t'];
        $chartCost[] = (float)$r['c'];
    }
}

// Top IPs (by Cost)
$topIPs = [];
$stmt = DB::query("SELECT user_ip, SUM(speech_count) as total_req, SUM(cost_total_est) as total_cost FROM ip_usage_monthly GROUP BY user_ip ORDER BY total_cost DESC LIMIT 5");
if ($stmt && $res = $stmt->get_result()) while ($r = $res->fetch_assoc()) $topIPs[] = $r;

// Top Countries
$topCountries = [];
$stmt = DB::query("SELECT country_code, COUNT(*) as c FROM users GROUP BY country_code ORDER BY c DESC LIMIT 5");
if ($stmt && $res = $stmt->get_result()) while ($r = $res->fetch_assoc()) $topCountries[] = $r;

// Blocked IPs
$blockedIPs = Security::getBlockedIPs();
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | PyViz</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --bg-color: #0f172a;
            --text-color: #e2e8f0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="h-screen flex overflow-hidden bg-slate-950">

    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-10 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- Sidebar -->
    <aside id="admin-sidebar" class="w-64 glass-panel border-r border-slate-800 flex flex-col fixed md:relative z-20 h-full transition-transform transform -translate-x-full md:translate-x-0 bg-slate-900">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <h1 class="text-xl font-bold text-red-500"><i class="fa-solid fa-shield-halved mr-2"></i>VDraw Admin</h1>
            <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <button onclick="switchTab('overview')" class="w-full text-left px-4 py-3 rounded bg-brand-600 text-white transition font-medium"><i class="fa-solid fa-gauge mr-3"></i> Overview</button>
            <button onclick="switchTab('users')" class="w-full text-left px-4 py-3 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium"><i class="fa-solid fa-users mr-3"></i> Users</button>
            <button onclick="switchTab('teachers')" class="w-full text-left px-4 py-3 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium"><i class="fa-solid fa-chalkboard-user mr-3"></i> Teachers <span class="ml-auto bg-yellow-500 text-black text-[10px] px-1.5 rounded-full font-bold <?php echo $pendingTeachers > 0 ? '' : 'hidden'; ?>"><?php echo $pendingTeachers; ?></span></button>
            <button onclick="switchTab('reports')" class="w-full text-left px-4 py-3 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium"><i class="fa-solid fa-chart-pie mr-3"></i> Reports</button>
            <button onclick="switchTab('settings')" class="w-full text-left px-4 py-3 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium"><i class="fa-solid fa-sliders mr-3"></i> Settings</button>
            <button onclick="switchTab('security')" class="w-full text-left px-4 py-3 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium"><i class="fa-solid fa-shield-halved mr-3"></i> Security</button>
            <button onclick="switchTab('ads')" class="w-full text-left px-4 py-3 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium"><i class="fa-solid fa-rectangle-ad mr-3"></i> Ads Manager</button>
            <button onclick="switchTab('apps')" class="w-full text-left px-4 py-3 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium"><i class="fa-solid fa-cube mr-3"></i> Apps</button>
            <button onclick="switchTab('changelog')" class="w-full text-left px-4 py-3 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium"><i class="fa-solid fa-clock-rotate-left mr-3"></i> Change Logs</button>
            <a href="../email/index.php" class="block w-full text-left px-4 py-3 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium"><i class="fa-solid fa-envelope mr-3"></i> Email Manager</a>
            <a href="../../frontend/PyViz/index.php" class="block px-4 py-3 rounded hover:bg-slate-800 transition text-slate-400"><i class="fa-solid fa-code mr-3"></i> Playground</a>
        </nav>
        <div class="p-4 border-t border-slate-800">
            <button onclick="logout()" class="w-full py-2 bg-slate-800 text-slate-300 border border-slate-700 rounded hover:bg-slate-700 transition">
                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-4 md:p-8 relative">
        <!-- Mobile Header -->
        <div class="md:hidden flex items-center mb-6">
            <button onclick="toggleSidebar()" class="text-slate-400 hover:text-white mr-4"><i class="fa-solid fa-bars text-xl"></i></button>
            <h2 class="text-lg font-bold text-white">Dashboard</h2>
        </div>
        <?php if (isset($_GET['msg'])):
            $msg = $_GET['msg'];
            $isErr = stripos($msg, 'Error') !== false || stripos($msg, 'Failed') !== false;
        ?>
            <div id="toast-msg" onclick="this.remove()" class="absolute top-4 right-4 <?php echo $isErr ? 'bg-red-500' : 'bg-green-500'; ?> text-white px-4 py-2 rounded shadow-lg cursor-pointer animate-fade-in z-50">
                <i class="fa-solid <?php echo $isErr ? 'fa-triangle-exclamation' : 'fa-check'; ?> mr-2"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
            <script>
                if (history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('msg');
                    window.history.replaceState(null, '', url);
                }
                setTimeout(() => {
                    const toast = document.getElementById('toast-msg');
                    if (toast) toast.remove();
                }, 3000);
            </script>
        <?php endif; ?>

        <!-- Content Wrapper -->
        <div class="max-w-7xl mx-auto">

            <div id="tab-overview" class="tab-content">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div class="flex items-center gap-4">
                        <h2 class="text-2xl font-bold text-white">System Overview</h2>
                        <span class="px-2 py-1 rounded bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-wider animate-pulse">Live</span>
                    </div>
                    <div class="space-x-2">
                        <button onclick="location.reload()" class="px-3 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-sm border border-slate-700"><i class="fa-solid fa-sync mr-2"></i>Refresh</button>
                        <a href="export_report.php?type=users" class="px-3 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-sm border border-slate-700"><i class="fa-solid fa-file-csv mr-2"></i>Users CSV</a>
                        <a href="export_report.php?type=usage" class="px-3 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-sm border border-slate-700"><i class="fa-solid fa-file-invoice-dollar mr-2"></i>Usage CSV</a>
                    </div>
                </div>
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="glass-panel p-6 rounded-xl border-l-4 border-blue-500">
                        <div class="text-slate-400 text-xs uppercase font-bold">Total Users</div>
                        <div class="text-3xl font-bold text-white mt-1"><?php echo $totalUsers; ?></div>
                    </div>
                    <div class="glass-panel p-6 rounded-xl border-l-4 border-yellow-500">
                        <div class="text-slate-400 text-xs uppercase font-bold">Pending Teachers</div>
                        <div class="text-3xl font-bold text-white mt-1"><?php echo $pendingTeachers; ?></div>
                    </div>
                    <div class="glass-panel p-6 rounded-xl border-l-4 border-green-500">
                        <div class="text-slate-400 text-xs uppercase font-bold">Credits Used Today</div>
                        <div class="text-3xl font-bold text-white mt-1"><?php echo $totalCreditsToday; ?></div>
                    </div>
                    <div class="glass-panel p-6 rounded-xl border-l-4 border-red-500">
                        <div class="text-slate-400 text-xs uppercase font-bold">Blocked IPs</div>
                        <div class="text-3xl font-bold text-white mt-1"><?php echo count($blockedIPs); ?></div>
                    </div>
                </div>

                <!-- Charts & Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Chart -->
                    <div class="glass-panel p-6 rounded-xl border border-slate-700 flex flex-col">
                        <h3 class="font-bold text-white mb-4">Weekly Token Usage</h3>
                        <div class="flex-1 relative min-h-[250px]"><canvas id="usageChart"></canvas></div>
                    </div>
                    <!-- Stats: Countries & IPs -->
                    <!-- Stats: Countries & IPs -->
                    <div class="space-y-6">
                        <!-- Top Countries -->
                        <div class="glass-panel p-6 rounded-xl border border-slate-700 max-h-[200px] overflow-y-auto custom-scrollbar">
                            <h3 class="font-bold text-white mb-2 text-sm uppercase">Top Countries</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-slate-400">
                                    <tbody>
                                        <?php foreach ($topCountries as $c): ?>
                                            <tr class="border-b border-slate-800/50 hover:bg-slate-800/30">
                                                <td class="py-2 flex items-center gap-2"><?php if (strtolower($c['country_code']) !== 'xx'): ?><img src="https://flagcdn.com/20x15/<?php echo strtolower($c['country_code']); ?>.png" class="h-3 rounded-sm"><?php endif; ?> <?php echo $c['country_code']; ?></td>
                                                <td class="py-2 text-right font-bold text-white"><?php echo $c['c']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($topCountries)): ?><tr>
                                                <td colspan="2" class="py-2 italic">No country data.</td>
                                            </tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Top IPs -->
                        <div class="glass-panel p-6 rounded-xl border border-slate-700 max-h-[200px] overflow-y-auto custom-scrollbar">
                            <h3 class="font-bold text-white mb-2 text-sm uppercase">Top Activity IPs</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-slate-400">
                                    <tbody>
                                        <?php foreach ($topIPs as $ip): ?>
                                            <tr class="border-b border-slate-800/50 hover:bg-slate-800/30">
                                                <td class="py-2 font-mono"><?php echo $ip['user_ip']; ?></td>
                                                <td class="py-2 text-right"><span class="text-white font-bold"><?php echo $ip['total_req']; ?></span> reqs</td>
                                                <td class="py-2 text-right text-green-400">$<?php echo number_format($ip['total_cost'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($topIPs)): ?><tr>
                                                <td colspan="3" class="py-2 italic">No IP data.</td>
                                            </tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>




                <!-- Chart Init Script -->
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const ctx = document.getElementById('usageChart');
                        if (ctx) {
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: <?php echo json_encode($chartLabels); ?>,
                                    datasets: [{
                                            label: 'Tokens',
                                            data: <?php echo json_encode($chartData); ?>,
                                            borderColor: '#3b82f6',
                                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                            borderWidth: 2,
                                            tension: 0.4,
                                            fill: true,
                                            yAxisID: 'y'
                                        },
                                        {
                                            label: 'Cost ($)',
                                            data: <?php echo json_encode($chartCost); ?>,
                                            borderColor: '#22c55e',
                                            fill: false,
                                            yAxisID: 'y1'
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            labels: {
                                                color: '#cbd5e1'
                                            }
                                        },
                                        tooltip: {
                                            mode: 'index',
                                            intersect: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            position: 'left',
                                            grid: {
                                                color: 'rgba(255,255,255,0.05)'
                                            },
                                            ticks: {
                                                color: '#94a3b8'
                                            },
                                            title: {
                                                display: true,
                                                text: 'Tokens',
                                                color: '#64748b'
                                            }
                                        },
                                        y1: {
                                            beginAtZero: true,
                                            position: 'right',
                                            grid: {
                                                drawOnChartArea: false
                                            },
                                            ticks: {
                                                color: '#22c55e',
                                                callback: (val) => '$' + val
                                            },
                                            title: {
                                                display: true,
                                                text: 'Cost',
                                                color: '#22c55e'
                                            }
                                        },
                                        x: {
                                            grid: {
                                                display: false
                                            },
                                            ticks: {
                                                color: '#94a3b8'
                                            }
                                        }
                                    },
                                    interaction: {
                                        mode: 'nearest',
                                        axis: 'x',
                                        intersect: false
                                    }
                                }
                            });
                        }
                    });
                </script>
            </div>

        </div>

        <!-- TAB 2: Users -->
        <div id="tab-users" class="tab-content hidden">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">User Management</h2>
            </div>

            <?php
            // Fetch Limits
            $limitsDay = ['student' => 20, 'teacher' => 100, 'teacher_verified' => 500];
            $limitsMonth = ['student' => 200, 'teacher' => 500, 'teacher_verified' => 2000];
            $resS = DB::query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'limit_%'");
            if ($resS && $rS = $resS->get_result()) {
                while ($row = $rS->fetch_assoc()) {
                    if (strpos($row['setting_key'], 'limit_daily_') === 0) {
                        $k = str_replace('limit_daily_', '', $row['setting_key']);
                        if ((int)$row['setting_value'] > 0) $limitsDay[$k] = (int)$row['setting_value'];
                    } elseif (strpos($row['setting_key'], 'limit_monthly_') === 0) {
                        $k = str_replace('limit_monthly_', '', $row['setting_key']);
                        if ((int)$row['setting_value'] > 0) $limitsMonth[$k] = (int)$row['setting_value'];
                    }
                }
            }

            // Filter Logic
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
            if ($limit === -1) $limit = 1000000;
            $offset = ($page - 1) * $limit;

            $s_role = $_GET['search_role'] ?? '';
            $s_status = $_GET['search_status'] ?? '';
            $s_email = $_GET['search_email'] ?? '';
            $s_date_start = $_GET['search_date_start'] ?? '';
            $s_date_end = $_GET['search_date_end'] ?? '';

            $where = ["u.role != 'admin'"]; // Exclude main admin from list? User requested: "Modify User Management tab to exclude the 'admin' user". Wait, "exclude the 'admin' user from the list".
            // Previous code showed admin if role='admin'.
            // Requirement 2: "Modify the User Management tab to exclude the 'admin' user from the list" (from original objective).
            // I will exclude role='admin' OR just specific 'admin' user? "exclude the 'admin' user". Usually means the super admin.
            // I'll add `u.role != 'admin'` to be safe, or `u.email != 'admin@vdraw.cc'`.
            // User said "admin users are displayed ... but without any action options".
            // Wait. Requirement 9: "Ensure admin users are displayed... but without any action options".
            // So I MUST display them.
            // So I will REMOVE `u.role != 'admin'`.

            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($s_role) {
                $where[] = "u.role = ?";
                $params[] = $s_role;
                $types .= "s";
            }
            if ($s_status) {
                $where[] = "u.status = ?";
                $params[] = $s_status;
                $types .= "s";
            }
            if ($s_email) {
                $where[] = "u.email LIKE ?";
                $params[] = "%$s_email%";
                $types .= "s";
            }
            if ($s_date_start) {
                $where[] = "DATE(u.created_at) >= ?";
                $params[] = $s_date_start;
                $types .= "s";
            }
            if ($s_date_end) {
                $where[] = "DATE(u.created_at) <= ?";
                $params[] = $s_date_end;
                $types .= "s";
            }

            $whereStr = implode(" AND ", $where);

            // Count
            $stmtC = DB::query("SELECT COUNT(*) as total FROM users u WHERE $whereStr", $params, $types);
            $totalRecords = ($stmtC && $resC = $stmtC->get_result()) ? $resC->fetch_assoc()['total'] : 0;
            $totalPages = ($limit > 0) ? ceil($totalRecords / $limit) : 1;

            // Data
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            $users = [];
            $curDay = date('Ymd');
            $curMonth = date('Ym');
            $q = "SELECT u.*, 
                        MAX(COALESCE(ucd.credits_used, 0)) as daily_used,
                        MAX(COALESCE(ucm.credits_used, 0)) as monthly_used,
                        SUM(COALESCE(l.audio_tokens_in,0)) as audio_used, 
                        SUM(COALESCE(l.text_tokens_in,0) + COALESCE(l.text_tokens_out,0)) as text_used,
                        SUM(COALESCE(l.estimated_cost_total,0)) as total_cost,
                        (SELECT code6 FROM user_email_verifications WHERE user_id = u.id ORDER BY id DESC LIMIT 1) as latest_verification_code
                      FROM users u 
                      LEFT JOIN user_credit_daily ucd ON u.id = ucd.user_id AND ucd.yyyymmdd = '$curDay'
                      LEFT JOIN user_credit_monthly ucm ON u.id = ucm.user_id AND ucm.yyyymm = '$curMonth'
                      LEFT JOIN speech_token_cost_log l ON u.id = l.user_id
                      WHERE $whereStr 
                      GROUP BY u.id 
                      ORDER BY u.created_at DESC 
                      LIMIT ? OFFSET ?";

            // DB::query wrapper usually rebuilds connection if needed? Assuming yes.
            // Note: DB::query in typical simplified classes might not support types string properly with empty params. 
            // If types is empty, params should be empty.
            if (empty($types)) $types = "";

            $stmtU = DB::query($q, $params, $types);
            if ($stmtU && $resU = $stmtU->get_result()) {
                while ($r = $resU->fetch_assoc()) $users[] = $r;
            }
            ?>

            <!-- Filters -->
            <form method="GET" class="bg-slate-900/50 p-4 rounded-xl border border-slate-700 mb-6 grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                <!-- Maintain Filter Tab Active -->
                <script>
                    if (window.location.search.includes('search_') || window.location.search.includes('page=')) {
                        document.addEventListener('DOMContentLoaded', () => {
                            // Simple hack to wait for switchTab definition, or assume it's there
                            setTimeout(() => switchTab('users'), 100);
                        });
                    }
                </script>

                <div class="col-span-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Role</label>
                    <select name="search_role" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-xs text-white focus:border-brand-500 outline-none">
                        <option value="">All Roles</option>
                        <option value="student" <?php echo $s_role == 'student' ? 'selected' : ''; ?>>Student</option>
                        <option value="teacher" <?php echo $s_role == 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                        <option value="admin" <?php echo $s_role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                    <select name="search_status" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-xs text-white focus:border-brand-500 outline-none">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $s_status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="blocked" <?php echo $s_status == 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                    </select>
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                    <input type="text" name="search_email" value="<?php echo htmlspecialchars($s_email); ?>" placeholder="Search email..." class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-xs text-white focus:border-brand-500 outline-none">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">From</label>
                    <input type="date" name="search_date_start" value="<?php echo htmlspecialchars($s_date_start); ?>" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-xs text-white focus:border-brand-500 outline-none">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">To</label>
                    <input type="date" name="search_date_end" value="<?php echo htmlspecialchars($s_date_end); ?>" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-xs text-white focus:border-brand-500 outline-none">
                </div>
                <div class="col-span-1 flex gap-2">
                    <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 rounded text-xs transition">Filter</button>
                    <a href="index.php" class="px-3 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded text-xs transition"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>

            <div class="glass-panel rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-400">
                        <thead class="bg-slate-800/50 text-slate-200 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">User & Stats</th>
                                <th class="px-6 py-3">Credits Left (Daily | Monthly)</th>
                                <th class="px-6 py-3">Lifetime Usage</th>
                                <th class="px-6 py-3">Joined / Last</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <?php foreach ($users as $u):
                                $isAdm = $u['role'] === 'admin';
                            ?>
                                <tr class="hover:bg-slate-800/30">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <!-- Avatar -->
                                            <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-sm font-bold text-white relative shrink-0">
                                                <?php echo substr($u['full_name'], 0, 1); ?>
                                                <?php if (empty($u['email_verified_at'])): ?>
                                                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <!-- Info Horizontal/Wrapped -->
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-white font-medium"><?php echo htmlspecialchars($u['full_name']); ?></span>
                                                    <?php if (empty($u['email_verified_at'])): ?>
                                                        <span class="text-[9px] bg-red-900/50 text-red-400 px-1 py-0.5 rounded uppercase font-bold tracking-wide">Unverified</span>
                                                        <?php if (!empty($u['latest_verification_code'])): ?>
                                                            <span class="text-[9px] font-mono bg-slate-700 text-slate-300 px-1 py-0.5 rounded ml-1" title="Latest Verification Code"><i class="fa-solid fa-key mr-1 text-[8px]"></i><?php echo $u['latest_verification_code']; ?></span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-slate-500 mb-1"><?php echo htmlspecialchars($u['email']); ?></div>
                                                <div class="flex gap-2">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?php echo $u['role'] === 'teacher' ? 'bg-blue-900 text-blue-300' : ($isAdm ? 'bg-purple-900 text-purple-300' : 'bg-slate-700 text-slate-300'); ?>">
                                                        <?php echo ($u['role'] === 'teacher' && !empty($u['teacher_verified'])) ? 'Verified Teacher' : ucfirst($u['role']); ?>
                                                    </span>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?php echo $u['status'] === 'active' ? 'bg-green-900 text-green-300' : 'bg-red-600 text-white'; ?>">
                                                        <?php echo $u['status'] === 'active' ? 'Active' : ($u['status'] === 'deactivated' ? 'Deactivated' : 'Blocked'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Horizontal Usage Stats -->
                                    <td class="px-6 py-4">
                                        <?php
                                        $uRole = $u['role'];
                                        $isVer = ($uRole === 'teacher' && !empty($u['teacher_verified']));

                                        // Daily
                                        $dLim = ($u['quota_daily'] > 0) ? $u['quota_daily'] : ($isVer ? ($limitsDay['teacher_verified'] ?? 500) : ($limitsDay[$uRole] ?? 20));

                                        // Monthly
                                        $mLim = ($u['quota_monthly'] > 0) ? $u['quota_monthly'] : ($isVer ? ($limitsMonth['teacher_verified'] ?? 2000) : ($limitsMonth[$uRole] ?? 200));

                                        $dLeft = max(0, $dLim - ($u['daily_used'] ?? 0));
                                        $reward = $u['reward_credits'] ?? 0;
                                        $mLeft = max(0, $mLim - ($u['monthly_used'] ?? 0));
                                        ?>
                                        <div class="flex flex-col gap-1 text-xs bg-slate-900/40 p-2 rounded border border-slate-700/50 w-fit">
                                            <div class="flex justify-between w-48 border-b border-slate-700/50 pb-1 mb-1">
                                                <span class="text-slate-500">Daily Left:</span>
                                                <span class="font-mono text-white"><?php echo $dLeft; ?> <span class="text-slate-600">/ <?php echo $dLim; ?></span></span>
                                            </div>
                                            <div class="flex justify-between w-48">
                                                <span class="text-slate-500">Monthly Left:</span>
                                                <span class="font-mono text-white"><?php echo $mLeft; ?></span>
                                            </div>
                                            <div class="flex justify-between w-48 bg-brand-900/20 px-1 rounded mt-1">
                                                <span class="text-brand-400 font-bold">Reward Left:</span>
                                                <span class="font-mono text-brand-300"><?php echo $reward; ?></span>
                                            </div>
                                            <button onclick="openRewardModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['email']); ?>')" class="text-[10px] text-blue-400 hover:text-white text-right mt-1 w-48 block transition"><i class="fa-solid fa-gift mr-1"></i>Add Reward</button>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1 text-xs font-mono">
                                            <div title="Audio Tokens"><i class="fa-solid fa-microphone text-slate-500 w-4"></i> <?php echo number_format($u['audio_used']); ?></div>
                                            <div title="Text Tokens"><i class="fa-solid fa-font text-slate-500 w-4"></i> <?php echo number_format($u['text_used']); ?></div>
                                            <div title="Total Cost" class="text-green-400 font-bold"><i class="fa-solid fa-dollar-sign w-4"></i> <?php echo number_format($u['total_cost'], 4); ?></div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-xs whitespace-nowrap">
                                        <div class="text-slate-300"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></div>
                                        <?php if (!empty($u['last_login_at'])): ?>
                                            <div class="text-[10px] text-slate-500 mt-1">Last: <?php echo date('M d H:i', strtotime($u['last_login_at'])); ?></div>
                                        <?php else: ?>
                                            <div class="text-[10px] text-slate-600 mt-1 italic">Never</div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                        <?php if (!$isAdm): ?>
                                            <button onclick="viewUserLogs('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>')" title="View Activities" class="hover:bg-slate-700 text-slate-300 border border-slate-600 p-1.5 rounded transition"><i class="fa-solid fa-list-check"></i></button>
                                            <button onclick="openEditUser('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>', '<?php echo $u['role']; ?>')" title="Edit User" class="hover:bg-purple-900/50 text-purple-400 border border-purple-900 p-1.5 rounded transition"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <?php if (empty($u['email_verified_at'])): ?>
                                                <button onclick="performUserAction('manual_verify_email', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>')" title="Verify Email (Manual)" class="hover:bg-cyan-900/50 text-cyan-400 border border-cyan-900 p-1.5 rounded transition"><i class="fa-solid fa-envelope-circle-check"></i></button>
                                                <button onclick="sendVerificationEmail('<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>')" title="Send Verification Email" class="hover:bg-orange-900/50 text-orange-400 border border-orange-900 p-1.5 rounded transition"><i class="fa-solid fa-paper-plane"></i></button>
                                            <?php endif; ?>

                                            <?php if ($u['status'] === 'active'): ?>
                                                <button onclick="performUserAction('block', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>')" title="Block" class="hover:bg-red-900/50 text-red-400 border border-red-900 p-1.5 rounded transition"><i class="fa-solid fa-ban"></i></button>
                                            <?php else: ?>
                                                <button onclick="performUserAction('unblock', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>')" title="Unblock" class="hover:bg-green-900/50 text-green-400 border border-green-900 p-1.5 rounded transition"><i class="fa-solid fa-unlock"></i></button>
                                            <?php endif; ?>

                                            <?php if ($u['role'] === 'teacher'): ?>
                                                <?php if ($u['teacher_verified']): ?>
                                                    <button onclick="performUserAction('unverify_teacher', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>')" title="Revoke Status" class="hover:bg-yellow-900/50 text-yellow-400 border border-yellow-900 p-1.5 rounded transition"><i class="fa-solid fa-user-xmark"></i></button>
                                                <?php else: ?>
                                                    <?php // Disable if blocked 
                                                    $disabled = $u['status'] !== 'active';
                                                    ?>
                                                    <button onclick="performUserAction('verify_teacher', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>')" <?php echo $disabled ? 'disabled class="opacity-50 cursor-not-allowed text-blue-400 border border-blue-900 p-1.5 rounded"' : 'class="hover:bg-blue-900/50 text-blue-400 border border-blue-900 p-1.5 rounded transition"'; ?> title="Verify Teacher"><i class="fa-solid fa-user-check"></i></button>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- Hard Delete Button -->
                                            <button onclick="confirmHardDelete('<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>')" title="Permanently Delete" class="hover:bg-red-700 text-red-500 border border-red-800 p-1.5 rounded transition ml-1"><i class="fa-solid fa-trash"></i></button>
                                        <?php else: ?>
                                            <span class="text-slate-600 text-xs italic pr-2">Adm</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500 italic">No users found matching filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-slate-800/50 border-t border-slate-800 p-4 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-400">
                    <div>
                        Showing <?php echo count($users); ?> of <?php echo $totalRecords; ?> users
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="GET" class="flex items-center gap-2 mr-4">
                            <?php // Keep current filters
                            foreach ($_GET as $k => $v) {
                                if ($k == 'limit' || $k == 'page') continue;
                                echo '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '">';
                            }
                            ?>
                            <span class="text-slate-500">Show:</span>
                            <select name="limit" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded px-2 py-1 focus:border-brand-500 outline-none">
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                                <option value="-1" <?php echo $limit == -1 ? 'selected' : ''; ?>>All</option>
                            </select>
                        </form>

                        <?php if ($totalPages > 1): ?>
                            <div class="flex gap-1">
                                <?php
                                $qParams = $_GET;
                                unset($qParams['page']);
                                $baseLink = '?' . http_build_query($qParams) . '&page=';
                                ?>
                                <a href="<?php echo $page > 1 ? $baseLink . ($page - 1) : '#'; ?>" class="px-2 py-1 rounded border border-slate-700 <?php echo $page > 1 ? 'hover:bg-slate-700 text-white' : 'opacity-50 cursor-not-allowed'; ?>"><i class="fa-solid fa-chevron-left"></i></a>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                                        <a href="<?php echo $baseLink . $i; ?>" class="px-2 py-1 rounded border <?php echo $i == $page ? 'bg-brand-600 border-brand-500 text-white' : 'border-slate-700 hover:bg-slate-700'; ?>"><?php echo $i; ?></a>
                                    <?php elseif ($i == $page - 2 || $i == $page + 2): ?>
                                        <span class="px-1 text-slate-600">..</span>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <a href="<?php echo $page < $totalPages ? $baseLink . ($page + 1) : '#'; ?>" class="px-2 py-1 rounded border border-slate-700 <?php echo $page < $totalPages ? 'hover:bg-slate-700 text-white' : 'opacity-50 cursor-not-allowed'; ?>"><i class="fa-solid fa-chevron-right"></i></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Teachers -->
        <div id="tab-teachers" class="tab-content hidden">
            <h2 class="text-2xl font-bold text-white mb-6">Teacher Verifications</h2>
            <div class="glass-panel rounded-xl overflow-hidden mb-8">
                <div class="p-4 border-b border-slate-700 bg-slate-900/50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-200">Pending Requests</h3>
                </div>
                <table class="w-full text-left text-sm text-slate-400">
                    <thead class="bg-slate-800/50 text-slate-200 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3">User</th>
                            <th class="px-6 py-3">Submitted</th>
                            <th class="px-6 py-3">Document</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($verifications as $v): ?>
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-6 py-3">
                                    <div class="text-white font-bold"><?php echo htmlspecialchars($v['full_name']); ?></div>
                                    <div class="text-xs"><?php echo htmlspecialchars($v['email']); ?></div>
                                </td>
                                <td class="px-6 py-3"><?php echo $v['submitted_at']; ?></td>
                                <td class="px-6 py-3">
                                    <a href="view_document.php?id=<?php echo $v['id']; ?>" target="_blank" class="text-blue-400 underline hover:text-blue-300">View File</a>
                                </td>
                                <td class="px-6 py-3 text-right space-x-2">
                                    <?php if (($v['user_status'] ?? 'active') !== 'active'): ?>
                                        <button disabled class="px-3 py-1 bg-slate-700 text-slate-500 rounded text-xs cursor-not-allowed" title="User Deactivated">Approve</button>
                                        <button disabled class="px-3 py-1 bg-slate-700 text-slate-500 rounded text-xs cursor-not-allowed" title="User Deactivated">Reject</button>
                                    <?php else: ?>
                                        <button onclick="performUserAction('verify_teacher', '<?php echo $v['email']; ?>')" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-500 text-xs">Approve</button>
                                        <button onclick="performUserAction('unverify_teacher', '<?php echo $v['email']; ?>')" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-500 text-xs">Reject</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($verifications)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center italic text-slate-600">No pending requests.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 4: Settings -->
        <div id="tab-settings" class="tab-content hidden">
            <?php
            require_once 'Settings.php';
            $settings = AdminSettings::loadSettings();
            $envContent = AdminSettings::loadEnv();
            ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- General Settings & Toggles -->
                <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2">Feature Toggles</h3>
                    <form method="POST" action="Settings.php" class="space-y-4">
                        <input type="hidden" name="action" value="save_settings">

                        <div class="flex items-center justify-between p-3 bg-slate-900 rounded">
                            <span class="text-slate-300">Enable Speech-to-Code</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="stt_enabled" class="sr-only peer" <?php echo ($settings['stt_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-900 rounded">
                            <span class="text-slate-300">Enable Editor Mode</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="editor_enabled" class="sr-only peer" <?php echo ($settings['editor_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-900 rounded">
                            <span class="text-slate-300">Enable New Registrations</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="new_registrations" class="sr-only peer" <?php echo ($settings['new_registrations'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-900 rounded">
                            <span class="text-slate-300">Enable User Tracking</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="tracking_enabled" class="sr-only peer" <?php echo ($settings['tracking_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        <!-- Operational Parameters Section -->
                        <div class="mt-8">
                            <h3 class="flex items-center text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2">
                                <i class="fa-solid fa-gears mr-2 text-brand-400"></i> Operational Parameters
                            </h3>

                            <!-- Category: User Rate Limits -->
                            <div class="mb-6">
                                <h4 class="text-xs uppercase font-bold text-slate-500 mb-3 tracking-wider">User Daily Request Limits</h4>
                                <div class="grid grid-cols-3 gap-3">
                                    <!-- Student -->
                                    <div class="bg-slate-900/50 p-3 rounded border border-slate-700 flex flex-col">
                                        <label class="text-[10px] text-slate-400 font-bold mb-1 flex items-center"><i class="fa-solid fa-graduation-cap mr-1"></i> Student</label>
                                        <input type="number" name="limit_daily_student" value="<?php echo $settings['limit_daily_student'] ?? 20; ?>" class="bg-transparent text-white font-mono text-lg font-bold focus:outline-none w-full border-b border-slate-700 focus:border-brand-500 transition-colors">
                                    </div>
                                    <!-- Teacher -->
                                    <div class="bg-slate-900/50 p-3 rounded border border-slate-700 flex flex-col">
                                        <label class="text-[10px] text-slate-400 font-bold mb-1 flex items-center"><i class="fa-solid fa-chalkboard-user mr-1"></i> Teacher (Std)</label>
                                        <input type="number" name="limit_daily_teacher" value="<?php echo $settings['limit_daily_teacher'] ?? 100; ?>" class="bg-transparent text-white font-mono text-lg font-bold focus:outline-none w-full border-b border-slate-700 focus:border-brand-500 transition-colors">
                                    </div>
                                    <!-- Verified Teacher -->
                                    <div class="bg-green-900/20 p-3 rounded border border-green-900/50 flex flex-col relative overflow-hidden">
                                        <div class="absolute top-0 right-0 w-8 h-8 bg-green-500/10 rounded-bl-xl"></div>
                                        <label class="text-[10px] text-green-400 font-bold mb-1 flex items-center"><i class="fa-solid fa-circle-check mr-1"></i> Verified Teacher</label>
                                        <input type="number" name="limit_daily_teacher_verified" value="<?php echo $settings['limit_daily_teacher_verified'] ?? 500; ?>" class="bg-transparent text-white font-mono text-lg font-bold focus:outline-none w-full border-b border-green-800 focus:border-green-400 transition-colors">
                                    </div>
                                </div>
                            </div>

                            <!-- Category: IP Limits -->
                            <div class="mb-6">
                                <h4 class="text-xs uppercase font-bold text-slate-500 mb-3 tracking-wider">IP Auto-Block Limits</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-red-900/10 p-3 rounded border border-red-900/30 flex flex-col">
                                        <label class="text-[10px] text-red-400 font-bold mb-1"><i class="fa-solid fa-server mr-1"></i> Max Requests / Day</label>
                                        <input type="number" name="max_ip_daily" value="<?php echo $settings['max_ip_daily'] ?? 100; ?>" class="bg-transparent text-white font-mono text-lg font-bold focus:outline-none w-full border-b border-red-800 focus:border-red-400 transition-colors">
                                    </div>
                                    <div class="bg-red-900/10 p-3 rounded border border-red-900/30 flex flex-col">
                                        <label class="text-[10px] text-red-400 font-bold mb-1"><i class="fa-solid fa-calendar mr-1"></i> Max Requests / Month</label>
                                        <input type="number" name="max_ip_monthly" value="<?php echo $settings['max_ip_monthly'] ?? 2000; ?>" class="bg-transparent text-white font-mono text-lg font-bold focus:outline-none w-full border-b border-red-800 focus:border-red-400 transition-colors">
                                    </div>
                                </div>
                            </div>

                            <!-- Category: Resource Pricing -->
                            <div>
                                <h4 class="text-xs uppercase font-bold text-slate-500 mb-3 tracking-wider">Model Token Pricing & Factors</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Audio Cost -->
                                    <div class="bg-slate-900 p-3 rounded border border-slate-700">
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">Audio Input (per min)</label>
                                        <div class="flex items-center">
                                            <span class="text-slate-500 text-sm mr-1">$</span>
                                            <input type="number" step="0.001" name="cost_audio_min" value="<?php echo $settings['cost_audio_min'] ?? 0.06; ?>" class="w-full bg-transparent text-white font-mono focus:outline-none">
                                        </div>
                                    </div>
                                    <!-- Audio Tokens -->
                                    <div class="bg-indigo-900/20 p-3 rounded border border-indigo-900/50">
                                        <label class="block text-[10px] font-bold text-indigo-400 mb-1">Audio Tokens / Sec</label>
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-coins text-indigo-500 mr-2 text-xs"></i>
                                            <input type="number" name="audio_tokens_per_sec" value="<?php echo $settings['audio_tokens_per_sec'] ?? 100; ?>" class="w-full bg-transparent text-white font-mono focus:outline-none">
                                        </div>
                                    </div>
                                    <!-- Text In -->
                                    <div class="bg-slate-900 p-3 rounded border border-slate-700">
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">Text Input (per 1M)</label>
                                        <div class="flex items-center">
                                            <span class="text-slate-500 text-sm mr-1">$</span>
                                            <input type="number" step="0.01" name="cost_text_in_1m" value="<?php echo $settings['cost_text_in_1m'] ?? 0.50; ?>" class="w-full bg-transparent text-white font-mono focus:outline-none">
                                        </div>
                                    </div>
                                    <!-- Text Out -->
                                    <div class="bg-slate-900 p-3 rounded border border-slate-700">
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">Text Output (per 1M)</label>
                                        <div class="flex items-center">
                                            <span class="text-slate-500 text-sm mr-1">$</span>
                                            <input type="number" step="0.01" name="cost_text_out_1m" value="<?php echo $settings['cost_text_out_1m'] ?? 1.50; ?>" class="w-full bg-transparent text-white font-mono focus:outline-none">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full mt-6 bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 rounded shadow-lg transition">Save General Settings</button>
                    </form>
                </div>

                <!-- Right Column: Security & Env -->
                <div class="flex flex-col gap-6">

                    <!-- SMTP Configuration -->
                    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                        <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2"><i class="fa-solid fa-envelope mr-2 text-brand-400"></i> SMTP Configuration</h3>
                        <form method="POST" action="Settings.php" class="space-y-4">
                            <input type="hidden" name="action" value="save_smtp">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1">SMTP Host</label>
                                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com" class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1">SMTP Port</label>
                                    <input type="number" name="smtp_port" value="<?php echo $settings['smtp_port'] ?? 587; ?>" class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1">Username</label>
                                    <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1">Password</label>
                                    <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white">
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-slate-700 hover:bg-brand-600 text-white font-bold py-2 rounded shadow transition">Update SMTP</button>
                        </form>
                    </div>

                    <!-- Admin Security -->
                    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 h-fit">
                        <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2"><i class="fa-solid fa-lock mr-2 text-brand-400"></i> Change Admin Password</h3>
                        <form method="POST" action="Settings.php" class="space-y-4">
                            <input type="hidden" name="action" value="change_password">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1">Current Password</label>
                                <input type="password" name="current_password" required class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1">New Password</label>
                                <input type="password" name="new_password" required minlength="6" class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white">
                            </div>
                            <button type="submit" class="w-full bg-slate-700 hover:bg-brand-600 text-white font-bold py-2 rounded shadow transition">Update Password</button>
                        </form>
                    </div>

                    <!-- Env Editor -->
                    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 flex flex-col flex-1">
                        <h3 class="text-lg font-bold text-white mb-2 border-b border-slate-700 pb-2 flex justify-between items-center">
                            <span>Environment Variables (.env)</span>
                            <span class="text-xs text-red-400 font-normal"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Handle with care</span>
                        </h3>
                        <form method="POST" action="Settings.php" class="flex-1 flex flex-col">
                            <input type="hidden" name="action" value="save_env">
                            <textarea name="env_content" class="flex-1 w-full bg-slate-950 font-mono text-xs text-green-400 p-4 rounded border border-slate-600 focus:border-brand-500 focus:outline-none resize-none" spellcheck="false"><?php echo htmlspecialchars($envContent); ?></textarea>
                            <button type="submit" onclick="return confirm('Please confirm! Changing .env values can break the application. Continue?')" class="w-full mt-4 bg-slate-700 hover:bg-red-600 text-white font-bold py-2 rounded shadow transition">Update .env File</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>



        <!-- TAB 5: Security -->
        <div id="tab-security" class="tab-content hidden">
            <h2 class="text-2xl font-bold text-white mb-6">Security & IP Control</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Block List -->
                <div class="md:col-span-2 glass-panel rounded-xl overflow-hidden">
                    <div class="p-4 border-b border-slate-700 bg-slate-900/50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-200">Blocked IPs</h3>
                    </div>
                    <table class="w-full text-left text-sm text-slate-400">
                        <thead class="bg-slate-800/50 text-slate-200 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">IP Address</th>
                                <th class="px-6 py-3">Reason</th>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <?php
                            // Security already required at top
                            $blockedIPs = Security::getBlockedIPs();
                            foreach ($blockedIPs as $b):
                            ?>
                                <tr class="hover:bg-slate-800/30">
                                    <td class="px-6 py-3 font-mono text-red-400"><?php echo htmlspecialchars($b['ip_address']); ?></td>
                                    <td class="px-6 py-3"><?php echo htmlspecialchars($b['reason']); ?></td>
                                    <td class="px-6 py-3 text-xs"><?php echo $b['created_at']; ?></td>
                                    <td class="px-6 py-3 text-right">
                                        <button onclick="performSecurityAction('unblock', '<?php echo $b['ip_address']; ?>')" class="text-slate-500 hover:text-white"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($blockedIPs)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center italic text-slate-600">No IPs blocked.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Manual Block Form -->
                <div class="glass-panel rounded-xl p-6 h-fit">
                    <h3 class="font-bold text-white mb-4">Block New IP</h3>
                    <form onsubmit="blockIP(event)" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1">IP Address</label>
                            <input type="text" name="ip" placeholder="e.g. 192.168.1.1" required pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1">Reason</label>
                            <input type="text" name="reason" placeholder="Abusive behavior..." class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-white">
                        </div>
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded shadow transition">Block Access</button>
                    </form>
                </div>

                <!-- Google Captcha Settings -->
                <div class="col-span-1 md:col-span-3 mt-4">
                    <div class="glass-panel p-6 rounded-xl border border-slate-700">
                        <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2 flex items-center">
                            <i class="fa-brands fa-google text-brand-400 mr-2"></i> Google reCAPTCHA v2 (Checkbox)
                        </h3>
                        <form method="POST" action="Settings.php" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <input type="hidden" name="action" value="save_security_settings">

                            <!-- Toggles -->
                            <div class="space-y-4">
                                <h4 class="text-xs uppercase font-bold text-slate-500 tracking-wider">Enable Protection</h4>
                                <div class="flex items-center justify-between p-3 bg-slate-900 rounded border border-slate-800">
                                    <span class="text-slate-300 text-sm">Enable on User Signup</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="captcha_enabled_signup" class="sr-only peer" <?php echo ($settings['captcha_enabled_signup'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-900 rounded border border-slate-800">
                                    <span class="text-slate-300 text-sm">Enable on User Login</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="captcha_enabled_login" class="sr-only peer" <?php echo ($settings['captcha_enabled_login'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- API Keys -->
                            <div class="space-y-4">
                                <h4 class="text-xs uppercase font-bold text-slate-500 tracking-wider">API Keys</h4>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1">Google Site Key</label>
                                    <input type="text" name="captcha_site_key" value="<?php echo htmlspecialchars($settings['captcha_site_key'] ?? ''); ?>" placeholder="6LeIXAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI" class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white font-mono text-xs">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1">Google Secret Key</label>
                                    <input type="password" name="captcha_secret_key" value="<?php echo htmlspecialchars($settings['captcha_secret_key'] ?? ''); ?>" placeholder="6LeIXAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe" class="w-full bg-slate-950 border border-slate-600 rounded p-2 text-white font-mono text-xs">
                                </div>
                                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 rounded shadow transition mt-2">Save Captcha Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- END TAB 5: Security -->

        </div>
        <div id="modal-logs" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center">
            <div class="glass-panel w-full max-w-4xl p-6 rounded-xl relative h-[80vh] flex flex-col bg-slate-900">
                <button onclick="document.getElementById('modal-logs').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
                <h2 class="text-xl font-bold text-white mb-4">Activity Logs: <span id="log-username" class="text-brand-400"></span></h2>

                <div class="flex border-b border-slate-700 mb-4">
                    <button onclick="switchLogTab('activity')" id="btn-log-activity" class="px-4 py-2 border-b-2 border-brand-500 text-brand-400 font-bold transition-colors">Activity</button>
                    <button onclick="switchLogTab('usage')" id="btn-log-usage" class="px-4 py-2 border-b-2 border-transparent text-slate-400 font-bold hover:text-white transition-colors">Token Usage</button>
                </div>

                <div id="log-content-activity" class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left text-xs text-slate-400">
                        <thead class="bg-slate-800 text-slate-200 sticky top-0">
                            <tr>
                                <th class="p-2">Time</th>
                                <th class="p-2">Type</th>
                                <th class="p-2">Description</th>
                                <th class="p-2">IP</th>
                                <th class="p-2">Country</th>
                            </tr>
                        </thead>
                        <tbody id="log-activity-body" class="divide-y divide-slate-800"></tbody>
                    </table>
                </div>
                <div id="log-content-usage" class="flex-1 overflow-y-auto custom-scrollbar hidden">
                    <table class="w-full text-left text-xs text-slate-400">
                        <thead class="bg-slate-800 text-slate-200 sticky top-0">
                            <tr>
                                <th class="p-2">Time</th>
                                <th class="p-2">Audio Tok</th>
                                <th class="p-2">Text Tok</th>
                                <th class="p-2">Aud Cost</th>
                                <th class="p-2">Txt Cost</th>
                                <th class="p-2">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody id="log-usage-body" class="divide-y divide-slate-800"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Edit User Modal -->
        <div id="modal-edit-user" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center">
            <div class="glass-panel w-full max-w-md p-6 rounded-xl relative bg-slate-900 border border-slate-700">
                <button onclick="document.getElementById('modal-edit-user').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
                <h2 class="text-xl font-bold text-white mb-4">Edit User</h2>
                <form onsubmit="submitEditUser(event)" class="space-y-4">
                    <input type="hidden" name="user_id" id="edit-user-id">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Email (Read Only)</label>
                        <input type="text" id="edit-user-email" disabled class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-slate-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Role</label>
                        <select name="role" id="edit-user-role" class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-white">
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">New Password (Leave blank to keep)</label>
                        <input type="password" name="password" placeholder="New Password" class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-white">
                    </div>
                    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 rounded shadow transition">Update User</button>
                </form>
            </div>
        </div>

        <!-- Modal: Add Reward -->
        <div id="modal-reward" class="fixed inset-0 bg-black/80 hidden flex items-center justify-center z-50 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-96 shadow-2xl relative">
                <button onclick="document.getElementById('modal-reward').classList.add('hidden')" class="absolute top-4 right-4 text-slate-500 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
                <h2 class="text-xl font-bold text-white mb-4">Add Reward Credits</h2>
                <form onsubmit="submitReward(event)" class="space-y-4">
                    <input type="hidden" name="user_id" id="reward-user-id">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">User</label>
                        <input type="text" id="reward-user-email" disabled class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-slate-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Amount</label>
                        <input type="number" name="amount" min="1" value="10" class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-white outline-none focus:border-brand-500">
                    </div>
                    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-2 rounded shadow transition">Add Credits</button>
                </form>
            </div>
        </div>
        <!-- TAB: Reports -->
        <div id="tab-reports" class="tab-content hidden">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">Reports & Analysis</h2>
                <button onclick="refreshActiveReportTab()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 rounded text-sm text-white"><i class="fa-solid fa-sync mr-2"></i>Refresh</button>
            </div>

            <!-- Sub-Tabs Navigation -->
            <div class="flex flex-wrap gap-1 bg-slate-800/50 p-1 rounded-lg mb-6">
                <button onclick="switchReportSubTab('tokens')" id="tab-btn-tokens" class="px-4 py-2 text-sm font-medium rounded-md text-white bg-brand-600 shadow transition-all">
                    Token Reports
                </button>
                <button onclick="switchReportSubTab('overview')" id="tab-btn-overview" class="px-4 py-2 text-sm font-medium rounded-md text-slate-400 hover:text-white transition-all">
                    Overview
                </button>
                <button onclick="switchReportSubTab('pyviz')" id="tab-btn-pyviz" class="px-4 py-2 text-sm font-medium rounded-md text-slate-400 hover:text-white transition-all">
                    PyViz
                </button>
                <button onclick="switchReportSubTab('stats')" id="tab-btn-stats" class="px-4 py-2 text-sm font-medium rounded-md text-slate-400 hover:text-white transition-all">
                    Stats
                </button>
                <button onclick="switchReportSubTab('linear')" id="tab-btn-linear" class="px-4 py-2 text-sm font-medium rounded-md text-slate-400 hover:text-white transition-all">
                    Linear
                </button>
                <button onclick="switchReportSubTab('graph')" id="tab-btn-graph" class="px-4 py-2 text-sm font-medium rounded-md text-slate-400 hover:text-white transition-all">
                    Graph
                </button>
                <button onclick="switchReportSubTab('dviz')" id="tab-btn-dviz" class="px-4 py-2 text-sm font-medium rounded-md text-slate-400 hover:text-white transition-all">
                    DViz
                </button>
                <button onclick="switchReportSubTab('home')" id="tab-btn-home" class="px-4 py-2 text-sm font-medium rounded-md text-slate-400 hover:text-white transition-all">
                    Home
                </button>
            </div>

            <!-- Token Reports Container -->
            <div id="report-subtab-tokens">
                <!-- Summary Section -->
                <div class="glass-panel p-6 rounded-xl mb-8">
                    <h3 class="font-bold text-white mb-4 border-b border-slate-700 pb-2">Summary Report</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-slate-900/40 p-3 rounded border border-slate-700/50">
                            <label class="text-xs text-slate-500 font-bold block mb-1">Total Users</label>
                            <div class="text-2xl text-white font-mono" id="rep-total-users">-</div>
                            <div class="text-xs text-slate-600 mt-1 flex justify-between">
                                <span>Active: <span class="text-green-400" id="rep-active-users">-</span></span>
                                <span>Blocked: <span class="text-red-400" id="rep-blocked-users">-</span></span>
                            </div>
                        </div>
                        <div class="bg-slate-900/40 p-3 rounded border border-slate-700/50">
                            <label class="text-xs text-slate-500 font-bold block mb-1">Teachers</label>
                            <div class="text-2xl text-white font-mono" id="rep-total-teachers">-</div>
                            <div class="text-xs text-slate-600 mt-1">Verified: <span class="text-yellow-400" id="rep-verified-teachers">-</span></div>
                        </div>
                        <div class="bg-slate-900/40 p-3 rounded border border-slate-700/50">
                            <label class="text-xs text-slate-500 font-bold block mb-1">Total Tokens</label>
                            <div class="text-xl text-blue-300 font-mono" id="rep-total-tokens">-</div>
                            <div class="text-[10px] text-slate-600 mt-1">Audio: <span id="rep-total-audio">-</span> | Text: <span id="rep-total-text">-</span></div>
                        </div>
                        <div class="bg-slate-900/40 p-3 rounded border border-slate-700/50">
                            <label class="text-xs text-slate-500 font-bold block mb-1">Total Cost</label>
                            <div class="text-2xl text-green-500 font-bold font-mono" id="rep-total-cost">-</div>
                            <div class="text-[10px] text-slate-600 mt-1">Audio: <span id="rep-cost-audio">-</span> | Text: <span id="rep-cost-text">-</span></div>
                        </div>
                    </div>
                </div>

                <!-- Analysis Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Geo Report -->
                    <div class="glass-panel p-6 rounded-xl border border-slate-700">
                        <h3 class="font-bold text-white mb-4">Cost by Country</h3>
                        <div class="max-h-[250px] overflow-y-auto custom-scrollbar">
                            <table class="w-full text-xs text-slate-400">
                                <thead class="bg-slate-800 text-slate-200 uppercase sticky top-0">
                                    <tr>
                                        <th class="p-2">Country</th>
                                        <th class="p-2">Tokens</th>
                                        <th class="p-2">Cost</th>
                                    </tr>
                                </thead>
                                <tbody id="rep-geo-body" class="divide-y divide-slate-800">
                                    <tr>
                                        <td colspan="3" class="p-4 text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- IP Report -->
                    <div class="glass-panel p-6 rounded-xl border border-slate-700">
                        <h3 class="font-bold text-white mb-4">Cost by IP</h3>
                        <div class="max-h-[250px] overflow-y-auto custom-scrollbar">
                            <table class="w-full text-xs text-slate-400">
                                <thead class="bg-slate-800 text-slate-200 uppercase sticky top-0">
                                    <tr>
                                        <th class="p-2">IP</th>
                                        <th class="p-2">Reqs</th>
                                        <th class="p-2">Toks</th>
                                        <th class="p-2">Cost</th>
                                    </tr>
                                </thead>
                                <tbody id="rep-ip-body" class="divide-y divide-slate-800">
                                    <tr>
                                        <td colspan="3" class="p-4 text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Detailed Section -->
                <div class="glass-panel p-6 rounded-xl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-white">Token Usage Detail</h3>
                        <div class="flex gap-2">
                            <input type="date" id="rep-date-start" class="bg-slate-900 border border-slate-700 rounded p-2 text-xs text-white" onchange="loadReportData(1)">
                            <input type="date" id="rep-date-end" class="bg-slate-900 border border-slate-700 rounded p-2 text-xs text-white" onchange="loadReportData(1)">
                            <input type="text" id="rep-search" placeholder="Search Email..." class="bg-slate-900 border border-slate-700 rounded p-2 text-xs text-white w-48" onkeyup="loadReportData(1)">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-400">
                            <thead class="bg-slate-800/50 text-slate-200 uppercase">
                                <tr>
                                    <th class="px-4 py-2">User</th>
                                    <th class="px-4 py-2">Audio Tok</th>
                                    <th class="px-4 py-2">Text Tok</th>
                                    <th class="px-4 py-2">Audio Cost</th>
                                    <th class="px-4 py-2">Text Cost</th>
                                    <th class="px-4 py-2 text-green-400">Total Cost</th>
                                    <th class="px-4 py-2">Total Tok</th>
                                </tr>
                            </thead>
                            <tbody id="rep-table-body" class="divide-y divide-slate-800">
                                <tr>
                                    <td colspan="7" class="p-4 text-center italic">Loading...</td>
                                </tr>
                            </tbody>
                            <tfoot id="rep-table-foot" class="bg-slate-800/50 uppercase font-bold text-slate-200"></tfoot>
                        </table>
                    </div>
                    <div class="mt-4 flex justify-end gap-2 items-center">
                        <button onclick="changeReportPage(-1)" class="px-3 py-1 bg-slate-800 rounded hover:bg-slate-700 disabled:opacity-50 text-xs text-white" id="rep-btn-prev">Prev</button>
                        <span class="px-2 py-1 text-slate-500 text-xs" id="rep-page-info">Page 1</span>
                        <button onclick="changeReportPage(1)" class="px-3 py-1 bg-slate-800 rounded hover:bg-slate-700 text-xs text-white" id="rep-btn-next">Next</button>
                    </div>
                </div>
            </div>

            <!-- Phase 15 Overview Reports Container -->
            <div id="report-subtab-overview" class="hidden">

                <!-- Phase 15 Filters -->
                <div class="glass-panel p-4 rounded-xl mb-6 border border-slate-700 flex flex-wrap gap-4 items-center justify-between">
                    <h3 class="font-bold text-white"><i class="fa-solid fa-filter mr-2 text-slate-400"></i> Analytics Filters</h3>
                    <div class="flex gap-2">
                        <input type="date" id="p15-date-start" class="bg-slate-900 border border-slate-700 rounded p-2 text-xs text-white" onchange="loadReportData(1)">
                        <input type="date" id="p15-date-end" class="bg-slate-900 border border-slate-700 rounded p-2 text-xs text-white" onchange="loadReportData(1)">
                    </div>
                </div>

                <!-- 1. Live Users Section -->
                <div class="glass-panel p-6 rounded-xl mb-6 border border-slate-700 relative overflow-hidden">
                    <div class="absolute top-4 right-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-xs text-green-400 font-mono uppercase">Live Stream</span>
                    </div>
                    <h3 class="font-bold text-white mb-4 border-b border-slate-700 pb-2">Real-Time Activity</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center md:text-left">
                            <label class="text-xs text-slate-500 font-bold block mb-1">Total Active (5m)</label>
                            <div class="text-5xl text-white font-mono" id="rep-live-total">-</div>
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-500 font-bold block mb-1 uppercase">By Country</label>
                            <div id="rep-live-country" class="text-xs text-white space-y-1">-</div>
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-500 font-bold block mb-1 uppercase">By App & Country</label>
                            <div id="rep-live-app-country" class="text-xs text-white space-y-1">-</div>
                        </div>
                    </div>
                </div>

                <!-- 2. Apps Ranking (Snapshot) -->
                <h3 class="text-lg font-bold text-white mb-4">Most Popular Apps (Rankings)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <!-- Daily -->
                    <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700">
                        <h4 class="text-xs font-bold text-slate-400 uppercase mb-3">Last 24 Hours</h4>
                        <div id="rep-rank-daily" class="space-y-2 text-sm text-white">-</div>
                    </div>
                    <!-- Weekly -->
                    <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700">
                        <h4 class="text-xs font-bold text-slate-400 uppercase mb-3">Last 7 Days</h4>
                        <div id="rep-rank-weekly" class="space-y-2 text-sm text-white">-</div>
                    </div>
                    <!-- Monthly -->
                    <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700">
                        <h4 class="text-xs font-bold text-slate-400 uppercase mb-3">Last 30 Days</h4>
                        <div id="rep-rank-monthly" class="space-y-2 text-sm text-white">-</div>
                    </div>
                </div>

                <!-- 3. Daily Trends (Date-wise) -->
                <div class="glass-panel p-6 rounded-xl border border-slate-700 mb-8">
                    <h3 class="font-bold text-white mb-4">Daily App Trends (Date-wise Metrics)</h3>
                    <div class="max-h-[300px] overflow-auto custom-scrollbar">
                        <table class="w-full text-xs text-slate-400">
                            <thead class="bg-slate-800 text-slate-200 uppercase sticky top-0">
                                <tr>
                                    <th class="p-2 text-left">Date</th>
                                    <th class="p-2 text-left">App</th>
                                    <th class="p-2 text-right text-white">Total</th>
                                    <th class="p-2 text-right text-blue-300">Student</th>
                                    <th class="p-2 text-right text-yellow-300">Teacher</th>
                                    <th class="p-2 text-right text-pink-300">Home</th>
                                    <th class="p-2 text-right text-slate-300">Desktop</th>
                                    <th class="p-2 text-right text-slate-300">Mobile</th>
                                </tr>
                            </thead>
                            <tbody id="rep-trends-body" class="divide-y divide-slate-800">
                                <tr>
                                    <td colspan="7" class="p-4 text-center">Loading trends...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. Button Hits & Documents -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                    <!-- Button Tracking -->
                    <div class="glass-panel p-6 rounded-xl border border-slate-700">
                        <h3 class="font-bold text-white mb-4">Button Hit Tracking</h3>
                        <div class="max-h-[300px] overflow-auto custom-scrollbar">
                            <table class="w-full text-xs text-slate-400">
                                <thead class="bg-slate-800 text-slate-200 uppercase sticky top-0">
                                    <tr>
                                        <th class="p-2 text-left">App</th>
                                        <th class="p-2 text-left">Button Name</th>
                                        <th class="p-2 text-right">Hits</th>
                                    </tr>
                                </thead>
                                <tbody id="rep-buttons-body" class="divide-y divide-slate-800">
                                    <tr>
                                        <td colspan="3" class="p-4 text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Document Analytics -->
                    <div class="glass-panel p-6 rounded-xl border border-slate-700 flex flex-col gap-4">
                        <div class="flex justify-between items-center">
                            <h3 class="font-bold text-white">Document Popularity</h3>
                            <div class="text-xs bg-slate-800 px-2 py-1 rounded text-white">Total Opens: <span id="rep-doc-total" class="font-mono font-bold">-</span></div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-slate-900/50 p-2 rounded">
                                <h4 class="text-[10px] text-slate-500 uppercase font-bold mb-1">Top Levels</h4>
                                <div id="rep-doc-levels" class="space-y-1 text-xs text-slate-300">-</div>
                            </div>
                            <div class="bg-slate-900/50 p-2 rounded">
                                <h4 class="text-[10px] text-slate-500 uppercase font-bold mb-1">Top Chapters</h4>
                                <div id="rep-doc-chapters" class="space-y-1 text-xs text-slate-300">-</div>
                            </div>
                        </div>

                        <div class="flex-1 overflow-hidden flex flex-col">
                            <h4 class="text-[10px] text-slate-500 uppercase font-bold mb-2">Top 10 Documents</h4>
                            <div class="overflow-auto custom-scrollbar flex-1">
                                <table class="w-full text-xs text-slate-400">
                                    <thead class="bg-slate-800 text-slate-200 uppercase sticky top-0">
                                        <tr>
                                            <th class="p-2 text-left">Title</th>
                                            <th class="p-2 text-right">Opens</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rep-doc-top" class="divide-y divide-slate-800">
                                        <tr>
                                            <td colspan="2" class="p-2 text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Rewards -->
                <div class="glass-panel p-6 rounded-xl border border-slate-700 mb-8">
                    <h3 class="font-bold text-white mb-4 flex items-center gap-2"><i class="fa-solid fa-trophy text-yellow-400"></i> Gamification & Rewards</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-xs text-slate-500 font-bold block mb-1">Total Points Distributed</label>
                            <div class="text-3xl text-yellow-500 font-mono font-bold" id="rep-total-points">-</div>
                            <div class="text-xs text-slate-400 mt-2">Points awarded across all apps.</div>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 font-bold block mb-2">Top Earners</label>
                            <div class="overflow-y-auto max-h-[150px] custom-scrollbar">
                                <table class="w-full text-xs text-slate-400">
                                    <thead class="bg-slate-800 text-slate-200">
                                        <tr>
                                            <th class="p-2 text-left">User</th>
                                            <th class="p-2 text-right">Points</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rep-top-earners" class="divide-y divide-slate-800">
                                        <tr>
                                            <td colspan="2" class="p-2 italic text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Environment & Location Stats (Restored) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Country Visits -->
                    <div class="glass-panel p-6 rounded-xl border border-slate-700">
                        <h3 class="font-bold text-white mb-4">Visits by Country</h3>
                        <div class="max-h-[250px] overflow-y-auto custom-scrollbar">
                            <table class="w-full text-xs text-slate-400">
                                <thead class="bg-slate-800 text-slate-200 uppercase sticky top-0">
                                    <tr>
                                        <th class="p-2">App</th>
                                        <th class="p-2">Country</th>
                                        <th class="p-2 text-right">Visits</th>
                                    </tr>
                                </thead>
                                <tbody id="rep-country-body" class="divide-y divide-slate-800">
                                    <tr>
                                        <td colspan="3" class="p-4 text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- OS Stats -->
                    <div class="glass-panel p-6 rounded-xl border border-slate-700">
                        <h3 class="font-bold text-white mb-4">Usage by OS</h3>
                        <div class="max-h-[250px] overflow-y-auto custom-scrollbar">
                            <table class="w-full text-xs text-slate-400">
                                <thead class="bg-slate-800 text-slate-200 uppercase sticky top-0">
                                    <tr>
                                        <th class="p-2">App</th>
                                        <th class="p-2">OS</th>
                                        <th class="p-2 text-right">Visits</th>
                                    </tr>
                                </thead>
                                <tbody id="rep-os-body" class="divide-y divide-slate-800">
                                    <tr>
                                        <td colspan="3" class="p-4 text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- App-Specific Tabs (Dynamically Loaded) -->
            <div id="report-subtab-pyviz" class="hidden">
                <div class="text-center py-8 text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading PyViz metrics...</div>
            </div>
            <div id="report-subtab-stats" class="hidden">
                <div class="text-center py-8 text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading Stats metrics...</div>
            </div>
            <div id="report-subtab-linear" class="hidden">
                <div class="text-center py-8 text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading Linear metrics...</div>
            </div>
            <div id="report-subtab-graph" class="hidden">
                <div class="text-center py-8 text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading Graph metrics...</div>
            </div>
            <div id="report-subtab-dviz" class="hidden">
                <div class="text-center py-8 text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading DViz metrics...</div>
            </div>
            <div id="report-subtab-home" class="hidden">
                <div class="text-center py-8 text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading Home metrics...</div>
            </div>

            <script>
                const APP_TABS = ['tokens', 'overview', 'pyviz', 'stats', 'linear', 'graph', 'dviz', 'home'];

                window.activeReportTab = 'tokens'; // Default

                function switchReportSubTab(tab) {
                    window.activeReportTab = tab;

                    // Hide all containers
                    APP_TABS.forEach(t => {
                        const container = document.getElementById('report-subtab-' + t);
                        if (container) container.classList.add('hidden');
                        const btn = document.getElementById('tab-btn-' + t);
                        if (btn) {
                            btn.classList.remove('bg-brand-600', 'text-white');
                            btn.classList.add('text-slate-400');
                        }
                    });

                    // Show active container
                    const activeContainer = document.getElementById('report-subtab-' + tab);
                    if (activeContainer) activeContainer.classList.remove('hidden');

                    // Highlight active button
                    const btn = document.getElementById('tab-btn-' + tab);
                    if (btn) {
                        btn.classList.add('bg-brand-600', 'text-white');
                        btn.classList.remove('text-slate-400');
                    }

                    // Load app-specific data if it's an app tab, or refresh main report if overview
                    if (['pyviz', 'stats', 'linear', 'graph', 'dviz', 'home'].includes(tab)) {
                        loadAppMetrics(tab);
                    } else if (tab === 'overview') {
                        loadReportData(1);
                    }
                }

                function refreshActiveReportTab() {
                    const tab = window.activeReportTab || 'tokens';
                    if (['pyviz', 'stats', 'linear', 'graph', 'dviz', 'home'].includes(tab)) {
                        loadAppMetrics(tab, true); // true to preserve dates if needed, though loadAppMetrics handles it
                    } else {
                        loadReportData(1);
                    }
                }

                async function loadAppMetrics(appName, preserveDates = false) {
                    const containerId = 'report-subtab-' + appName;
                    const container = document.getElementById(containerId);
                    if (!container) return;

                    // Preserve current date values before re-rendering
                    const existingStartDate = document.getElementById('app-date-start-' + appName)?.value || '';
                    const existingEndDate = document.getElementById('app-date-end-' + appName)?.value || '';

                    container.innerHTML = '<div class="text-center py-8 text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading ' + appName.charAt(0).toUpperCase() + appName.slice(1) + ' metrics...</div>';

                    const fd = new FormData();
                    fd.append('action', 'get_app_metrics');
                    fd.append('app_name', appName.charAt(0).toUpperCase() + appName.slice(1));
                    fd.append('date_start', existingStartDate);
                    fd.append('date_end', existingEndDate);

                    try {
                        const res = await fetch('user_actions.php', {
                            method: 'POST',
                            body: fd
                        });
                        const d = await res.json();

                        if (d.status === 'success') {
                            renderAppMetrics(container, appName, d, existingStartDate, existingEndDate);
                        } else {
                            container.innerHTML = '<div class="text-center py-8 text-red-400">' + (d.message || 'Failed to load metrics') + '</div>';
                        }
                    } catch (e) {
                        console.error(e);
                        container.innerHTML = '<div class="text-center py-8 text-red-400">Error loading metrics: ' + e.message + '</div>';
                    }
                }

                function renderAppMetrics(container, appName, data, preservedStartDate = '', preservedEndDate = '') {
                    const m = data.metrics;
                    const displayName = appName.charAt(0).toUpperCase() + appName.slice(1);

                    let html = `
                            <!-- Date Filter for this App -->
                            <div class="glass-panel p-4 rounded-xl mb-6 border border-slate-700 flex flex-wrap gap-4 items-center justify-between">
                                <h3 class="font-bold text-white"><i class="fa-solid fa-filter mr-2 text-slate-400"></i> ${displayName} Date Filter</h3>
                                <div class="flex gap-2">
                                     <input type="date" id="app-date-start-${appName}" value="${preservedStartDate}" class="bg-slate-900 border border-slate-700 rounded p-2 text-xs text-white" onchange="loadAppMetrics('${appName}')">
                                     <input type="date" id="app-date-end-${appName}" value="${preservedEndDate}" class="bg-slate-900 border border-slate-700 rounded p-2 text-xs text-white" onchange="loadAppMetrics('${appName}')">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                <div class="glass-panel p-4 rounded-xl border border-slate-700 text-center">
                                    <div class="text-3xl font-mono font-bold text-white">${m.total_visits || 0}</div>
                                    <div class="text-xs text-slate-500 mt-1">Total Visits</div>
                                </div>
                                <div class="glass-panel p-4 rounded-xl border border-slate-700 text-center">
                                    <div class="text-3xl font-mono font-bold text-blue-400">${m.student_visits || 0}</div>
                                    <div class="text-xs text-slate-500 mt-1">Student Visits</div>
                                </div>
                                <div class="glass-panel p-4 rounded-xl border border-slate-700 text-center">
                                    <div class="text-3xl font-mono font-bold text-purple-400">${m.teacher_visits || 0}</div>
                                    <div class="text-xs text-slate-500 mt-1">Teacher Visits</div>
                                </div>
                                <div class="glass-panel p-4 rounded-xl border border-slate-700 text-center">
                                    <div class="text-3xl font-mono font-bold text-green-400">${m.button_clicks || 0}</div>
                                    <div class="text-xs text-slate-500 mt-1">Button Clicks</div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Device Breakdown -->
                                <div class="glass-panel p-6 rounded-xl border border-slate-700">
                                    <h4 class="font-bold text-white mb-4">Device Breakdown</h4>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-slate-400"><i class="fa-solid fa-desktop mr-2"></i>Desktop</span>
                                            <span class="font-mono text-white">${m.desktop_visits || 0}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-slate-400"><i class="fa-solid fa-mobile mr-2"></i>Mobile</span>
                                            <span class="font-mono text-white">${m.mobile_visits || 0}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Country Breakdown -->
                                <div class="glass-panel p-6 rounded-xl border border-slate-700">
                                    <h4 class="font-bold text-white mb-4">Top Countries</h4>
                                    <div class="space-y-2 max-h-40 overflow-y-auto custom-scrollbar">
                                        ${(m.countries || []).length ? m.countries.map(c => `
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="flex items-center gap-2"><img src="https://flagcdn.com/20x15/${(c.country_code || 'xx').toLowerCase()}.png" class="h-3 rounded-sm"> ${c.country_code || 'Unknown'}</span>
                                                <span class="font-mono text-white">${c.count}</span>
                                            </div>
                                        `).join('') : '<span class="italic text-slate-500">No country data</span>'}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Top 10 Users -->
                            <div class="glass-panel p-6 rounded-xl border border-slate-700 mb-6">
                                <h4 class="font-bold text-white mb-4"><i class="fa-solid fa-users mr-2 text-brand-400"></i>Top 10 Users by Activity</h4>
                                <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                    <table class="w-full text-sm text-slate-400">
                                        <thead class="bg-slate-800 text-slate-200 uppercase sticky top-0"><tr><th class="p-2 text-left">User</th><th class="p-2 text-right">Visits</th></tr></thead>
                                        <tbody class="divide-y divide-slate-800">
                                            ${(m.top_users || []).length ? m.top_users.map(u => `<tr><td class="p-2 text-white">${u.email || u.full_name || 'Guest'}</td><td class="p-2 text-right font-mono text-brand-300">${u.count}</td></tr>`).join('') : '<tr><td colspan="2" class="p-4 text-center italic">No user data</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;

                    // Add Button Hits section ONLY if NOT Home tab
                    if (appName !== 'home') {
                        html += `
                                <div class="glass-panel p-6 rounded-xl border border-slate-700 mb-6">
                                    <h4 class="font-bold text-white mb-4">Button Click Analysis</h4>
                                    <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                        <table class="w-full text-sm text-slate-400">
                                            <thead class="bg-slate-800 text-slate-200 uppercase sticky top-0"><tr><th class="p-2 text-left">Button</th><th class="p-2 text-right">Clicks</th></tr></thead>
                                            <tbody class="divide-y divide-slate-800">
                                                ${(m.buttons || []).length ? m.buttons.map(b => `<tr><td class="p-2">${b.event_name || 'Unknown'}</td><td class="p-2 text-right font-mono text-white">${b.count}</td></tr>`).join('') : '<tr><td colspan="2" class="p-4 text-center italic">No button data</td></tr>'}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;
                    }

                    // Add document section for DViz
                    if (appName === 'dviz' && m.documents) {
                        html += `
                                <div class="glass-panel p-6 rounded-xl border border-slate-700">
                                    <h4 class="font-bold text-white mb-4">Document Analytics</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div class="bg-slate-900/50 p-4 rounded-lg text-center">
                                            <div class="text-3xl font-mono font-bold text-brand-400">${m.documents.total_opens || 0}</div>
                                            <div class="text-xs text-slate-500 mt-1">Total Document Opens</div>
                                        </div>
                                        <div class="bg-slate-900/50 p-4 rounded-lg">
                                            <h5 class="text-xs font-bold text-slate-400 mb-2">Top Documents</h5>
                                            <div class="space-y-1 max-h-32 overflow-y-auto custom-scrollbar">
                                                ${(m.documents.top || []).length ? m.documents.top.map(doc => `
                                                    <div class="flex justify-between text-xs">
                                                        <span class="text-slate-300 truncate max-w-[80%]">${doc.title}</span>
                                                        <span class="font-mono text-white">${doc.views}</span>
                                                    </div>
                                                `).join('') : '<span class="italic text-slate-500 text-xs">No document data yet</span>'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                    }

                    container.innerHTML = html;
                }
            </script>


        </div>

        <?php
        $adsMain = __DIR__ . '/../ads/main_view.php';
        if (file_exists($adsMain)) require_once $adsMain;
        ?>
        <?php
        $adsRep = __DIR__ . '/../ads/reports_view.php';
        if (file_exists($adsRep)) require_once $adsRep;
        ?>
        <?php
        $appsView = __DIR__ . '/apps_view.php';
        if (file_exists($appsView)) require_once $appsView;
        ?>
        <?php
        $changelogView = __DIR__ . '/changelog_view.php';
        if (file_exists($changelogView)) require_once $changelogView;
        ?>
    </main>

    <script>
        // Check URL for tab
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            let tab = urlParams.get('tab');
            if (!tab) tab = 'overview'; // Default to overview if no tab
            switchTab(tab);
        });

        function switchTab(tabId) {
            // Update URL so reload works
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);

            // Hide all tab content sections
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(el => el.classList.add('hidden'));

            // Show the target tab
            const target = document.getElementById('tab-' + tabId);
            if (target) {
                target.classList.remove('hidden');

                // Triggers for dynamic data loading
                if (tabId === 'reports') loadReportData(1);
                if (tabId === 'overview') loadOverviewData();
                if (tabId === 'ads' && typeof loadAdsList === 'function') loadAdsList();
                if (tabId === 'changelog' && typeof loadChangeLogsList === 'function') loadChangeLogsList();

                // Ads Sub-tabs handled via internal navigation but if direct link:
                if (tabId === 'ads-reports' && typeof loadAdsReport === 'function') {
                    // Ensure parent/sibling visibility if needed, or just load
                    loadAdsReport();
                }
            } else {
                console.error('Tab target not found:', tabId);
            }

            // Highlight sidebar navigation
            // Find button based on onclick attribute because 'event' might be null on initial load
            const buttons = document.querySelectorAll('aside nav button');
            buttons.forEach(btn => {
                const clickAttr = btn.getAttribute('onclick');
                if (clickAttr && clickAttr.includes(`'${tabId}'`)) {
                    btn.classList.add('bg-brand-600', 'text-white');
                    btn.classList.remove('text-slate-400', 'hover:text-white', 'hover:bg-slate-800');
                } else {
                    btn.classList.remove('bg-brand-600', 'text-white');
                    btn.classList.add('text-slate-400', 'hover:text-white', 'hover:bg-slate-800');
                }
            });

            // Close sidebar on mobile
            if (window.innerWidth < 768) {
                const sb = document.getElementById('admin-sidebar');
                if (sb && !sb.classList.contains('-translate-x-full')) {
                    toggleSidebar();
                }
            }
        }


        async function loadOverviewData() {
            // Re-use the existing report endpoint which returns everything we need
            // We can optimize backend later if needed
            const fd = new FormData();
            fd.append('action', 'get_report_data');
            // No filters for overview usually, or just defaults

            try {
                const res = await fetch('user_actions.php', {
                    method: 'POST',
                    body: fd
                });
                const d = await res.json();
                if (d.status === 'success') {
                    // 1. Stats Cards
                    if (document.getElementById('stat-total-users')) document.getElementById('stat-total-users').innerText = d.stats.total_users;
                    if (document.getElementById('stat-active-users')) document.getElementById('stat-active-users').innerText = d.stats.active_users;
                    if (document.getElementById('stat-total-teachers')) document.getElementById('stat-total-teachers').innerText = d.stats.teachers;
                    if (document.getElementById('stat-verified-teachers')) document.getElementById('stat-verified-teachers').innerText = d.stats.verified_teachers;

                    // 2. Live Users
                    if (document.getElementById('live-users-count')) document.getElementById('live-users-count').innerText = d.stats.live_users ? (d.stats.live_users.total || 0) : 0;

                    // 3. Top Countries (Logic from geo)
                    const geo = d.stats.geo || [];
                    if (document.getElementById('top-countries-list')) {
                        const topC = geo.slice(0, 5).map(r => `
                            <div class="flex justify-between items-center text-sm border-b border-slate-700/50 pb-2 last:border-0">
                                <span class="flex items-center gap-2 text-white">
                                    ${(r.cc!=='Unknown'&&r.cc)?`<img src="https://flagcdn.com/20x15/${r.cc.toLowerCase()}.png" class="h-3 rounded-sm">`:''} ${r.cc||'Unknown'}
                                </span>
                                <span class="font-mono text-brand-300 font-bold">$${parseFloat(r.cost).toFixed(2)}</span>
                            </div>
                        `).join('');
                        document.getElementById('top-countries-list').innerHTML = topC || '<div class="text-center text-slate-500 italic text-xs p-2">No country data</div>';
                    }

                    // 4. Top IPs
                    const ips = d.stats.ips || [];
                    if (document.getElementById('top-activity-ips-list')) {
                        const topI = ips.slice(0, 5).map(r => `
                            <div class="flex justify-between items-center text-sm border-b border-slate-700/50 pb-2 last:border-0">
                                <span class="font-mono text-slate-300 text-xs">${r.user_ip}</span>
                                <div class="text-right">
                                    <div class="text-white font-bold">${r.reqs} reqs</div>
                                    <div class="text-[10px] text-green-400">$${parseFloat(r.cost).toFixed(4)}</div>
                                </div>
                            </div>
                        `).join('');
                        document.getElementById('top-activity-ips-list').innerHTML = topI || '<div class="text-center text-slate-500 italic text-xs p-2">No IP data</div>';
                    }

                    // 5. Rankings (If present in Overview)
                    // Check for IDs like 'ov-rank-list' or match what was restored
                    // Assuming generic 'rankings-list' if restored
                    if (document.getElementById('rankings-list')) {
                        const daily = d.stats.rankings ? d.stats.rankings.daily : [];
                        document.getElementById('rankings-list').innerHTML = daily.length ? daily.map((r, i) => `
                            <div class="flex justify-between items-center text-sm border-b border-slate-700/50 pb-2 last:border-0">
                                <span class="text-slate-300"><span class="text-slate-500 font-mono mr-2">${i+1}.</span>${r.app_name}</span>
                                <span class="font-bold text-white">${r.c}</span>
                            </div>
                        `).join('') : '<div class="text-center text-slate-500 italic">No data</div>';
                    }

                    // 6. Real Time Activity (Detailed List if exists)
                    if (document.getElementById('real-time-list')) {
                        // User actions log... we need 'get_logs'? No, 'get_report_data' doesn't return activity log feed.
                        // We might need a separate call/action for 'recent_activity_feed' if Overview expects it.
                        // For now, leave empty or placeholder.
                    }

                }
            } catch (e) {
                console.error("Overview Load Error:", e);
            }
        }

        let curReportPage = 1;
        async function loadReportData(page = 1) {
            curReportPage = page;
            const search = document.getElementById('rep-search')?.value || '';
            const dStart = document.getElementById('p15-date-start')?.value || document.getElementById('rep-date-start')?.value || '';
            const dEnd = document.getElementById('p15-date-end')?.value || document.getElementById('rep-date-end')?.value || '';

            document.getElementById('rep-table-body').innerHTML = '<tr><td colspan="7" class="p-4 text-center italic">Loading...</td></tr>';

            const fd = new FormData();
            fd.append('action', 'get_report_data');
            fd.append('page', page);
            fd.append('search', search);
            fd.append('date_start', dStart);
            fd.append('date_end', dEnd);

            try {
                const res = await fetch('user_actions.php', {
                    method: 'POST',
                    body: fd
                });
                const d = await res.json();
                if (d.status === 'success') {
                    // Helper to safely run updates
                    const safeUpdate = (fn) => {
                        try {
                            fn();
                        } catch (e) {
                            console.error('Report Update Error:', e);
                        }
                    };

                    // 1. Basic Stats
                    safeUpdate(() => {
                        const s = d.stats || {};
                        if (document.getElementById('rep-total-users')) document.getElementById('rep-total-users').innerText = s.total_users;
                        if (document.getElementById('rep-active-users')) document.getElementById('rep-active-users').innerText = s.active_users;
                        if (document.getElementById('rep-blocked-users')) document.getElementById('rep-blocked-users').innerText = s.blocked_users;
                        if (document.getElementById('rep-total-teachers')) document.getElementById('rep-total-teachers').innerText = s.teachers;
                        if (document.getElementById('rep-verified-teachers')) document.getElementById('rep-verified-teachers').innerText = s.verified_teachers;

                        const t = s.tokens || {};
                        if (document.getElementById('rep-total-tokens')) document.getElementById('rep-total-tokens').innerText = parseInt(t.aud || 0) + parseInt(t.txt || 0);
                        if (document.getElementById('rep-total-audio')) document.getElementById('rep-total-audio').innerText = t.aud || 0;
                        if (document.getElementById('rep-total-text')) document.getElementById('rep-total-text').innerText = t.txt || 0;

                        if (document.getElementById('rep-total-cost')) document.getElementById('rep-total-cost').innerText = '$' + parseFloat(t.cost_total || 0).toFixed(4);
                        if (document.getElementById('rep-cost-audio')) document.getElementById('rep-cost-audio').innerText = '$' + parseFloat(t.cost_aud || 0).toFixed(4);
                        if (document.getElementById('rep-cost-text')) document.getElementById('rep-cost-text').innerText = '$' + parseFloat(t.cost_txt || 0).toFixed(4);
                    });

                    // 2. Geo Table
                    safeUpdate(() => {
                        const geoRows = d.stats.geo && d.stats.geo.length ? d.stats.geo.map(r => `<tr><td class="p-2 flex items-center gap-2">${(r.cc!=='Unknown'&&r.cc)?`<img src="https://flagcdn.com/20x15/${r.cc.toLowerCase()}.png" class="h-3 rounded-sm">`:''} ${r.cc||'Unknown'}</td><td class="p-2 font-mono text-white">${r.toks}</td><td class="p-2 font-mono text-green-400">$${parseFloat(r.cost).toFixed(4)}</td></tr>`).join('') : '<tr><td colspan="3" class="p-4 text-center italic">No country data.</td></tr>';
                        if (document.getElementById('rep-geo-body')) document.getElementById('rep-geo-body').innerHTML = geoRows;
                    });

                    // 3. IP Table
                    safeUpdate(() => {
                        const ipRows = d.stats.ips && d.stats.ips.length ? d.stats.ips.map(r => `<tr><td class="p-2 font-mono text-white">${r.user_ip}</td><td class="p-2">${r.reqs}</td><td class="p-2 font-mono text-white">${r.toks}</td><td class="p-2 font-mono text-green-400">$${parseFloat(r.cost).toFixed(4)}</td></tr>`).join('') : '<tr><td colspan="4" class="p-4 text-center italic">No IP data.</td></tr>';
                        if (document.getElementById('rep-ip-body')) document.getElementById('rep-ip-body').innerHTML = ipRows;
                    });

                    // --- Phase 15 Update Logic ---

                    // 4. Live Users
                    safeUpdate(() => {
                        const live = d.stats.live_users || {};
                        if (document.getElementById('rep-live-total')) document.getElementById('rep-live-total').innerText = live.total || 0;
                        if (document.getElementById('rep-live-country')) document.getElementById('rep-live-country').innerHTML = live.by_country && live.by_country.length ? live.by_country.map(r => `<div class="flex justify-between items-center"><span class="flex items-center gap-1"><img src="https://flagcdn.com/20x15/${(r.country_code||'unknown').toLowerCase()}.png" class="h-3 rounded-sm"> ${r.country_code||'Unknown'}</span><span class="font-mono text-green-400 font-bold">${r.c}</span></div>`).join('') : '<span class="italic text-slate-500">No active users.</span>';
                        if (document.getElementById('rep-live-app-country')) document.getElementById('rep-live-app-country').innerHTML = live.by_app_country && live.by_app_country.length ? live.by_app_country.map(r => `<div class="flex justify-between items-center"><span>${r.country_code||'-'}: <span class="text-slate-300">${r.app_name}</span></span><span class="font-mono text-green-400">${r.c}</span></div>`).join('') : '<span class="italic text-slate-500">No data.</span>';
                    });

                    // 5. Rankings
                    safeUpdate(() => {
                        const rn = d.stats.rankings || {
                            daily: [],
                            weekly: [],
                            monthly: []
                        };
                        const renderRank = (list) => list && list.length ? list.map((r, i) => `<div class="flex justify-between border-b border-slate-700/50 pb-1 last:border-0"><span class="flex gap-2"><span class="text-slate-500 font-mono w-4">${i+1}.</span> ${r.app_name}</span><span class="font-bold text-brand-400">${r.c}</span></div>`).join('') : '<span class="italic text-slate-500">No data.</span>';
                        if (document.getElementById('rep-rank-daily')) document.getElementById('rep-rank-daily').innerHTML = renderRank(rn.daily);
                        if (document.getElementById('rep-rank-weekly')) document.getElementById('rep-rank-weekly').innerHTML = renderRank(rn.weekly);
                        if (document.getElementById('rep-rank-monthly')) document.getElementById('rep-rank-monthly').innerHTML = renderRank(rn.monthly);
                    });

                    // 6. Daily Trends
                    safeUpdate(() => {
                        const trends = d.stats.daily_trends;
                        const trendRows = trends && trends.length ? trends.map(r => `
                            <tr>
                                <td class="p-2 text-white font-mono">${r.log_date}</td>
                                <td class="p-2 text-slate-300">${r.app_name}</td>
                                <td class="p-2 text-right font-bold text-white">${r.total_visits}</td>
                                <td class="p-2 text-right font-mono text-blue-300">${r.student_visits||0}</td>
                                <td class="p-2 text-right font-mono text-yellow-300">${r.teacher_visits||0}</td>
                                <td class="p-2 text-right font-mono text-pink-300">${r.home_visits||0}</td>
                                <td class="p-2 text-right font-mono text-slate-300">${r.desktop_visits||0}</td>
                                <td class="p-2 text-right font-mono text-slate-400">${r.mobile_visits||0}</td>
                            </tr>
                        `).join('') : '<tr><td colspan="8" class="p-4 text-center italic">No trend data for selected range.</td></tr>';
                        if (document.getElementById('rep-trends-body')) document.getElementById('rep-trends-body').innerHTML = trendRows;
                    });

                    // 7. Button Hits
                    safeUpdate(() => {
                        const btns = d.stats.button_hits;
                        const btnRows = btns && btns.length ? btns.map(r => `<tr><td class="p-2 text-slate-400">${r.app_name}</td><td class="p-2 text-white">${r.button_name}</td><td class="p-2 text-right font-mono text-brand-300">${r.hits}</td></tr>`).join('') : '<tr><td colspan="3" class="p-4 text-center italic">No button hits recorded.</td></tr>';
                        if (document.getElementById('rep-buttons-body')) document.getElementById('rep-buttons-body').innerHTML = btnRows;
                    });

                    // 8. Document Analytics
                    safeUpdate(() => {
                        const docs = d.stats.doc_analytics || {};
                        if (document.getElementById('rep-doc-total')) document.getElementById('rep-doc-total').innerText = docs.total_opens || 0;
                        if (document.getElementById('rep-doc-levels')) document.getElementById('rep-doc-levels').innerHTML = docs.levels && docs.levels.length ? docs.levels.slice(0, 5).map(r => `<div class="flex justify-between"><span>${r.level_name}</span><span class="font-mono">${r.c}</span></div>`).join('') : '<span class="italic">No level data</span>';
                        if (document.getElementById('rep-doc-chapters')) document.getElementById('rep-doc-chapters').innerHTML = docs.chapters && docs.chapters.length ? docs.chapters.slice(0, 5).map(r => `<div class="flex justify-between"><span>${r.chapter_name}</span><span class="font-mono">${r.c}</span></div>`).join('') : '<span class="italic">No chapter data</span>';

                        const topDocs = docs.top_docs && docs.top_docs.length ? docs.top_docs.map(r => `<tr><td class="p-2 text-white truncate max-w-[150px]" title="${r.title}">${r.title}</td><td class="p-2 text-right font-mono text-white">${r.views}</td></tr>`).join('') : '<tr><td colspan="2" class="p-4 text-center italic">No document data.</td></tr>';
                        if (document.getElementById('rep-doc-top')) document.getElementById('rep-doc-top').innerHTML = topDocs;
                    });

                    // 9. Rewards
                    safeUpdate(() => {
                        const rewards = d.stats.rewards || {};
                        if (document.getElementById('rep-total-points')) document.getElementById('rep-total-points').innerText = parseInt(rewards.total || 0).toLocaleString();
                        const topRows = rewards.top_earners && rewards.top_earners.length ? rewards.top_earners.map(r => `<tr><td class="p-2 text-white">${r.email}</td><td class="p-2 text-right font-mono text-yellow-400 font-bold">${parseInt(r.points).toLocaleString()}</td></tr>`).join('') : '<tr><td colspan="2" class="p-2 italic text-center">No rewards data.</td></tr>';
                        if (document.getElementById('rep-top-earners')) document.getElementById('rep-top-earners').innerHTML = topRows;
                    });

                    // 10. Environment Stats
                    safeUpdate(() => {
                        const countryRows = d.stats.country_visits && d.stats.country_visits.length ? d.stats.country_visits.map(r => `<tr><td class="p-2 text-slate-300">${r.app_name}</td><td class="p-2 flex items-center gap-2"><img src="https://flagcdn.com/20x15/${(r.country_code||'unknown').toLowerCase()}.png" class="h-3 rounded-sm"> ${r.country_code||'Unknown'}</td><td class="p-2 text-right font-mono text-white">${r.c}</td></tr>`).join('') : '<tr><td colspan="3" class="p-4 text-center italic">No country data.</td></tr>';
                        if (document.getElementById('rep-country-body')) document.getElementById('rep-country-body').innerHTML = countryRows;

                        const osRows = d.stats.os_stats && d.stats.os_stats.length ? d.stats.os_stats.map(r => `<tr><td class="p-2 text-slate-300">${r.app_name}</td><td class="p-2 text-white">${r.os}</td><td class="p-2 text-right font-mono text-brand-300">${r.c}</td></tr>`).join('') : '<tr><td colspan="3" class="p-4 text-center italic">No OS data.</td></tr>';
                        if (document.getElementById('rep-os-body')) document.getElementById('rep-os-body').innerHTML = osRows;
                    });

                    // 11. Main User Table
                    safeUpdate(() => {
                        let h = '';
                        if (!d.table || d.table.length === 0) {
                            h = '<tr><td colspan="7" class="p-4 text-center text-slate-500">No data found</td></tr>';
                        } else {
                            d.table.forEach(r => {
                                h += `<tr class="hover:bg-slate-800/50 border-b border-slate-800/50">
                                    <td class="px-4 py-2">
                                        <div class="font-bold text-white">${r.full_name}</div>
                                        <div class="text-[10px] text-slate-500">${r.email}</div>
                                    </td>
                                    <td class="px-4 py-2 text-blue-300 font-mono">${parseInt(r.aud_tok).toLocaleString()}</td>
                                    <td class="px-4 py-2 text-purple-300 font-mono">${parseInt(r.txt_tok).toLocaleString()}</td>
                                    <td class="px-4 py-2 text-slate-400 font-mono">$${parseFloat(r.aud_cost).toFixed(4)}</td>
                                    <td class="px-4 py-2 text-slate-400 font-mono">$${parseFloat(r.txt_cost).toFixed(4)}</td>
                                    <td class="px-4 py-2 text-green-400 font-bold font-mono">$${parseFloat(r.total_cost).toFixed(4)}</td>
                                    <td class="px-4 py-2 text-slate-300 font-mono">${parseInt(r.total_tok_sum).toLocaleString()}</td>
                                </tr>`;
                            });
                        }
                        if (document.getElementById('rep-table-body')) document.getElementById('rep-table-body').innerHTML = h;

                        // Footer Totals
                        if (d.totals && document.getElementById('rep-table-foot')) {
                            const t = d.totals;
                            document.getElementById('rep-table-foot').innerHTML = `
                                <tr>
                                    <td class="px-4 py-3">TOTALS</td>
                                    <td class="px-4 py-3 text-blue-300 font-bold font-mono">${parseInt(t.aud_tok||0).toLocaleString()}</td>
                                    <td class="px-4 py-3 text-purple-300 font-bold font-mono">${parseInt(t.txt_tok||0).toLocaleString()}</td>
                                    <td class="px-4 py-3">-</td>
                                    <td class="px-4 py-3">-</td>
                                    <td class="px-4 py-3 text-green-400 font-bold font-mono">$${parseFloat(t.total_cost||0).toFixed(4)}</td>
                                    <td class="px-4 py-3">-</td>
                                </tr>
                            `;
                        }
                        if (document.getElementById('rep-page-info')) document.getElementById('rep-page-info').innerText = 'Page ' + page;
                        if (document.getElementById('rep-btn-prev')) document.getElementById('rep-btn-prev').disabled = page <= 1;
                        if (document.getElementById('rep-btn-next')) document.getElementById('rep-btn-next').disabled = (d.table && d.table.length < 20);
                    });

                } else {
                    alert(d.message);
                }
            } catch (e) {
                console.error(e);
                alert('Reports Load Failed: ' + e.message);
            }
        }

        let reportDebounce;

        function loadReportTable(resetInfo = 0) {
            clearTimeout(reportDebounce);
            reportDebounce = setTimeout(() => loadReportData(1), 500);
        }

        function changeReportPage(delta) {
            const newPage = curReportPage + delta;
            if (newPage < 1) return;
            loadReportData(newPage);
        }

        async function performUserAction(action, email) {
            if (!confirm(`Are you sure you want to ${action.replace('_', ' ')} for ${email}?`)) return;

            const formData = new FormData();
            formData.append('action', action);
            formData.append('email', email);

            try {
                const res = await fetch('user_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await res.text();
                try {
                    const data = JSON.parse(text);
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown'));
                    }
                } catch (err) {
                    console.error("Invalid JSON:", text);
                    alert("Server Error: Response was not JSON. Check Console.");
                }
            } catch (e) {
                alert('Request failed');
                console.error(e);
            }
        }

        async function confirmHardDelete(email, fullName) {
            // First confirmation
            if (!confirm(`⚠️ DANGER: You are about to PERMANENTLY DELETE this user!\n\nUser: ${fullName}\nEmail: ${email}\n\nThis action:\n• Removes the user from the system\n• Deletes all related data (sessions, credits, activity logs)\n• Cannot be undone!\n\nA backup will be saved to the users_dump table.\n\nClick OK to proceed with deletion.`)) return;

            // Second confirmation with type check
            const confirmText = prompt(`⚠️ FINAL WARNING ⚠️\n\nTo confirm permanent deletion of "${email}", please type DELETE in the box below:`);

            if (confirmText !== 'DELETE') {
                if (confirmText !== null) {
                    alert('Deletion cancelled. You must type DELETE exactly (case-sensitive) to confirm.');
                }
                return;
            }

            const formData = new FormData();
            formData.append('action', 'hard_delete');
            formData.append('email', email);

            try {
                const res = await fetch('user_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await res.text();
                try {
                    const data = JSON.parse(text);
                    if (data.status === 'success') {
                        alert(`✅ ${data.message}`);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                    }
                } catch (err) {
                    console.error("Invalid JSON:", text);
                    alert("Server Error: Response was not valid JSON. Check Console.");
                }
            } catch (e) {
                alert('Request failed: ' + e.message);
                console.error(e);
            }
        }

        async function sendVerificationEmail(email) {
            if (!confirm(`Send verification email to ${email}?\n\nThis will generate a new 6-digit code and send it to the user's email address.`)) return;

            const formData = new FormData();
            formData.append('action', 'send_verification_email');
            formData.append('email', email);

            try {
                const res = await fetch('user_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await res.text();
                try {
                    const data = JSON.parse(text);
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to send email'));
                    }
                } catch (err) {
                    console.error("Invalid JSON:", text);
                    alert("Server Error: Response was not JSON. Check Console.");
                }
            } catch (e) {
                alert('Request failed');
                console.error(e);
            }
        }

        async function performSecurityAction(action, ip) {
            if (!confirm(`Unblock ${ip}?`)) return;
            const fd = new FormData();
            fd.append('action', action);
            fd.append('ip', ip);
            try {
                const res = await fetch('security_actions.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function blockIP(e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'block');
            try {
                const res = await fetch('security_actions.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function viewUserLogs(uid, name) {
            document.getElementById('log-username').innerText = name;
            document.getElementById('modal-logs').classList.remove('hidden');

            // Clear
            document.getElementById('log-activity-body').innerHTML = '<tr><td colspan="4" class="p-4 text-center">Loading...</td></tr>';
            document.getElementById('log-usage-body').innerHTML = '<tr><td colspan="3" class="p-4 text-center">Loading...</td></tr>';

            const fd = new FormData();
            fd.append('action', 'get_logs');
            fd.append('user_id', uid);

            try {
                const res = await fetch('user_actions.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.status === 'success') {
                    // Activity
                    const actBody = data.logs.length ? data.logs.map(l => `<tr><td class="p-2 whitespace-nowrap">${l.created_at}</td><td class="p-2 font-bold text-white">${l.activity_type}</td><td class="p-2">${l.description}</td><td class="p-2 font-mono text-[10px]">${l.ip_address||'-'}</td><td class="p-2 text-[10px]">${l.country||'-'}</td></tr>`).join('') : '<tr><td colspan="5" class="p-4 text-center">No activity found.</td></tr>';
                    document.getElementById('log-activity-body').innerHTML = actBody;

                    // Usage
                    const usageBody = data.usage.length ? data.usage.map(u => `<tr>
                        <td class="p-2 whitespace-nowrap text-[10px] text-slate-400">${u.created_at}</td>
                        <td class="p-2 text-white">${u.audio_tokens_in}</td>
                        <td class="p-2 text-white">${parseInt(u.text_tokens_in)+parseInt(u.text_tokens_out)} <span class="text-[9px] text-slate-500">(${u.text_tokens_in}/${u.text_tokens_out})</span></td>
                        <td class="p-2 text-green-400 text-xs">$${parseFloat(u.estimated_cost_audio||0).toFixed(4)}</td>
                        <td class="p-2 text-green-400 text-xs">$${(parseFloat(u.estimated_cost_text_in||0)+parseFloat(u.estimated_cost_text_out||0)).toFixed(4)}</td>
                        <td class="p-2 font-bold text-green-300">$${parseFloat(u.estimated_cost_total).toFixed(4)}</td>
                    </tr>`).join('') : '<tr><td colspan="6" class="p-4 text-center">No usage found.</td></tr>';
                    document.getElementById('log-usage-body').innerHTML = usageBody;

                } else {
                    alert(data.message);
                }
            } catch (e) {
                console.error(e);
                alert('Failed to fetch logs');
            }
        }

        function switchLogTab(tab) {
            document.getElementById('log-content-activity').classList.toggle('hidden', tab !== 'activity');
            document.getElementById('log-content-usage').classList.toggle('hidden', tab !== 'usage');

            document.getElementById('btn-log-activity').className = tab === 'activity' ? 'px-4 py-2 border-b-2 border-brand-500 text-brand-400 font-bold transition-colors' : 'px-4 py-2 border-b-2 border-transparent text-slate-400 font-bold hover:text-white transition-colors';
            document.getElementById('btn-log-usage').className = tab === 'usage' ? 'px-4 py-2 border-b-2 border-brand-500 text-brand-400 font-bold transition-colors' : 'px-4 py-2 border-b-2 border-transparent text-slate-400 font-bold hover:text-white transition-colors';
        }

        function openEditUser(id, email, role) {
            document.getElementById('edit-user-id').value = id;
            document.getElementById('edit-user-email').value = email;
            document.getElementById('edit-user-role').value = role;
            document.getElementById('modal-edit-user').classList.remove('hidden');
        }

        async function submitEditUser(e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'edit_user');

            try {
                const res = await fetch('user_actions.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
                alert('Update failed');
            }
        }

        function openRewardModal(id, email) {
            document.getElementById('reward-user-id').value = id;
            document.getElementById('reward-user-email').value = email;
            document.getElementById('modal-reward').classList.remove('hidden');
        }

        async function submitReward(e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'add_reward');
            try {
                const res = await fetch('user_actions.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (e) {
                console.error(e);
                alert('Failed');
            }
        }
    </script>

    <script>
        async function logout() {
            const formData = new FormData();
            formData.append('action', 'logout');
            await fetch('../../auth/api.php', {
                method: 'POST',
                body: formData
            });
            window.location.href = '../../login.php';
        }

        function toggleSidebar() {
            const sb = document.getElementById('admin-sidebar');
            const over = document.getElementById('sidebar-overlay');
            sb.classList.toggle('-translate-x-full');
            over.classList.toggle('hidden');
        }
    </script>
</body>

</html>