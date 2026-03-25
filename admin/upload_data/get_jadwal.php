<?php
/**
 * GET DATA JADWAL - SiPagu
 * API untuk mendapatkan data jadwal via AJAX
 * Lokasi: admin/get_jadwal.php
 */
require_once __DIR__ . '/../../config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
    
    $query = mysqli_query($koneksi, "SELECT * FROM t_jadwal WHERE id_jdwl = '$id'");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Data tidak ditemukan'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}