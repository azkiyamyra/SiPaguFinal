<?php
require_once __DIR__ . '/../../config.php';

if (!isset($_POST['id_user'])) {
    redirect('admin/users.php');
}

$id_user = (int) $_POST['id_user'];

$relasi = [
    't_jadwal',
    't_transaksi_ujian',
    't_transaksi_pa_ta'
];

foreach ($relasi as $tabel) {
    $sql = "SELECT 1 FROM $tabel WHERE id_user = ? LIMIT 1";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error_message'] =
            "User tidak bisa dihapus karena masih digunakan di tabel <b>$tabel</b>.";
        redirect('admin/users.php');
    }
}

// HAPUS USER
$stmt = $koneksi->prepare("DELETE FROM t_user WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();

$_SESSION['success_message'] = 'Data user berhasil dihapus';
redirect('admin/users.php');
