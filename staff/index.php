<?php
session_start();
require_once '../config.php';
require_once 'includes/function_helper.php';

// Cek login dan role
if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$semester_aktif = '20262'; // Sesuai database

// Ambil data user
$query_user = "SELECT * FROM t_user WHERE id_user = ?";
$stmt = mysqli_prepare($koneksi, $query_user);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

// Hitung total honor (hanya yang statusnya sudah diverifikasi/disetujui/dicairkan)
$total_honor = 0;
$detail_per_jenis = [
    'mengajar' => 0,
    'pa_ta' => 0,
    'ujian' => 0
];

// 1. Honor Mengajar dari t_transaksi_honor_dosen
$query_mengajar = "
    SELECT thd.*, jdwl.kode_matkul, jdwl.nama_matkul, 
           COALESCE(u.honor_persks, 50000) as honor_persks,
           a.status
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal jdwl ON thd.id_jadwal = jdwl.id_jdwl
    JOIN t_user u ON jdwl.id_user = u.id_user
    LEFT JOIN t_approval_status a ON a.table_name = 'transaksi_honor_dosen' AND a.record_id = thd.id_thd
    WHERE jdwl.id_user = ? 
    AND thd.semester = ?
    AND (a.status IS NULL OR a.status IN ('diverifikasi', 'disetujui', 'dicairkan'))
";

$stmt = mysqli_prepare($koneksi, $query_mengajar);
mysqli_stmt_bind_param($stmt, "is", $id_user, $semester_aktif);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $honor = $row['jml_tm'] * $row['sks_tempuh'] * $row['honor_persks'];
    $detail_per_jenis['mengajar'] += $honor;
    $total_honor += $honor;
}

// 2. Honor PA/TA dari t_transaksi_pa_ta
$query_pa_ta = "
    SELECT tpt.*, p.jbtn_pnt, p.honor_std, a.status
    FROM t_transaksi_pa_ta tpt
    JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
    LEFT JOIN t_approval_status a ON a.table_name = 'transaksi_pa_ta' AND a.record_id = tpt.id_tpt
    WHERE tpt.id_user = ? 
    AND tpt.semester = ?
    AND (a.status IS NULL OR a.status IN ('diverifikasi', 'disetujui', 'dicairkan'))
";

$stmt = mysqli_prepare($koneksi, $query_pa_ta);
mysqli_stmt_bind_param($stmt, "is", $id_user, $semester_aktif);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $detail_per_jenis['pa_ta'] += $row['honor_std'];
    $total_honor += $row['honor_std'];
}

// 3. Honor Ujian dari t_transaksi_ujian
$query_ujian = "
    SELECT tu.*, p.jbtn_pnt, p.honor_std, a.status
    FROM t_transaksi_ujian tu
    JOIN t_panitia p ON tu.id_panitia = p.id_pnt
    LEFT JOIN t_approval_status a ON a.table_name = 'transaksi_ujian' AND a.record_id = tu.id_tu
    WHERE tu.id_user = ? 
    AND tu.semester = ?
    AND (a.status IS NULL OR a.status IN ('diverifikasi', 'disetujui', 'dicairkan'))
";

$stmt = mysqli_prepare($koneksi, $query_ujian);
mysqli_stmt_bind_param($stmt, "is", $id_user, $semester_aktif);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $detail_per_jenis['ujian'] += $row['honor_std'];
    $total_honor += $row['honor_std'];
}

// Hitung pajak dan potongan
$perhitungan = hitungHonorStaff($total_honor);

// Include header
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Dashboard Staff</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                <div class="breadcrumb-item">Honor Staff</div>
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="summary-card p-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="text-white">Halo, <?= htmlspecialchars($user['nama_user']) ?></h3>
                            <p class="text-white-50 mb-0">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Semester Aktif: <?= $semester_aktif ?>
                            </p>
                            <p class="text-white-50 mt-2 mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                Menampilkan total honor dari semua transaksi
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1 honor-card">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Honor Mengajar</h4>
                        </div>
                        <div class="card-body">
                            <?= formatRupiah($detail_per_jenis['mengajar']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1 honor-card">
                    <div class="card-icon bg-success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Honor PA/TA</h4>
                        </div>
                        <div class="card-body">
                            <?= formatRupiah($detail_per_jenis['pa_ta']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1 honor-card">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Honor Ujian</h4>
                        </div>
                        <div class="card-body">
                            <?= formatRupiah($detail_per_jenis['ujian']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Honor Card dengan Perhitungan Lengkap -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="text-white mb-0">RINCIAN HONOR SEMESTER <?= $semester_aktif ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <h6 class="text-muted">Total Honor</h6>
                                    <h4 class="font-weight-bold"><?= formatRupiah($perhitungan['nominal']) ?></h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded bg-danger text-white">
                                    <h6 class="text-white">Pajak (5%)</h6>
                                    <h4 class="font-weight-bold"><?= formatRupiah($perhitungan['pajak']) ?></h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded bg-warning text-white">
                                    <h6 class="text-white">Potongan (5%)</h6>
                                    <h4 class="font-weight-bold"><?= formatRupiah($perhitungan['potongan']) ?></h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded bg-success text-white">
                                    <h6 class="text-white">Honor Bersih</h6>
                                    <h4 class="font-weight-bold"><?= formatRupiah($perhitungan['bersih']) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Perhitungan:</strong> 
                            <?= formatRupiah($perhitungan['nominal']) ?> - Pajak 5% (<?= formatRupiah($perhitungan['pajak']) ?>) = 
                            <?= formatRupiah($perhitungan['sisa']) ?> - Potongan 5% (<?= formatRupiah($perhitungan['potongan']) ?>) = 
                            <strong><?= formatRupiah($perhitungan['bersih']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<?php
include 'includes/footer.php';
?>