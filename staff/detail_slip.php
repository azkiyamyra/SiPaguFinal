<?php
session_start();
require_once '../config.php';
require_once 'includes/function_helper.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$jenis = $_GET['jenis'] ?? '';
$id = $_GET['id'] ?? 0;

if (empty($jenis) || empty($id)) {
    header("Location: riwayat_honor.php");
    exit();
}

// Ambil data user
$query_user = "SELECT * FROM t_user WHERE id_user = ?";
$stmt = mysqli_prepare($koneksi, $query_user);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

$detail = [];
$rincian = [];

// Query berdasarkan jenis
if ($jenis == 'Honor Mengajar') {
    $query = "
        SELECT 
            thd.*,
            jdwl.kode_matkul,
            jdwl.nama_matkul,
            jdwl.jml_mhs,
            COALESCE(u.honor_persks, 50000) as honor_persks,
            (thd.jml_tm * thd.sks_tempuh * COALESCE(u.honor_persks, 50000)) as nominal
        FROM t_transaksi_honor_dosen thd
        JOIN t_jadwal jdwl ON thd.id_jadwal = jdwl.id_jdwl
        JOIN t_user u ON jdwl.id_user = u.id_user
        WHERE thd.id_thd = ? AND jdwl.id_user = ?
    ";
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $detail = mysqli_fetch_assoc($result);
    
    if ($detail) {
        $rincian = [
            'Mata Kuliah' => $detail['nama_matkul'],
            'Kode MK' => $detail['kode_matkul'],
            'SKS' => $detail['sks_tempuh'],
            'Jumlah Tatap Muka' => $detail['jml_tm'] . 'x',
            'Honor per SKS' => formatRupiah($detail['honor_persks']),
            'Total Mahasiswa' => $detail['jml_mhs'] . ' orang'
        ];
    }
    
} elseif ($jenis == 'Honor PA/TA') {
    $query = "
        SELECT 
            tpt.*,
            p.jbtn_pnt,
            p.honor_std as nominal
        FROM t_transaksi_pa_ta tpt
        JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
        WHERE tpt.id_tpt = ? AND tpt.id_user = ?
    ";
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $detail = mysqli_fetch_assoc($result);
    
    if ($detail) {
        $rincian = [
            'Jabatan' => $detail['jbtn_pnt'],
            'Prodi' => $detail['prodi'],
            'Jumlah Mahasiswa Prodi' => $detail['jml_mhs_prodi'] . ' orang',
            'Jumlah Bimbingan' => $detail['jml_mhs_bimbingan'] . ' orang',
            'PGJI 1' => $detail['jml_pgji_1'] . ' orang',
            'PGJI 2' => $detail['jml_pgji_2'] . ' orang',
            'Ketua PGJI' => $detail['ketua_pgji']
        ];
    }
    
} elseif ($jenis == 'Honor Ujian') {
    $query = "
        SELECT 
            tu.*,
            p.jbtn_pnt,
            p.honor_std as nominal
        FROM t_transaksi_ujian tu
        JOIN t_panitia p ON tu.id_panitia = p.id_pnt
        WHERE tu.id_tu = ? AND tu.id_user = ?
    ";
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $detail = mysqli_fetch_assoc($result);
    
    if ($detail) {
        $rincian = [
            'Jabatan' => $detail['jbtn_pnt'],
            'Jumlah Mahasiswa Prodi' => $detail['jml_mhs_prodi'] . ' orang',
            'Jumlah Mahasiswa' => $detail['jml_mhs'] . ' orang',
            'Jumlah Koreksi' => $detail['jml_koreksi'] . 'x',
            'Jumlah Matkul' => $detail['jml_matkul'],
            'Pengawas Pagi' => $detail['jml_pgws_pagi'] . 'x',
            'Pengawas Sore' => $detail['jml_pgws_sore'] . 'x',
            'Koordinator Pagi' => $detail['jml_koor_pagi'] . 'x',
            'Koordinator Sore' => $detail['jml_koor_sore'] . 'x'
        ];
    }
}

if (!$detail) {
    header("Location: riwayat_honor.php");
    exit();
}

// Hitung honor
$perhitungan = hitungHonorStaff($detail['nominal']);

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Detail Slip Honor</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="index.php">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="riwayat_honor.php">Riwayat Honor</a></div>
                <div class="breadcrumb-item">Detail Slip</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>SLIP HONOR - <?= $jenis ?></h4>
                <div class="card-header-action">
                    <a href="cetak_slip.php?jenis=<?= urlencode($jenis) ?>&id=<?= $id ?>" 
                       class="btn btn-pdf" target="_blank">
                        <i class="fas fa-file-pdf mr-2"></i>Cetak PDF
                    </a>
                    <a href="riwayat_honor.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                
                <!-- Info Staff -->
                <div class="info-profile mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="120">Nama</td>
                                    <td width="10">:</td>
                                    <td class="font-weight-bold"><?= htmlspecialchars($user['nama_user']) ?></td>
                                </tr>
                                <tr>
                                    <td>NPP</td>
                                    <td>:</td>
                                    <td><?= htmlspecialchars($user['npp_user']) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="120">Semester</td>
                                    <td width="10">:</td>
                                    <td><?= $detail['semester'] ?></td>
                                </tr>
                                <?php if (isset($detail['bulan']) && $detail['bulan']): ?>
                                <tr>
                                    <td>Bulan</td>
                                    <td>:</td>
                                    <td><?= ucfirst($detail['bulan']) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Rincian Kegiatan -->
                <div class="honor-section">
                    <h5>RINCIAN KEGIATAN</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <?php foreach($rincian as $key => $value): ?>
                                <tr>
                                    <th width="250"><?= $key ?></th>
                                    <td><?= $value ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Perhitungan Honor -->
                <div class="honor-section">
                    <h5>PERHITUNGAN HONOR</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-muted">Nominal</h6>
                                <h5 class="font-weight-bold"><?= formatRupiah($perhitungan['nominal']) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-danger text-white">
                                <h6 class="text-white">Pajak (5%)</h6>
                                <h5 class="font-weight-bold"><?= formatRupiah($perhitungan['pajak']) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-warning text-white">
                                <h6 class="text-white">Potongan (5%)</h6>
                                <h5 class="font-weight-bold"><?= formatRupiah($perhitungan['potongan']) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-success text-white">
                                <h6 class="text-white">Honor Bersih</h6>
                                <h5 class="font-weight-bold"><?= formatRupiah($perhitungan['bersih']) ?></h5>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Detail Perhitungan:</strong><br>
                        <?= formatRupiah($perhitungan['nominal']) ?> - Pajak 5% (<?= formatRupiah($perhitungan['pajak']) ?>) = 
                        <?= formatRupiah($perhitungan['sisa']) ?><br>
                        <?= formatRupiah($perhitungan['sisa']) ?> - Potongan 5% (<?= formatRupiah($perhitungan['potongan']) ?>) = 
                        <strong><?= formatRupiah($perhitungan['bersih']) ?></strong>
                    </div>
                </div>

                <!-- Total -->
                <div class="total-box mt-4">
                    <div class="row">
                        <div class="col-6">
                            <h4 class="font-weight-bold">HONOR BERSIH DITERIMA</h4>
                        </div>
                        <div class="col-6 text-right">
                            <h2 class="font-weight-bold text-success"><?= formatRupiah($perhitungan['bersih']) ?></h2>
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