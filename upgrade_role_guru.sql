ALTER TABLE users
MODIFY role ENUM('admin', 'siswa', 'guru') DEFAULT 'siswa' NULL;