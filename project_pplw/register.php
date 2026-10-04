<?php
require_once __DIR__ . '/bootstrap.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();

    try {
        $role = $_POST['role'] ?? 'customer';
        $password = $_POST['password'] ?? '';
        $retypePassword = $_POST['retype_password'] ?? '';

        if ($password !== $retypePassword) {
            throw new InvalidArgumentException('Password dan konfirmasi password tidak sama.');
        }

        $user->register($_POST['nama'] ?? '', $_POST['email'] ?? '', $password, $role, $retypePassword);
        $message = 'Registrasi berhasil. Silakan login.';
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Persewaan Mobil</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); width: 420px; }
        h2 { margin-top: 0; text-align: center; }
        input, button { width: 100%; box-sizing: border-box; padding: 12px; margin-top: 12px; border-radius: 8px; border: 1px solid #ddd; }
        button { background: #28a745; color: white; border: none; cursor: pointer; }
        .message { margin-top: 12px; text-align: center; color: #155724; }
        .error { color: #dc3545; }
        a { display: block; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Register</h2>
        <form method="POST">
            <select name="role" required style="width: 100%; box-sizing: border-box; padding: 12px; margin-top: 12px; border-radius: 8px; border: 1px solid #ddd;">
                <option value="customer">Pembeli</option>
                <option value="admin">Admin</option>
            </select>
            <input type="text" name="nama" placeholder="Nama lengkap" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="retype_password" placeholder="Ulangi Password" required>
            <button type="submit">Daftar</button>
        </form>
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'berhasil') !== false ? '' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <a href="login.php">Sudah punya akun? Login</a>
    </div>
</body>
</html>
