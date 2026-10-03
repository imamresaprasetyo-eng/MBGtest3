<?php
session_start();
require_once 'config/database.php';

$error = '';

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Menghapus spasi yang tidak sengaja ketik di input
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $passwordValid = $result && (
            password_verify($password, $result['password']) ||
            hash_equals((string) $result['password'], $password)
        );

        if ($passwordValid) {
            if (!password_get_info($result['password'])['algo']) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                $update->bind_param("ss", $newHash, $result['username']);
                $update->execute();
            }
            session_regenerate_id(true);
            $_SESSION['user'] = $result['username'];
            $_SESSION['role'] = $result['role'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Username atau Password salah!";
        }
    } else {
        $error = "Harap isi username dan password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Sistem MBG</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body>
<div class="container" style="max-width:400px; margin-top:100px;">
    <h2>Login Sistem MBG</h2>
    
    <?php if ($error): ?>
        <p style="color:red; font-weight:bold; background-color:#fee; padding:10px; border-radius:4px; border:1px solid red; text-align:center;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required autocomplete="off" placeholder="admin">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="admin123">
        </div>
        <button type="submit">Login</button>
    </form>
    <p>Belum punya akun? <a href="daftar.php">Daftar sebagai murid atau guru</a></p>
</div>
</body>
</html>