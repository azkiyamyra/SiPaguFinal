-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 25 Mar 2026 pada 03.43
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sistem_honor_udinus`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_approval_status`
--

CREATE TABLE `t_approval_status` (
  `id_approval` int(11) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `status` enum('draft','diverifikasi','disetujui','dicairkan','ditolak') DEFAULT 'draft',
  `approval_notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_approval_status`
--

INSERT INTO `t_approval_status` (`id_approval`, `table_name`, `record_id`, `status`, `approval_notes`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 'transaksi_ujian', 12, 'ditolak', NULL, 99, '2026-03-04 09:21:11', '2026-03-04 09:21:11', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_jadwal`
--

CREATE TABLE `t_jadwal` (
  `id_jdwl` int(11) NOT NULL,
  `semester` varchar(5) NOT NULL,
  `kode_matkul` varchar(7) NOT NULL,
  `nama_matkul` varchar(30) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jml_mhs` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_jadwal`
--

INSERT INTO `t_jadwal` (`id_jdwl`, `semester`, `kode_matkul`, `nama_matkul`, `id_user`, `jml_mhs`) VALUES
(2, '20262', 'SI101', 'Algoritma pemrograman', 1, 3),
(3, '20261', 'SI200', 'Informatika', 99, 36),
(6, '20242', 'SI102', 'Struktur Data', 125, 30),
(7, '20251', 'SI201', 'Basis Data', 126, 40),
(8, '20252', 'SI202', 'Pemrograman Web', 127, 28),
(9, '20261', 'SI245', 'Sains Data', 126, 6),
(10, '20261', 'S081', 'Tambang', 132, 10);

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_panitia`
--

CREATE TABLE `t_panitia` (
  `id_pnt` int(11) NOT NULL,
  `jbtn_pnt` varchar(100) NOT NULL,
  `honor_std` int(11) NOT NULL,
  `honor_p1` int(11) DEFAULT NULL,
  `honor_p2` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_panitia`
--

INSERT INTO `t_panitia` (`id_pnt`, `jbtn_pnt`, `honor_std`, `honor_p1`, `honor_p2`) VALUES
(9, 'ketua acara', 10000, 0, 0),
(17, 'Sekretaris', 8000, 0, 0),
(18, 'Bendahara', 8000, 0, 0),
(19, 'Anggota', 5000, 0, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_transaksi_honor_dosen`
--

CREATE TABLE `t_transaksi_honor_dosen` (
  `id_thd` int(11) NOT NULL,
  `semester` varchar(5) DEFAULT NULL,
  `bulan` enum('januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember') NOT NULL,
  `id_jadwal` int(11) NOT NULL,
  `jml_tm` int(11) NOT NULL,
  `sks_tempuh` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_transaksi_honor_dosen`
--

INSERT INTO `t_transaksi_honor_dosen` (`id_thd`, `semester`, `bulan`, `id_jadwal`, `jml_tm`, `sks_tempuh`) VALUES
(23, '20261', 'maret', 8, 10, 11),
(30, NULL, 'juli', 10, 8, 10);

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_transaksi_pa_ta`
--

CREATE TABLE `t_transaksi_pa_ta` (
  `id_tpt` int(11) NOT NULL,
  `semester` varchar(5) NOT NULL,
  `periode_wisuda` enum('januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember') NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_panitia` int(11) NOT NULL,
  `jml_mhs_prodi` int(11) NOT NULL,
  `jml_mhs_bimbingan` int(11) NOT NULL,
  `prodi` varchar(100) NOT NULL,
  `jml_pgji_1` int(11) NOT NULL,
  `jml_pgji_2` int(11) DEFAULT NULL,
  `ketua_pgji` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_transaksi_pa_ta`
--

INSERT INTO `t_transaksi_pa_ta` (`id_tpt`, `semester`, `periode_wisuda`, `id_user`, `id_panitia`, `jml_mhs_prodi`, `jml_mhs_bimbingan`, `prodi`, `jml_pgji_1`, `jml_pgji_2`, `ketua_pgji`) VALUES
(6, '20251', 'maret', 99, 9, 3, 5, 'informatika', 4, 2, 'kiyak'),
(8, '20242', 'september', 3, 9, 2, 2, 'kesehatan', 2, 3, 'ya'),
(15, '20262', 'agustus', 125, 19, 3, 2, 'Teknik Mesin', 2, 3, 'Az'),
(18, '20271', 'maret', 124, 18, 3, 4, 'Teknik Mesin', 2, 3, 'Aziz S.M'),
(19, '20261', 'juli', 132, 9, 10, 2, 'Teknik Mesin', 2, 2, 'Aziz S.M');

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_transaksi_ujian`
--

CREATE TABLE `t_transaksi_ujian` (
  `id_tu` int(11) NOT NULL,
  `semester` varchar(10) NOT NULL,
  `id_panitia` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jml_mhs_prodi` int(11) NOT NULL,
  `jml_mhs` int(11) NOT NULL,
  `jml_koreksi` int(11) NOT NULL,
  `jml_matkul` int(11) NOT NULL,
  `jml_pgws_pagi` int(11) NOT NULL,
  `jml_pgws_sore` int(11) NOT NULL,
  `jml_koor_pagi` int(11) NOT NULL,
  `jml_koor_sore` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_transaksi_ujian`
--

INSERT INTO `t_transaksi_ujian` (`id_tu`, `semester`, `id_panitia`, `id_user`, `jml_mhs_prodi`, `jml_mhs`, `jml_koreksi`, `jml_matkul`, `jml_pgws_pagi`, `jml_pgws_sore`, `jml_koor_pagi`, `jml_koor_sore`) VALUES
(3, '20251', 9, 1, 5, 5, 5, 5, 5, 5, 5, 5),
(8, '20262', 17, 126, 2, 5, 4, 4, 3, 2, 3, 3),
(9, '20251', 17, 125, 5, 5, 5, 5, 5, 5, 5, 5),
(10, '20252', 18, 99, 3, 4, 5, 3, 3, 4, 5, 7),
(12, '20271', 19, 132, 2, 2, 2, 2, 2, 3, 2, 2),
(13, '20261', 9, 132, 3, 4, 8, 8, 9, 10, 9, 8);

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_user`
--

CREATE TABLE `t_user` (
  `id_user` int(11) NOT NULL,
  `npp_user` varchar(20) NOT NULL,
  `nik_user` char(16) NOT NULL,
  `npwp_user` varchar(20) NOT NULL,
  `norek_user` varchar(30) NOT NULL,
  `nama_user` varchar(100) NOT NULL,
  `nohp_user` varchar(20) NOT NULL,
  `pw_user` varchar(255) NOT NULL,
  `role_user` enum('koordinator','admin','staff') NOT NULL,
  `honor_persks` int(11) DEFAULT 50000,
  `remember_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_user`
--

INSERT INTO `t_user` (`id_user`, `npp_user`, `nik_user`, `npwp_user`, `norek_user`, `nama_user`, `nohp_user`, `pw_user`, `role_user`, `honor_persks`, `remember_token`) VALUES
(1, '0686.11.1995.071', '3374010101950001', '12.345.678.9-012.000', ' 1410003456789', 'Dr. Andi Prasetyo, M.Kom', '0881234567', '$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry', 'admin', 0, NULL),
(2, '0721.12.1998.034', '3374010202980002', '23.456.789.0-123.000', '1410002345678', 'Siti Rahmawati, M.T', '081234567802', '$2y$10$/BkQAaJ1bw9lkxcDOzDd9eSa6AClBvvyR35Gs0gLNEuilpuazcSZO', 'staff', 0, NULL),
(3, '0815.10.2001.112', '3374010303010003', '34.567.890.1-234.000', '1410003456789', 'Budi Santoso, S.Kom', '081234567803', '$2y$10$ptdJFN18H5.rbrgNfL8d.uD5bZPLgimtnceE3J/H6O3lHAdP3thr6', 'staff', 0, NULL),
(99, '0686.11.1995.000', '1111111111111111', '12.335.678.9-012.134', '141000123425', 'Azkiya, S.Kom', '0882005337277', '$2y$10$8Fz.xsh5Jtv.ApBGdTm7YeeX1hF401W9mZXH49Ir6kymdelIxzxuC', 'koordinator', 0, NULL),
(124, '1145.02.1988.090', '3273011212880001', '67.890.123.4-567.000', '1410009876543', 'Rina Wijaya S.T', '081345678910', '$2y$10$XPWJjFx7puJEG85CsB1TY.90fGPvgclIF2R8Lkgz42EMLBNJ/juPm', 'staff', 0, NULL),
(125, '1256.04.1992.012', '3171021505920005', '78.901.234.5-678.000', '1410008765432', 'Ahmad Fauzi M.Pd', '081345678911', '$2y$10$vL4ebftOJv7iqUZQ5Z.kF.G4SAdGoYGhYiSK0R8/Cbm6yn8MWplQ2', 'staff', 0, NULL),
(126, '1367.06.1996.034', '3578032010960002', '89.012.345.6-789.000', '1410007654321', 'Linda Permata M.Ak', '081345678912', '$2y$10$ux60z8e1zLvhBIjxzS8yDONOsxK/C9Hq.5VesaLTcY.vwKBuybcGK', 'staff', 0, NULL),
(127, '1478.08.1980.056', '3374042508800008', '90.123.456.7-890.000', '1410006543210', 'Dr. Hendra Kusuma', '081345678913', '$2y$10$YbyG/uaUxmOnz/FijzBne.Xz86/3KJToWgaAMQOkYQNl7qPhOvQ36', 'staff', 100000, NULL),
(128, '1589.10.1994.078', '5171053012940003', '01.234.567.8-901.000', '1410005432109', 'Maya Sartika S.Psi', '081345678914', '$2y$10$d2cVNvFLxu7mPqaVlKqW0uuP9UP9orv1EFEq9oVisaYRVEFZVrIMu', 'staff', 0, NULL),
(132, '0123.11.1995.000', '2323232111111111', '12.335.678.9-012.134', '141000123425', 'Fachri Saputra S.Tr.Kom', '0812305337277', '$2y$10$K5phSDfhZs4M9OFVG6ZEF.WQDGBiCEB9kJWkwK.6nA1qVFY6Q869.', 'staff', 10000, NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `t_approval_status`
--
ALTER TABLE `t_approval_status`
  ADD PRIMARY KEY (`id_approval`),
  ADD UNIQUE KEY `unique_record` (`table_name`,`record_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_approved_by` (`approved_by`);

--
-- Indeks untuk tabel `t_jadwal`
--
ALTER TABLE `t_jadwal`
  ADD PRIMARY KEY (`id_jdwl`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `t_panitia`
--
ALTER TABLE `t_panitia`
  ADD PRIMARY KEY (`id_pnt`) USING BTREE;

--
-- Indeks untuk tabel `t_transaksi_honor_dosen`
--
ALTER TABLE `t_transaksi_honor_dosen`
  ADD PRIMARY KEY (`id_thd`),
  ADD KEY `id_jadwal` (`id_jadwal`);

--
-- Indeks untuk tabel `t_transaksi_pa_ta`
--
ALTER TABLE `t_transaksi_pa_ta`
  ADD PRIMARY KEY (`id_tpt`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_panitia` (`id_panitia`);

--
-- Indeks untuk tabel `t_transaksi_ujian`
--
ALTER TABLE `t_transaksi_ujian`
  ADD PRIMARY KEY (`id_tu`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_panitia` (`id_panitia`);

--
-- Indeks untuk tabel `t_user`
--
ALTER TABLE `t_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `t_approval_status`
--
ALTER TABLE `t_approval_status`
  MODIFY `id_approval` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `t_jadwal`
--
ALTER TABLE `t_jadwal`
  MODIFY `id_jdwl` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `t_panitia`
--
ALTER TABLE `t_panitia`
  MODIFY `id_pnt` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `t_transaksi_honor_dosen`
--
ALTER TABLE `t_transaksi_honor_dosen`
  MODIFY `id_thd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `t_transaksi_pa_ta`
--
ALTER TABLE `t_transaksi_pa_ta`
  MODIFY `id_tpt` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `t_transaksi_ujian`
--
ALTER TABLE `t_transaksi_ujian`
  MODIFY `id_tu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `t_user`
--
ALTER TABLE `t_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `t_jadwal`
--
ALTER TABLE `t_jadwal`
  ADD CONSTRAINT `t_jadwal_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `t_user` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `t_transaksi_honor_dosen`
--
ALTER TABLE `t_transaksi_honor_dosen`
  ADD CONSTRAINT `t_transaksi_honor_dosen_ibfk_1` FOREIGN KEY (`id_jadwal`) REFERENCES `t_jadwal` (`id_jdwl`);

--
-- Ketidakleluasaan untuk tabel `t_transaksi_pa_ta`
--
ALTER TABLE `t_transaksi_pa_ta`
  ADD CONSTRAINT `t_transaksi_pa_ta_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `t_user` (`id_user`),
  ADD CONSTRAINT `t_transaksi_pa_ta_ibfk_3` FOREIGN KEY (`id_panitia`) REFERENCES `t_panitia` (`id_pnt`);

--
-- Ketidakleluasaan untuk tabel `t_transaksi_ujian`
--
ALTER TABLE `t_transaksi_ujian`
  ADD CONSTRAINT `t_transaksi_ujian_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `t_user` (`id_user`),
  ADD CONSTRAINT `t_transaksi_ujian_ibfk_4` FOREIGN KEY (`id_panitia`) REFERENCES `t_panitia` (`id_pnt`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
