<?php
session_start();
require_once '../config.php';
require_once 'includes/function_helper.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$semester_aktif = '20262';

// Ambil daftar semester untuk filter
$query_semester = "SELECT DISTINCT semester FROM t_transaksi_honor_dosen 
                   UNION SELECT DISTINCT semester FROM t_transaksi_pa_ta 
                   UNION SELECT DISTINCT semester FROM t_transaksi_ujian 
                   ORDER BY semester DESC";
$result_semester = mysqli_query($koneksi, $query_semester);

$bulan_list = [
    'januari', 'februari', 'maret', 'april', 'mei', 'juni',
    'juli', 'agustus', 'september', 'oktober', 'november', 'desember'
];

// Filter
$selected_semester = $_POST['semester'] ?? $semester_aktif;
$selected_bulan = $_POST['bulan'] ?? 'maret';

$honor_mengajar = [];
$honor_pa_ta = [];
$honor_ujian = [];
$total_all = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tampilkan'])) {
    
    // Ambil data user
    $query_user = "SELECT * FROM t_user WHERE id_user = ?";
    $stmt = mysqli_prepare($koneksi, $query_user);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result_user = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result_user);
    
    // Honor Mengajar
    $query_mengajar = "SELECT thd.*, jdwl.kode_matkul, jdwl.nama_matkul, thd.sks_tempuh, 
                              COALESCE(usr.honor_persks, 50000) as honor_persks 
                       FROM t_transaksi_honor_dosen thd
                       JOIN t_jadwal jdwl ON thd.id_jadwal = jdwl.id_jdwl
                       JOIN t_user usr ON jdwl.id_user = usr.id_user
                       WHERE jdwl.id_user = ? AND thd.bulan = ? AND thd.semester = ?";
    
    $stmt = mysqli_prepare($koneksi, $query_mengajar);
    mysqli_stmt_bind_param($stmt, "iss", $id_user, $selected_bulan, $selected_semester);
    mysqli_stmt_execute($stmt);
    $result_mengajar = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result_mengajar)) {
        $honor = $row['jml_tm'] * $row['sks_tempuh'] * $row['honor_persks'];
        $honor_mengajar[] = [
            'matkul' => $row['nama_matkul'],
            'sks' => $row['sks_tempuh'],
            'jml_tm' => $row['jml_tm'],
            'honor' => $honor
        ];
        $total_all += $honor;
    }
    
    // Honor PA/TA
    $query_pa_ta = "SELECT tpt.*, pnt.jbtn_pnt, pnt.honor_std 
                    FROM t_transaksi_pa_ta tpt
                    JOIN t_panitia pnt ON tpt.id_panitia = pnt.id_pnt
                    WHERE tpt.id_user = ? AND tpt.periode_wisuda = ? AND tpt.semester = ?";
    
    $stmt = mysqli_prepare($koneksi, $query_pa_ta);
    mysqli_stmt_bind_param($stmt, "iss", $id_user, $selected_bulan, $selected_semester);
    mysqli_stmt_execute($stmt);
    $result_pa_ta = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result_pa_ta)) {
        $honor_pa_ta[] = [
            'jabatan' => $row['jbtn_pnt'],
            'honor' => $row['honor_std']
        ];
        $total_all += $row['honor_std'];
    }
    
    // Honor Ujian
    $query_ujian = "SELECT tu.*, pnt.jbtn_pnt, pnt.honor_std 
                    FROM t_transaksi_ujian tu
                    JOIN t_panitia pnt ON tu.id_panitia = pnt.id_pnt
                    WHERE tu.id_user = ? AND tu.semester = ?";
    
    $stmt = mysqli_prepare($koneksi, $query_ujian);
    mysqli_stmt_bind_param($stmt, "is", $id_user, $selected_semester);
    mysqli_stmt_execute($stmt);
    $result_ujian = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result_ujian)) {
        $honor_ujian[] = [
            'jabatan' => $row['jbtn_pnt'],
            'honor' => $row['honor_std']
        ];
        $total_all += $row['honor_std'];
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Slip Honor</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Slip Honor</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Semester</label>
                            <select name="semester" class="form-control select2">
                                <?php while($row = mysqli_fetch_assoc($result_semester)): ?>
                                <option value="<?= $row['semester'] ?>" 
                                    <?= $selected_semester == $row['semester'] ? 'selected' : '' ?>>
                                    <?= $row['semester'] ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Bulan</label>
                            <select name="bulan" class="form-control select2">
                                <?php foreach($bulan_list as $bulan): ?>
                                <option value="<?= $bulan ?>" 
                                    <?= $selected_bulan == $bulan ? 'selected' : '' ?>>
                                    <?= ucfirst($bulan) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="tampilkan" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-2"></i>Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tampilkan'])): ?>

        <!-- Slip Honor Card -->
        <div class="card">
            <div class="card-header">
                <h4>SLIP HONOR - <?= strtoupper($selected_bulan) ?> <?= $selected_semester ?></h4>
                <div class="card-header-action">
                    <a href="cetak_pdf.php?semester=<?= $selected_semester ?>&bulan=<?= $selected_bulan ?>" 
                       class="btn btn-pdf" target="_blank">
                        <i class="fas fa-file-pdf mr-2"></i>Cetak PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                
                <!-- Info Dosen -->
                <div class="info-profile mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="120">Nama</td>
                                    <td width="10">:</td>
                                    <td class="font-weight-bold"><?= htmlspecialchars($user['nama_user'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <td>NPP</td>
                                    <td>:</td>
                                    <td><?= htmlspecialchars($user['npp_user'] ?? '') ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="120">Semester</td>
                                    <td width="10">:</td>
                                    <td><?= $selected_semester ?></td>
                                </tr>
                                <tr>
                                    <td>Bulan</td>
                                    <td>:</td>
                                    <td class="font-weight-bold"><?= ucfirst($selected_bulan) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Honor Mengajar -->
                <?php if (!empty($honor_mengajar)): ?>
                <div class="honor-section">
                    <h5>HONOR MENGAJAR</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mata Kuliah</th>
                                    <th width="80" class="text-center">SKS</th>
                                    <th width="150" class="text-center">Jml Tatap Muka</th>
                                    <th width="200" class="text-right">Honor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($honor_mengajar as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['matkul']) ?></td>
                                    <td class="text-center"><?= $item['sks'] ?></td>
                                    <td class="text-center"><?= $item['jml_tm'] ?>x</td>
                                    <td class="text-right font-weight-bold">
                                        <?= 'Rp ' . number_format($item['honor'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Honor PA/TA -->
                <?php if (!empty($honor_pa_ta)): ?>
                <div class="honor-section">
                    <h5>HONOR PA / TA</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Jabatan</th>
                                    <th width="200" class="text-right">Honor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($honor_pa_ta as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['jabatan']) ?></td>
                                    <td class="text-right font-weight-bold">
                                        <?= 'Rp ' . number_format($item['honor'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Honor Ujian -->
                <?php if (!empty($honor_ujian)): ?>
                <div class="honor-section">
                    <h5>HONOR UJIAN</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Jabatan</th>
                                    <th width="200" class="text-right">Honor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($honor_ujian as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['jabatan']) ?></td>
                                    <td class="text-right font-weight-bold">
                                        <?= 'Rp ' . number_format($item['honor'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ringkasan Pajak -->
                <?php
                $pajak = $total_all * 0.05;
                $sisa = $total_all - $pajak;
                $potongan = $sisa * 0.05;
                $bersih = $sisa - $potongan;
                ?>
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Honor</h6>
                                <h4 class="font-weight-bold"><?= formatRupiah($total_all) ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h6 class="text-white">Pajak (5%)</h6>
                                <h4 class="font-weight-bold"><?= formatRupiah($pajak) ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h6 class="text-white">Potongan (5%)</h6>
                                <h4 class="font-weight-bold"><?= formatRupiah($potongan) ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h6 class="text-white">Honor Bersih</h6>
                                <h4 class="font-weight-bold"><?= formatRupiah($bersih) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Detail Perhitungan:</strong><br>
                    <?= formatRupiah($total_all) ?> - Pajak 5% (<?= formatRupiah($pajak) ?>) = <?= formatRupiah($sisa) ?><br>
                    <?= formatRupiah($sisa) ?> - Potongan 5% (<?= formatRupiah($potongan) ?>) = <strong><?= formatRupiah($bersih) ?></strong>
                </div>

            </div>
        </div>

        <?php endif; ?>

    </section>
</div>

<?php
include 'includes/footer.php';
?>