<?php
// frontend/PyViz/reset_password.php
require_once 'auth/db.php';
require_once 'auth/Auth.php';

$token = $_GET['token'] ?? '';
$msg = '';
$valid = false;

$conn = DB::connect();
if ($token) {
    $stmt = DB::query("SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > NOW()", [$token], "s");
    if ($stmt->get_result()->fetch_assoc()) {
        $valid = true;
    } else {
        $msg = "Invalid or expired reset link.";
    }
} else {
    $msg = "Missing token.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $pass = $_POST['password'] ?? '';
    if (strlen($pass) < 6) {
        $msg = "Password must be at least 6 characters.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        DB::query("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE reset_token = ?", [$hash, $token], "ss");
        header("Location: index.php?msg=Password reset successful. Please login.");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | PyViz</title>
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
        <h2 class="text-2xl font-bold text-white mb-6 text-center">Set New Password</h2>
        
        <?php if($msg): ?>
            <div class="bg-red-900/30 text-red-200 p-3 rounded mb-4 text-sm border border-red-800">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if($valid): ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">New Password</label>
                <input type="password" name="password" required minlength="6" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-white focus:border-brand-500 focus:outline-none transition">
            </div>
            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-brand-600/20 transition">
                Update Password
            </button>
        </form>
        <?php else: ?>
            <div class="text-center">
                <a href="forgot_password.php" class="text-brand-400 hover:text-brand-300 underline">Request a new link</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
