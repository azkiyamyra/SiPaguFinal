<?php
require_once __DIR__ . '/../../config.php';

if (!isset($_POST['id_jdwl'])) {
    redirect('admin/jadwal.php');
}

$id_jdwl = mysqli_real_escape_string($koneksi, $_POST['id_jdwl']);

// daftar tabel yang bergantung ke t_jdwl
$relasi = [
    't_transaksi_honor_dosen'
    // tambahkan tabel lain kalau ada
];

foreach ($relasi as $tabel) {
    $cek = mysqli_query(
        $koneksi,
        "SELECT 1 FROM $tabel WHERE id_jadwal='$id_jdwl' LIMIT 1"
    );

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['error_message'] =
            "Jadwal tidak bisa dihapus karena masih digunakan di tabel <b>$tabel</b>.";
        redirect('admin/jadwal.php');
    }
}

// jika aman, baru hapus
mysqli_query(
    $koneksi,
    "DELETE FROM t_jadwal WHERE id_jdwl='$id_jdwl'"
);

$_SESSION['success_message'] = 'Data jadwal berhasil dihapus';
redirect('admin/jadwal.php');