<?php
require_once __DIR__ . '/../../config.php';
session_start();

if (!isset($_POST['id_tu'])) {
    $_SESSION['error_message'] = 'ID tidak valid';
    header('Location: ../../admin/transaksi_ujian.php');
    exit;
}

$id_tu = (int) $_POST['id_tu'];

$stmt = $koneksi->prepare(
    "DELETE FROM t_transaksi_ujian WHERE id_tu = ?"
);
$stmt->bind_param("i", $id_tu);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Data Transaksi Ujian berhasil dihapus';
} else {
    $_SESSION['error_message'] = 'Gagal menghapus data';
}

header('Location: ../../admin/transaksi_ujian.php');
exit;