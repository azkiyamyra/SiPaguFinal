<?php
session_start();
require_once '../config.php';
require_once 'includes/function_helper.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Ambil riwayat honor dari semua tabel (tanpa filter status)
$riwayat = [];

// 1. Dari t_transaksi_honor_dosen
$query_mengajar = "
    SELECT 
        'Honor Mengajar' as sumber,
        thd.id_thd as id_transaksi,
        thd.semester,
        thd.bulan,
        jdwl.kode_matkul,
        jdwl.nama_matkul,
        thd.jml_tm,
        thd.sks_tempuh,
        COALESCE(u.honor_persks, 50000) as honor_persks,
        (thd.jml_tm * thd.sks_tempuh * COALESCE(u.honor_persks, 50000)) as nominal
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal jdwl ON thd.id_jadwal = jdwl.id_jdwl
    JOIN t_user u ON jdwl.id_user = u.id_user
    WHERE jdwl.id_user = ?
";

// 2. Dari t_transaksi_pa_ta
$query_pa_ta = "
    SELECT 
        'Honor PA/TA' as sumber,
        tpt.id_tpt as id_transaksi,
        tpt.semester,
        tpt.periode_wisuda as bulan,
        '' as kode_matkul,
        p.jbtn_pnt as nama_matkul,
        tpt.jml_mhs_bimbingan as jml_tm,
        1 as sks_tempuh,
        p.honor_std as honor_persks,
        p.honor_std as nominal
    FROM t_transaksi_pa_ta tpt
    JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
    WHERE tpt.id_user = ?
";

// 3. Dari t_transaksi_ujian
$query_ujian = "
    SELECT 
        'Honor Ujian' as sumber,
        tu.id_tu as id_transaksi,
        tu.semester,
        '' as bulan,
        '' as kode_matkul,
        p.jbtn_pnt as nama_matkul,
        (tu.jml_koreksi + tu.jml_pgws_pagi + tu.jml_pgws_sore + tu.jml_koor_pagi + tu.jml_koor_sore) as jml_tm,
        1 as sks_tempuh,
        p.honor_std as honor_persks,
        p.honor_std as nominal
    FROM t_transaksi_ujian tu
    JOIN t_panitia p ON tu.id_panitia = p.id_pnt
    WHERE tu.id_user = ?
";

// Gabungkan semua query dengan UNION
$query = "$query_mengajar UNION ALL $query_pa_ta UNION ALL $query_ujian ORDER BY semester DESC, bulan DESC";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "iii", $id_user, $id_user, $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    // Hitung perhitungan honor
    $perhitungan = hitungHonorStaff($row['nominal']);
    
    $riwayat[] = [
        'id_transaksi' => $row['id_transaksi'],
        'sumber' => $row['sumber'],
        'semester' => $row['semester'],
        'bulan' => $row['bulan'],
        'kegiatan' => $row['sumber'] == 'Honor Mengajar' ? $row['nama_matkul'] : $row['nama_matkul'],
        'jml' => $row['jml_tm'],
        'nominal' => $row['nominal'],
        'pajak' => $perhitungan['pajak'],
        'potongan' => $perhitungan['potongan'],
        'bersih' => $perhitungan['bersih']
    ];
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Riwayat Honor</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Riwayat Honor</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Riwayat Penerimaan Honor</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($riwayat)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped" id="table-1">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Semester</th>
                                        <th>Jenis</th>
                                        <th>Kegiatan</th>
                                        <th>Nominal</th>
                                        <th>Pajak</th>
                                        <th>Potongan</th>
                                        <th>Bersih</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($riwayat as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($item['semester'] ?? '') ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars($item['sumber']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($item['kegiatan']) ?></td>
                                        <td class="text-right"><?= formatRupiah($item['nominal']) ?></td>
                                        <td class="text-right text-danger"><?= formatRupiah($item['pajak']) ?></td>
                                        <td class="text-right text-warning"><?= formatRupiah($item['potongan']) ?></td>
                                        <td class="text-right font-weight-bold text-success"><?= formatRupiah($item['bersih']) ?></td>
                                        <td>
                                            <a href="detail_slip.php?jenis=<?= urlencode($item['sumber']) ?>&id=<?= $item['id_transaksi'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada riwayat honor</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
include 'includes/footer.php';
?>