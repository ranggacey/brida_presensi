CREATE DATABASE IF NOT EXISTS face_recognition_db;
USE face_recognition_db;

-- Tabel untuk menyimpan informasi pengguna
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- Simpan dalam bentuk hash (bcrypt)
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk menyimpan data wajah pengguna
CREATE TABLE face_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL, -- Path gambar wajah
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel untuk menyimpan riwayat absensi
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    status ENUM('Hadir', 'Tidak Hadir', 'Terlambat') DEFAULT 'Hadir',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tambahkan admin default
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@example.com', '$2y$10$wH1vXhXsO4QK/iTqumFruOfX3x8X/Tj8e5KzWwepeexwglqld5k9i', 'admin');
