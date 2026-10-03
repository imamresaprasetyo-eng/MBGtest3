<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($username === '' || $password === '' || !in_array($role, ['siswa', 'guru'], true)) {
        $error = 'Lengkapi semua data dengan benar.';
    } elseif (strlen($username) > 50 || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Nama pengguna harus 3-50 karakter dan hanya boleh memakai huruf, angka, titik, garis bawah, atau tanda hubung.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi harus terdiri dari minimal 8 karakter.';
    } else {
        $check = $conn->prepare("SELECT username FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
        $check->bind_param("s", $username);
        $check->execute();

        if ($check->get_result()->fetch_assoc()) {
            $error = 'Nama pengguna sudah digunakan.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $username, $passwordHash, $role);

            if ($insert->execute()) {
                session_regenerate_id(true);
                $_SESSION['user'] = $username;
                $_SESSION['role'] = $role;
                header("Location: index.php");
                exit();
            }

            $error = 'Pendaftaran gagal. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar - Sistem MBG</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body>
<div class="container" style="max-width:400px; margin-top:80px;">
    <h2>Daftar Akun MBG</h2>

    <?php if ($error): ?>
        <p style="color:red; font-weight:bold; background-color:#fee; padding:10px; border-radius:4px; border:1px solid red; text-align:center;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="daftar.php">
        <div class="form-group">
            <label>Nama pengguna</label>
            <input type="text" name="username" required autocomplete="username" minlength="3" maxlength="50" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="form-group">
            <label>Kata sandi (minimal 8 karakter)</label>
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
            <label>Peran</label>
            <select name="role" required>
                <option value="">-- Pilih peran --</option>
                <option value="siswa">Murid</option>
                <option value="guru">Guru</option>
            </select>
        </div>
        <button type="submit">Daftar</button>
    </form>
    <p>Sudah punya akun? <a href="login.php">Login</a></p>
</div>
</body>
</html>