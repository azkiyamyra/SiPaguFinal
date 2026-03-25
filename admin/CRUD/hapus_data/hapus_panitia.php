<?php
require_once __DIR__ . '/../../config.php';

if (!isset($_POST['id_pnt'])) {
    redirect('admin/panitia.php');
}

$id_pnt = mysqli_real_escape_string($koneksi, $_POST['id_pnt']);

// daftar tabel yang bergantung ke t_pnt
$relasi = [
    't_transaksi_pa_ta'
    // tambahkan tabel lain kalau ada
];

foreach ($relasi as $tabel) {
    $cek = mysqli_query(
        $koneksi,
        "SELECT 1 FROM $tabel WHERE id_panitia='$id_pnt' LIMIT 1"
    );

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['error_message'] =
            "Panitia tidak bisa dihapus karena masih digunakan di tabel <b>$tabel</b>.";
        redirect('admin/panitia.php');
    }
}

// jika aman, baru hapus
mysqli_query(
    $koneksi,
    "DELETE FROM t_panitia WHERE id_pnt='$id_pnt'"
);

$_SESSION['success_message'] = 'Data panitia berhasil dihapus';
redirect('admin/panitia.php');