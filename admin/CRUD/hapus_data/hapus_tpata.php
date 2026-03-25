<?php
require_once __DIR__ . '/../../config.php';
session_start();

if (!isset($_POST['id_tpt'])) {
    $_SESSION['error_message'] = 'ID tidak valid';
    header('Location: ../../admin/pa_ta.php');
    exit;
}

$id_tpt = (int) $_POST['id_tpt'];

$stmt = $koneksi->prepare(
    "DELETE FROM t_transaksi_pa_ta WHERE id_tpt = ?"
);
$stmt->bind_param("i", $id_tpt);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Data PA/TA berhasil dihapus';
} else {
    $_SESSION['error_message'] = 'Gagal menghapus data';
}

header('Location: ../../admin/pa_ta.php');
exit;