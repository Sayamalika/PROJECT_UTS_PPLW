<?php
require_once __DIR__ . '/bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    $selectedRole = $_POST['role'] ?? 'customer';
    $login = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '', $selectedRole);

    if ($login) {
        header('Location: dashboard.php');
        exit;
    }

    $error = 'Email, password, atau role salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Persewaan Mobil</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); width: 380px; }
        h2 { margin-top: 0; text-align: center; }
        input, button { width: 100%; box-sizing: border-box; padding: 12px; margin-top: 12px; border-radius: 8px; border: 1px solid #ddd; }
        button { background: #1f6feb; color: white; border: none; cursor: pointer; }
        .error { color: #dc3545; margin-top: 12px; text-align: center; }
        a { display: block; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Login</h2>
        <form method="POST">
            <select name="role" required style="width: 100%; box-sizing: border-box; padding: 12px; margin-top: 12px; border-radius: 8px; border: 1px solid #ddd;">
                <option value="customer">Pembeli</option>
                <option value="admin">Admin</option>
            </select>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Masuk</button>
        </form>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <a href="register.php">Belum punya akun? Daftar</a>
    </div>
</body>
</html>
