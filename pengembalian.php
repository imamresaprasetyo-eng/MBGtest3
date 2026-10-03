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

$stmtTransaksi = $conn->prepare("SELECT id, nis, nama_siswa, jumlah_diambil FROM transaksi_mbg WHERE username = ? AND status = 'Dipinjam' ORDER BY id DESC");
$stmtTransaksi->bind_param("s", $_SESSION['user']);
$stmtTransaksi->execute();
$listDipinjam = $stmtTransaksi->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pengembalian = new PengembalianMBG($conn);
    $jumlahKembali = filter_var($_POST['jumlah_dikembalikan'] ?? null, FILTER_VALIDATE_INT);
    if ($jumlahKembali !== false && $jumlahKembali > 0) {
        $pengembalian->setJumlah($jumlahKembali);
    }

    $idTransaksi = filter_var($_POST['id_transaksi'] ?? null, FILTER_VALIDATE_INT);
    if ($jumlahKembali !== false && $jumlahKembali > 0 && $idTransaksi !== false && $idTransaksi > 0 && $pengembalian->simpanDataKembali($idTransaksi, 60, $_SESSION['user'])) {
        $msg = "Data pengembalian berhasil diproses!";
    } else {
        $msg = "Gagal memproses pengembalian!";
        $isError = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Pengembalian MBG</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body>
<div class="container">
    <h2>Form Pengembalian MBG</h2>
    <div class="nav">
        <a href="index.php">Dashboard</a>
        <a href="pengambilan.php">Form Pengambilan</a>
        <a href="pengembalian.php">Form Pengembalian</a>
        <a href="logout.php" class="logout">Logout (<?= htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?>)</a>
    </div>

    <?php if ($msg): ?>
        <p style="color:<?= $isError ? 'red' : 'green'; ?>;"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Pilih transaksi Anda yang masih dipinjam</label>
            <select name="id_transaksi" required>
                <option value="">-- Pilih Siswa --</option>
                <?php while ($row = mysqli_fetch_assoc($listDipinjam)): ?>
                    <option value="<?= $row['id']; ?>">
                        <?= htmlspecialchars($row['nis'] . ' - ' . $row['nama_siswa'] . ' (Jumlah: ' . $row['jumlah_diambil'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Jumlah MBG dikembalikan</label>
            <input type="number" name="jumlah_dikembalikan" min="1" required>
        </div>
        <button type="submit">Proses Pengembalian</button>
    </form>
</div>
</body>
</html>