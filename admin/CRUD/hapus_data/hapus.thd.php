<?php
require_once __DIR__ . '/../../config.php';
session_start();

if (!isset($_POST['id_thd'])) {
    $_SESSION['error_message'] = 'ID tidak valid';
    header('Location: ../../admin/honor_dosen.php');
    exit;
}

$id_thd = (int) $_POST['id_thd'];

$stmt = $koneksi->prepare(
    "DELETE FROM t_transaksi_honor_dosen WHERE id_thd = ?"
);
$stmt->bind_param("i", $id_thd);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Data honor dosen berhasil dihapus';
} else {
    $_SESSION['error_message'] = 'Gagal menghapus data';
}

header('Location: ../../admin/honor_dosen.php');
exit;