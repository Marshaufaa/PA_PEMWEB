-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 13, 2025 at 03:06 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_berita`
--

-- --------------------------------------------------------

--
-- Table structure for table `berita`
--

CREATE TABLE `berita` (
  `id_berita` int NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi_berita` text NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `view_count` int NOT NULL DEFAULT '0',
  `tgl_upload` datetime NOT NULL,
  `id_penulis` int NOT NULL,
  `id_kategori` int NOT NULL,
  `tipe_konten` enum('Berita','Data','Artikel','Tutorial') NOT NULL DEFAULT 'Berita'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Pendidikan'),
(2, 'Teknologi'),
(3, 'Kesehatan'),
(4, 'Ekonomi');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `nama` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `peran` enum('admin','uploader','mahasiswa','masyarakat') NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `deskripsi` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `username`, `password`, `peran`, `foto_profil`, `deskripsi`) VALUES
(2, 'Uploader Handal', 'uploader@web.com', 'uploader', '$2y$10$T8.suxeVrY.9O.H2.Dq9UeJd6Q9.GF.2eG/M6.0n.6v.Z.P/f1k.O', 'uploader', NULL, NULL),
(3, 'Budi Mahasiswa', 'budi@kampus.com', 'budimhs', '$2y$10$T8.suxeVrY.9O.H2.Dq9UeJd6Q9.GF.2eG/M6.0n.6v.Z.P/f1k.O', 'mahasiswa', NULL, NULL),
(5, 'rr', 'nloh3706@gmail.com', 'rr', '$2y$10$C6S8lIz8iVIB5TL31.k6E.SnJIUY/EtAaU.aTdQpf288sE2GSsyWe', 'uploader', 'uploads/profil/5_1762778231.png', 'Tulis deskripsi singkat tentang Anda di sini...'),
(6, 'gelo', 'belajarpastimudah@gmail.com', 'gg', '$2y$10$wI6gv.roIs2WKam1ysMf.et7RRfEnO95z9uATPu2c74rHQXkL.nZm', 'masyarakat', NULL, NULL),
(7, 'bbbbbbbb', 'yaelah@gmail.com', 'p', '$2y$10$tOe1Tlr6XyeR8vczNi3kVOw1m5Pq.JS9fuxlSS9fT8LV6FHFTvPna', 'uploader', NULL, NULL),
(10, 'gelo', 'rikz@gg', 'eer', '$2y$10$fKvtwSAn147dvpYhCio1c.62imAGke5NDoqC0O1kRfITe0eDyn2mG', 'masyarakat', NULL, NULL),
(13, 'Admin Utama', 'admin@web.com', 'admin', '$2y$10$fKvtwSAn147dvpYhCio1c.62imAGke5NDoqC0O1kRfITe0eDyn2mG', 'admin', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `berita`
--
ALTER TABLE `berita`
  ADD PRIMARY KEY (`id_berita`),
  ADD KEY `id_penulis` (`id_penulis`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `berita`
--
ALTER TABLE `berita`
  MODIFY `id_berita` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `berita`
--
ALTER TABLE `berita`
  ADD CONSTRAINT `berita_ibfk_1` FOREIGN KEY (`id_penulis`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `berita_ibfk_2` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
