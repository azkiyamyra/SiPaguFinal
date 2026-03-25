<?php
/**
 * PROSES UPDATE JADWAL - SiPagu
 * Lokasi: admin/proses_jadwal.php
 */

// Include required files
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id_jdwl = mysqli_real_escape_string($koneksi, $_POST['id_jdwl']);
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $kode_matkul = mysqli_real_escape_string($koneksi, $_POST['kode_matkul']);
    $nama_matkul = mysqli_real_escape_string($koneksi, $_POST['nama_matkul']);
    $jml_mhs = mysqli_real_escape_string($koneksi, $_POST['jml_mhs']);
    
    $update = mysqli_query($koneksi, "
        UPDATE t_jadwal SET
            semester = '$semester',
            kode_matkul = '$kode_matkul',
            nama_matkul = '$nama_matkul',
            jml_mhs = '$jml_mhs'
        WHERE id_jdwl = '$id_jdwl'
    ");
    
    if ($update) {
        $_SESSION['success_message'] = "Data jadwal berhasil diperbarui!";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui data: " . mysqli_error($koneksi);
    }
    
    header("Location: jadwal.php");
    exit;
}