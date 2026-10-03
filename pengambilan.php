<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'classes/MBG.php';

$msg = '';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $jumlah = filter_var($_POST['jumlah_diambil'] ?? null, FILTER_VALIDATE_INT);
    $nisLength = function_exists('mb_strlen') ? mb_strlen($nis, 'UTF-8') : strlen($nis);
    $namaLength = function_exists('mb_strlen') ? mb_strlen($nama, 'UTF-8') : strlen($nama);
    $kelasLength = function_exists('mb_strlen') ? mb_strlen($kelas, 'UTF-8') : strlen($kelas);
    $jurusanLength = function_exists('mb_strlen') ? mb_strlen($jurusan, 'UTF-8') : strlen($jurusan);

    if ($nis === '' || $nama === '' || !in_array($jurusan, ['RPL', 'PBS', 'KA', 'TEI'], true) || $jumlah === false || $jumlah < 1 || $nisLength > 20 || $namaLength > 100 || $kelasLength > 20 || $jurusanLength > 50) {
        $msg = 'Isi identitas sesuai batas kolom dan jumlah MBG dengan benar.';
        $isError = true;
    } else {
        $pengambilan = new PengambilanMBG($conn);
        $pengambilan->setUsername($_SESSION['user']);
        $pengambilan->setNis($nis);
        $pengambilan->setKelas($kelas);
        $pengambilan->setJurusan($jurusan);
        $pengambilan->setJumlah($jumlah);

        if ($pengambilan->simpanDataAmbil($nama)) {
            $msg = 'Pengambilan MBG berhasil dicatat.';
            $_POST = [];
        } else {
            $msg = 'Pengambilan gagal disimpan. Silakan coba lagi.';
            $isError = true;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Pengambilan MBG</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body>
<div class="container">
    <h2>Sistem Pengambilan & Pengembalian MBG</h2>
    <div class="nav">
        <a href="index.php">Dashboard</a>
        <a href="pengambilan.php">Form Pengambilan</a>
        <a href="pengembalian.php">Form Pengembalian</a>
        <a href="logout.php" class="logout">Logout (<?= htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?>)</a>
    </div>

    <h3>Catat Pengambilan</h3>
    <p>Masuk sebagai <?= htmlspecialchars($_SESSION['role'] === 'guru' ? 'Guru' : 'Murid', ENT_QUOTES, 'UTF-8'); ?>.</p>
    <?php if ($msg): ?>
        <p style="color:<?= $isError ? 'red' : 'green'; ?>; font-weight:bold;"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST" action="pengambilan.php">
        <div class="form-group">
            <label>NIS / NIP</label>
            <input type="text" name="nis" required maxlength="20" value="<?= htmlspecialchars($_POST['nis'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="form-group">
            <label>Nama</label>
            <input type="text" name="nama" required maxlength="100" value="<?= htmlspecialchars($_POST['nama'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="form-group">
            <label>Kelas (murid) / unit kerja (guru)</label>
            <input type="text" name="kelas" maxlength="20" value="<?= htmlspecialchars($_POST['kelas'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="form-group">
            <label>Jurusan (jika ada)</label>
            <select name="jurusan" required>
                <option value="">-- Pilih jurusan --</option>
                <?php foreach (['RPL', 'PBS', 'KA', 'TEI'] as $pilihanJurusan): ?>
                    <option value="<?= $pilihanJurusan; ?>" <?= ($_POST['jurusan'] ?? '') === $pilihanJurusan ? 'selected' : ''; ?>><?= $pilihanJurusan; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Jumlah MBG diambil</label>
            <input type="number" name="jumlah_diambil" min="1" required value="<?= htmlspecialchars($_POST['jumlah_diambil'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <button type="submit">Catat Pengambilan</button>
    </form>
</div>

</body>
</html>