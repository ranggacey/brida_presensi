-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 19 Feb 2025 pada 07.42
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_magang`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_masuk` time NOT NULL,
  `waktu_keluar` time DEFAULT NULL,
  `status` enum('hadir','terlambat','tidak hadir') NOT NULL DEFAULT 'hadir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Hadir','Tidak Hadir') NOT NULL DEFAULT 'Hadir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `date`, `status`, `created_at`) VALUES
(1, 1, '2025-02-19', 'Hadir', '2025-02-19 04:03:26'),
(2, 1, '2025-02-19', 'Hadir', '2025-02-19 04:41:52'),
(3, 4, '2025-02-19', 'Hadir', '2025-02-19 04:42:43'),
(4, 4, '2025-02-19', 'Hadir', '2025-02-19 04:52:36'),
(5, 4, '2025-02-19', 'Hadir', '2025-02-19 04:57:00'),
(6, 1, '2025-02-19', 'Hadir', '2025-02-19 05:08:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `aktivitas` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `instansi` varchar(100) NOT NULL,
  `role` enum('magang','admin') NOT NULL DEFAULT 'magang',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `instansi`, `role`, `foto`, `created_at`) VALUES
(1, 'gundogan', 'gundogan@gmail.com', '$2y$10$FOP2I3eLQTebxlxUT32q.uGweOZTP6mPEZfvJ6/yh8d3Ddvc0V1y.', '', 'admin', NULL, '2025-02-19 03:34:34'),
(3, 'rangga', 'ranggadanceyong@gmail.com', '$2y$10$.5IHxgIZGurYVwk8JG2P6OBlzcKFimKvEicsc.vGrHkZuXmcUaHtm', '', 'magang', NULL, '2025-02-19 04:00:51'),
(4, 'agil', 'agil@gmail.com', '$2y$10$/Xw2y0M6Dp.Tf6GPh7ZZ6.l7nA0UolmWARb.KmgKCen33sSojjbfy', '', 'magang', NULL, '2025-02-19 04:28:26');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
