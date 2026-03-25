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

// Hitung total honor bulan ini (SEMUA TRANSAKSI)
$bulan = strtolower(date('F'));
$bulan_indonesia = [
    'january' => 'januari', 'february' => 'februari', 'march' => 'maret',
    'april' => 'april', 'may' => 'mei', 'june' => 'juni',
    'july' => 'juli', 'august' => 'agustus', 'september' => 'september',
    'october' => 'oktober', 'november' => 'november', 'december' => 'desember'
];
$bulan_skrg = $bulan_indonesia[$bulan] ?? 'maret';

// Query untuk mendapatkan total honor (tanpa filter status)
$query_sum = "
    SELECT 
        COALESCE(SUM(
            CASE WHEN tu.id_transaksi IS NOT NULL 
                THEN (SELECT honor_std FROM t_panitia WHERE id_pnt = tu.id_panitia) 
                ELSE 0 
            END
        ), 0) as total_ujian,
        COALESCE(SUM(
            CASE WHEN tpt.id_transaksi IS NOT NULL 
                THEN (SELECT honor_std FROM t_panitia WHERE id_pnt = tpt.id_panitia) 
                ELSE 0 
            END
        ), 0) as total_pa_ta,
        COALESCE(SUM(
            CASE WHEN thd.id_transaksi IS NOT NULL 
                THEN thd.jml_kegiatan * (SELECT honor_std FROM t_panitia WHERE id_pnt = thd.id_panitia)
                ELSE 0 
            END
        ), 0) as total_mengajar
    FROM t_user u
    LEFT JOIN t_transaksi_ujian tu ON u.id_user = tu.id_user AND tu.semester = ? AND tu.periode = ?
    LEFT JOIN t_transaksi_pa_ta tpt ON u.id_user = tpt.id_user AND tpt.semester = ? AND tpt.periode_wisuda = ?
    LEFT JOIN t_transaksi_honor_dosen thd ON u.id_user = thd.id_user AND thd.semester = ? AND thd.bulan = ?
    WHERE u.id_user = ?
";

$stmt = mysqli_prepare($koneksi, $query_sum);
mysqli_stmt_bind_param($stmt, "ssssssi", $semester_aktif, $bulan_skrg, $semester_aktif, $bulan_skrg, $semester_aktif, $bulan_skrg, $id_user);
mysqli_stmt_execute($stmt);
$result_sum = mysqli_stmt_get_result($stmt);
$sum_data = mysqli_fetch_assoc($result_sum);

$total_ujian = $sum_data['total_ujian'];
$total_pa_ta = $sum_data['total_pa_ta'];
$total_mengajar = $sum_data['total_mengajar'];
$total_honor = $total_ujian + $total_pa_ta + $total_mengajar;

// Hitung pajak dan potongan
$perhitungan = hitungHonorStaff($total_honor);

// Ambil statistik bulanan untuk chart (6 bulan terakhir) - tanpa filter status
$statistik_bulanan = [];
$query_statistik = "
    SELECT 
        thd.bulan as periode,
        'Honor Mengajar' as jenis,
        SUM(thd.jml_kegiatan * p.honor_std) as total
    FROM t_transaksi_honor_dosen thd
    JOIN t_panitia p ON thd.id_panitia = p.id_pnt
    WHERE thd.id_user = ?
    GROUP BY thd.bulan
    UNION ALL
    SELECT 
        tpt.periode_wisuda as periode,
        'Honor PA/TA' as jenis,
        SUM(p.honor_std) as total
    FROM t_transaksi_pa_ta tpt
    JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
    WHERE tpt.id_user = ?
    GROUP BY tpt.periode_wisuda
    UNION ALL
    SELECT 
        tu.periode as periode,
        'Honor Ujian' as jenis,
        SUM(p.honor_std) as total
    FROM t_transaksi_ujian tu
    JOIN t_panitia p ON tu.id_panitia = p.id_pnt
    WHERE tu.id_user = ?
    GROUP BY tu.periode
    ORDER BY periode DESC
    LIMIT 6
";

$stmt = mysqli_prepare($koneksi, $query_statistik);
mysqli_stmt_bind_param($stmt, "iii", $id_user, $id_user, $id_user);
mysqli_stmt_execute($stmt);
$result_stat = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result_stat)) {
    $statistik_bulanan[] = $row;
}

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
                                Semester Aktif: <?= $semester_aktif ?> | 
                                Bulan: <?= ucfirst($bulan_skrg) ?>
                            </p>
                            <p class="text-white-50 mt-2 mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                Menampilkan semua honor yang telah diinput
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
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Honor Ujian</h4>
                        </div>
                        <div class="card-body">
                            <?= formatRupiah($total_ujian) ?>
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
                            <?= formatRupiah($total_pa_ta) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1 honor-card">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Honor Mengajar</h4>
                        </div>
                        <div class="card-body">
                            <?= formatRupiah($total_mengajar) ?>
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
                        <h4 class="text-white mb-0">RINCIAN HONOR BULAN <?= strtoupper($bulan_skrg) ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <h6 class="text-muted">Nominal Honor</h6>
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
                            <div class="row">
                                <div class="col-md-6">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Perhitungan:</strong><br>
                                    Nominal: <?= formatRupiah($perhitungan['nominal']) ?><br>
                                    Pajak 5%: <?= formatRupiah($perhitungan['pajak']) ?> = <?= formatRupiah($perhitungan['sisa']) ?> (sisa)
                                </div>
                                <div class="col-md-6">
                                    Sisa: <?= formatRupiah($perhitungan['sisa']) ?><br>
                                    Potongan 5%: <?= formatRupiah($perhitungan['potongan']) ?><br>
                                    <strong>Total Bersih: <?= formatRupiah($perhitungan['bersih']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik Bulanan -->
        <?php if (!empty($statistik_bulanan)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Statistik Honor 6 Bulan Terakhir</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="honorChart" height="150"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($statistik_bulanan)): ?>
// Chart.js untuk statistik
var ctx = document.getElementById('honorChart').getContext('2d');
var chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($statistik_bulanan, 'periode')) ?>,
        datasets: [{
            label: 'Total Honor',
            data: <?= json_encode(array_column($statistik_bulanan, 'total')) ?>,
            backgroundColor: '#6777ef',
            borderColor: '#6777ef',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Rp ' + context.raw.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php
// Include footer
include 'includes/footer.php';
?>