<?php
require_once __DIR__ . '/../../auth/Auth.php';
if (!Auth::isLoggedIn() || Auth::user()['role'] !== 'admin') {
    die("Access Denied: Super Admin Only");
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Management - VDraw Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif']
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            900: '#1e3a8a'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 100;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>

<body class="min-h-screen">

    <header class="glass-panel border-b border-slate-700 p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="../dashboard/index.php" class="px-3 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-sm border border-slate-700 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Dashboard
            </a>
            <h1 class="text-xl font-bold text-white"><i class="fa-solid fa-envelope mr-2 text-brand-400"></i>Email Management</h1>
        </div>
        <nav class="flex gap-2 flex-wrap">
            <button onclick="switchTab('templates')" class="nav-btn px-4 py-2 rounded text-sm font-medium bg-brand-600 text-white" data-tab="templates">Templates</button>
            <button onclick="switchTab('batches')" class="nav-btn px-4 py-2 rounded text-sm font-medium bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700" data-tab="batches">Batches</button>
            <button onclick="switchTab('delivery')" class="nav-btn px-4 py-2 rounded text-sm font-medium bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700" data-tab="delivery">Delivery</button>
            <button onclick="switchTab('smtp')" class="nav-btn px-4 py-2 rounded text-sm font-medium bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700" data-tab="smtp">SMTP</button>
            <button onclick="switchTab('logs')" class="nav-btn px-4 py-2 rounded text-sm font-medium bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700" data-tab="logs">Logs</button>
        </nav>
    </header>

    <main class="p-4 md:p-8 max-w-7xl mx-auto">
        <div id="tab-templates" class="tab-content active">
            <?php include __DIR__ . '/views/templates.php'; ?>
        </div>
        <div id="tab-batches" class="tab-content">
            <?php include __DIR__ . '/views/batches.php'; ?>
        </div>
        <div id="tab-delivery" class="tab-content">
            <?php include __DIR__ . '/views/delivery.php'; ?>
        </div>
        <div id="tab-smtp" class="tab-content">
            <?php include __DIR__ . '/views/smtp.php'; ?>
        </div>
        <div id="tab-logs" class="tab-content">
            <?php include __DIR__ . '/views/logs.php'; ?>
        </div>
    </main>

    <script src="app.js"></script>
</body>

</html>