<?php
// frontend/PyViz/forgot_password.php
require_once 'auth/db.php';
require_once 'auth/Auth.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    if ($email) {
        $conn = DB::connect();
        $stmt = DB::query("SELECT id FROM users WHERE email = ?", [$email], "s");
        if ($stmt->get_result()->fetch_assoc()) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            DB::query("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE email = ?", [$token, $expiry, $email], "sss");
            
            // In production, send email. Here we simulate.
            // If SMTP configured in app_settings...
            // For now, display link for dev convenience
            $basePath = dirname($_SERVER['PHP_SELF']); 
            // If we are in /frontend/PyViz, we might want to stay there or go up? 
            // The script is in the same folder, so dirname is correct base for this file.
            $link = "http://" . $_SERVER['HTTP_HOST'] . $basePath . "/reset_password.php?token=$token";
            $msg = "Password reset link generated (Simulation): <a href='$link' class='text-brand-400 underline'>Reset Link</a>";
        } else {
            $msg = "If that email exists, a reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | PyViz</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-200 h-screen flex items-center justify-center">
    <div class="bg-slate-900 p-8 rounded-2xl shadow-2xl border border-slate-800 w-full max-w-md">
        <h2 class="text-2xl font-bold text-white mb-2 text-center">Recover Password</h2>
        <p class="text-slate-400 text-sm text-center mb-6">Enter your email to receive a reset link.</p>
        
        <?php if($msg): ?>
            <div class="bg-blue-900/30 text-blue-200 p-3 rounded mb-4 text-sm border border-blue-800">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-white focus:border-brand-500 focus:outline-none transition">
            </div>
            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-brand-600/20 transition">
                Send Reset Link
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="text-slate-500 hover:text-white text-sm">Back to Login</a>
        </div>
    </div>
</body>
</html>
