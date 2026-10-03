<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$query = "SELECT * FROM transaksi_mbg ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard MBG</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body data-current-user="<?= htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?>">
<div class="container">
    <h2>Sistem Pengambilan & Pengembalian MBG</h2>
    <div class="nav">
        <a href="index.php">Dashboard</a>
        <a href="pengambilan.php">Form Pengambilan</a>
        <a href="pengembalian.php">Form Pengembalian</a>
        <a href="logout.php" class="logout">Logout (<?= $_SESSION['user']; ?>)</a>
    </div>

    <section class="alarm-panel" id="alarm-panel" aria-labelledby="alarm-title">
        <div>
            <h3 id="alarm-title">Pengingat pengembalian MBG</h3>
            <p id="alarm-status" aria-live="polite">Memeriksa transaksi yang masih dipinjam...</p>
        </div>
        <button type="button" id="enable-alarm">Aktifkan alarm HP</button>
    </section>

    <h3>Daftar Transaksi MBG</h3>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>NIS</th>
                <th>Nama</th>
                <th>Kelas / Jurusan</th>
                <th>Jumlah Ambil</th>
                <th>Waktu Ambil</th>
                <th>Jumlah Kembali</th>
                <th>Waktu Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr data-transaksi-id="<?= (int) $row['id']; ?>" data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?>" data-waktu-ambil="<?= htmlspecialchars($row['waktu_ambil'], ENT_QUOTES, 'UTF-8'); ?>" data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>">
                <td><?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($row['nis'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($row['nama_siswa'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($row['kelas'] . ' ' . $row['jurusan'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($row['jumlah_diambil'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($row['waktu_ambil'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($row['jumlah_dikembalikan'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= $row['waktu_kembali'] ? htmlspecialchars($row['waktu_kembali'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                <td>
                    <?php 
                    if($row['status'] == 'Terlambat') {
                        echo '<span class="badge-terlambat">Terlambat</span>';
                    } elseif($row['status'] == 'Dipinjam') {
                        echo '<span class="badge-dipinjam">Dipinjam</span>';
                    } else {
                        echo '<span class="badge-selesai">Selesai</span>';
                    }
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="assets/js/alarm.js?v=2"></script>
</body>
</html>