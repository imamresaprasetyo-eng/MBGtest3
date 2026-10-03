<?php
// Class Induk
class MBGTransaction {
    protected $db;
    private $username;
    private $nis;
    private $kelas;
    private $jurusan;
    private $jumlah;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    // Encapsulation: Setter & Getter
    public function setUsername($username) { $this->username = $username; }
    public function getUsername() { return $this->username; }

    public function setNis($nis) { $this->nis = htmlspecialchars($nis); }
    public function getNis() { return $this->nis; }

    public function setKelas($kelas) { $this->kelas = htmlspecialchars($kelas); }
    public function getKelas() { return $this->kelas; }

    public function setJurusan($jurusan) { $this->jurusan = htmlspecialchars($jurusan); }
    public function getJurusan() { return $this->jurusan; }

    public function setJumlah($jumlah) { $this->jumlah = (int)$jumlah; }
    public function getJumlah() { return $this->jumlah; }
}

// Class Turunan untuk Pengambilan
class PengambilanMBG extends MBGTransaction {
    public function simpanDataAmbil($namaSiswa) {
        $username = $this->getUsername();
        $nis      = $this->getNis();
        $kelas    = $this->getKelas();
        $jurusan  = $this->getJurusan();
        $jumlah   = $this->getJumlah();
        $waktu    = date('Y-m-d H:i:s');
        $nama     = htmlspecialchars($namaSiswa);

        $stmt = $this->db->prepare("INSERT INTO transaksi_mbg (username, nis, nama_siswa, kelas, jurusan, jumlah_diambil, waktu_ambil, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Dipinjam')");
        $stmt->bind_param("sssssis", $username, $nis, $nama, $kelas, $jurusan, $jumlah, $waktu);
        return $stmt->execute();
    }
}

// Class Turunan untuk Pengembalian
class PengembalianMBG extends MBGTransaction {
    public function simpanDataKembali($idTransaksi, $batasMenit = 60, $username = null) {
        $waktuKembali = date('Y-m-d H:i:s');
        $jumlahKembali = $this->getJumlah();

        // Ambil data pengambilan
        $query = $this->db->prepare("SELECT waktu_ambil FROM transaksi_mbg WHERE id = ? AND status = 'Dipinjam' AND username = ?");
        $query->bind_param("is", $idTransaksi, $username);
        $query->execute();
        $result = $query->get_result()->fetch_assoc();

        if (!$result) return false;

        $selisihMenit = (strtotime($waktuKembali) - strtotime($result['waktu_ambil'])) / 60;
        $status = ($selisihMenit > $batasMenit) ? 'Terlambat' : 'Selesai';

        $update = $this->db->prepare("UPDATE transaksi_mbg SET jumlah_dikembalikan = ?, waktu_kembali = ?, status = ? WHERE id = ? AND status = 'Dipinjam' AND username = ?");
        $update->bind_param("issis", $jumlahKembali, $waktuKembali, $status, $idTransaksi, $username);
        return $update->execute();
    }
}
?>