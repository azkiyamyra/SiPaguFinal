<?php
// =====================================================
// ADMIN DASHBOARD - SiPagu Universitas Dian Nuswantoro
// =====================================================
require_once __DIR__ . '/../config.php';

// ── Helper: format semester YYYYS → label ─────────────────────────────────
function formatSemesterLabel($semester) {
    if (!preg_match('/^\d{5}$/', $semester)) return $semester;
    $tahun = substr($semester, 0, 4);
    $kode  = substr($semester, -1);
    return $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
}

// ── Stat Cards ─────────────────────────────────────────────────────────────
$query_user      = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_user");
$total_user      = (int) mysqli_fetch_assoc($query_user)['total'];

$query_transaksi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_ujian");
$total_transaksi = (int) mysqli_fetch_assoc($query_transaksi)['total'];

$query_panitia   = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_panitia");
$total_panitia   = (int) mysqli_fetch_assoc($query_panitia)['total'];

$query_jadwal    = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_jadwal");
$total_jadwal    = (int) mysqli_fetch_assoc($query_jadwal)['total'];

// ── Semester Aktif (terbaru dari semua tabel transaksi) ────────────────────
$q_sem = mysqli_query($koneksi, "
    SELECT semester FROM (
        SELECT semester FROM t_transaksi_ujian
        UNION ALL SELECT semester FROM t_transaksi_honor_dosen
        UNION ALL SELECT semester FROM t_transaksi_pa_ta
    ) s ORDER BY semester DESC LIMIT 1
");
$semester_aktif = ($r = mysqli_fetch_assoc($q_sem)) ? $r['semester'] : '-';
$sem_label      = formatSemesterLabel($semester_aktif);

// ── Transaksi semester aktif ───────────────────────────────────────────────
$sem_esc     = mysqli_real_escape_string($koneksi, $semester_aktif);
$q_trx_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM t_transaksi_ujian WHERE semester='$sem_esc'");
$trx_aktif   = (int) mysqli_fetch_assoc($q_trx_aktif)['c'];
$pct_semester = ($total_transaksi > 0) ? round($trx_aktif / $total_transaksi * 100) : 0;

// ── Recent Transaksi Ujian (5 terbaru) ────────────────────────────────────
$q_activity = mysqli_query($koneksi, "
    SELECT tu.id_tu, tu.semester, tu.jml_mhs, tu.jml_matkul,
           u.nama_user, p.jbtn_pnt
    FROM t_transaksi_ujian tu
    LEFT JOIN t_user    u ON tu.id_user    = u.id_user
    LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
    ORDER BY tu.id_tu DESC LIMIT 5
");

// ── Recent Honor Dosen (5 terbaru) ────────────────────────────────────────
$q_honor = mysqli_query($koneksi, "
    SELECT thd.semester, thd.bulan, thd.jml_tm, thd.sks_tempuh,
           u.nama_user, j.nama_matkul
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user   u ON j.id_user     = u.id_user
    ORDER BY thd.id_thd DESC LIMIT 5
");

// ── Row counts semua tabel ─────────────────────────────────────────────────
$tbl_info = [
    't_user'                  => ['label' => 'User',           'color' => 'primary'],
    't_transaksi_ujian'       => ['label' => 'Transaksi Ujian','color' => 'success'],
    't_panitia'               => ['label' => 'Panitia',        'color' => 'warning'],
    't_jadwal'                => ['label' => 'Jadwal',         'color' => 'info'],
    't_transaksi_honor_dosen' => ['label' => 'Honor Dosen',    'color' => 'secondary'],
    't_transaksi_pa_ta'       => ['label' => 'PA/TA',          'color' => 'danger'],
];
$tbl_counts = [];
foreach ($tbl_info as $tbl => $meta) {
    $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM `$tbl`"));
    $tbl_counts[$tbl] = (int)$r['c'];
}
$total_rows = array_sum($tbl_counts);

// ── Distribusi Role User ───────────────────────────────────────────────────
$q_role = mysqli_query($koneksi, "SELECT role_user, COUNT(*) as c FROM t_user GROUP BY role_user");
$role_dist = [];
while ($r = mysqli_fetch_assoc($q_role)) $role_dist[$r['role_user']] = (int)$r['c'];

// ── Statistik Transaksi Ujian per Semester (untuk chart) ──────────────────
$q_sem_stat = mysqli_query($koneksi, "
    SELECT semester, COUNT(*) AS total_trx, SUM(jml_mhs) AS total_mhs
    FROM t_transaksi_ujian
    GROUP BY semester
    ORDER BY semester ASC
");
$chart_semesters = $chart_trx = $chart_mhs = [];
while ($r = mysqli_fetch_assoc($q_sem_stat)) {
    $chart_semesters[] = formatSemesterLabel($r['semester']);
    $chart_trx[]       = (int)$r['total_trx'];
    $chart_mhs[]       = (int)$r['total_mhs'];
}

// ── Top 5 Dosen by Total SKS ───────────────────────────────────────────────
$q_top = mysqli_query($koneksi, "
    SELECT u.nama_user,
           COUNT(thd.id_thd)   AS jml_trx,
           SUM(thd.sks_tempuh) AS total_sks
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user   u ON j.id_user     = u.id_user
    GROUP BY j.id_user
    ORDER BY total_sks DESC
    LIMIT 5
");

// ── Quick Actions (PERSIS SAMA DENGAN FILE ASLINYA) ───────────────────────
$quick_actions = [
    ['title' => 'Upload User',    'url' => 'upload_user.php',   'icon' => 'fa-users',               'color' => 'primary'],
    ['title' => 'Transaksi Ujian','url' => 'upload_tu.php',     'icon' => 'fa-file-invoice-dollar',  'color' => 'success'],
    ['title' => 'Panitia PA/TA',  'url' => 'upload_tpata.php',  'icon' => 'fa-user-tie',             'color' => 'warning'],
    ['title' => 'Data Panitia',   'url' => 'upload_panitia.php','icon' => 'fa-clipboard-list',       'color' => 'danger'],
    ['title' => 'Jadwal',         'url' => 'upload_thd.php',    'icon' => 'fa-calendar-alt',         'color' => 'info'],
    ['title' => 'Jadwal Lain',    'url' => 'upload_jadwal.php', 'icon' => 'fa-clock',                'color' => 'secondary'],
];
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Custom CSS - dimuat setelah header untuk override Stisla jika perlu -->
<link rel="stylesheet" href="<?= ASSETS_URL ?>css/custom.css">

<?php include __DIR__ . '/includes/navbar.php'; ?>
<?php include __DIR__ . '/includes/sidebar_koordinator.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">

        <!-- ── Header ──────────────────────────────────────────── -->
        <div class="section-header pt-4 pb-0">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h1 class="h3 font-weight-normal text-dark mb-1">Dashboard Koordinator</h1>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Koordinator'); ?></p>
                </div>
                <div class="text-muted small"><?php echo date('l, d F Y'); ?></div>
            </div>
        </div>

        <div class="section-body">

            <!-- ══════════════════════════════════════
                 STAT CARDS
                 ══════════════════════════════════════ -->
            <div class="row mb-5">
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-icon bg-primary-soft text-primary"><i class="fas fa-users"></i></div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo number_format($total_user); ?></h3>
                            <p class="stat-label">Total User</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-success">
                        <div class="stat-icon bg-success-soft text-success"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo number_format($total_transaksi); ?></h3>
                            <p class="stat-label">Transaksi Ujian</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-icon bg-warning-soft text-warning"><i class="fas fa-user-tie"></i></div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo number_format($total_panitia); ?></h3>
                            <p class="stat-label">Panitia PA/TA</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-info">
                        <div class="stat-icon bg-info-soft text-info"><i class="fas fa-calendar-alt"></i></div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo htmlspecialchars($semester_aktif); ?></h3>
                            <p class="stat-label">Semester Aktif</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 QUICK ACTIONS  (persis sama dengan file asli)
                 ══════════════════════════════════════ -->
            <div class="row">
                <div class="col-12 mb-5">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h4 class="h5 font-weight-normal text-dark mb-0">Quick Actions</h4>
                    </div>
                    <div class="row">
                        <?php foreach ($quick_actions as $action): ?>
                        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>koordinator/<?php echo $action['url']; ?>"
                               class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas <?php echo $action['icon']; ?> fa-2x text-<?php echo $action['color']; ?>"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark"><?php echo $action['title']; ?></h6>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 RECENT ACTIVITY  +  SYSTEM STATUS
                 ══════════════════════════════════════ -->
            <div class="row">

                <!-- Recent Transaksi Ujian (data real) -->
                <div class="col-lg-8 mb-4">
                    <div class="content-card content-card-primary">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">Recent Activity — Transaksi Ujian</h5>
                            <a href="<?= BASE_URL ?>koordinator/master_data/data_tu.php" class="text-primary small">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="activity-list">
                                <?php if (mysqli_num_rows($q_activity) === 0): ?>
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <p class="mb-0 text-muted">Belum ada data transaksi ujian.</p>
                                    </div>
                                </div>
                                <?php else: ?>
                                <?php while ($row = mysqli_fetch_assoc($q_activity)): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-file-invoice-dollar text-success"></i>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <p class="mb-1">
                                            <strong><?php echo htmlspecialchars($row['nama_user'] ?? '-'); ?></strong>
                                            &mdash; Sem <strong><?php echo htmlspecialchars($row['semester']); ?></strong>
                                            <span class="text-muted">&nbsp;·&nbsp; <?php echo (int)$row['jml_mhs']; ?> Mhs &nbsp;·&nbsp; <?php echo (int)$row['jml_matkul']; ?> Matkul</span>
                                        </p>
                                        <small class="text-muted">Jabatan: <?php echo htmlspecialchars($row['jbtn_pnt'] ?? '-'); ?> &nbsp;#TU-<?php echo $row['id_tu']; ?></small>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Status (row counts real) -->
                <div class="col-lg-4 mb-4">
                    <div class="content-card content-card-info">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">System Status</h5>
                            <i class="fas fa-server text-info"></i>
                        </div>
                        <div class="card-body">
                            <?php foreach ($tbl_info as $tbl => $meta):
                                $cnt = $tbl_counts[$tbl];
                                $pct = ($total_rows > 0) ? round($cnt / $total_rows * 100) : 0;
                            ?>
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo $meta['label']; ?></span>
                                    <span class="badge badge-<?php echo $meta['color']; ?>"><?php echo number_format($cnt); ?></span>
                                </div>
                                <div class="progress-bar-thin">
                                    <div class="progress-fill bg-<?php echo $meta['color']; ?>" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="info-item mt-3 pt-2 border-top">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Role</span>
                                    <span class="font-weight-medium text-primary"><?php echo ucfirst($_SESSION['role'] ?? 'Koordinatoristrator'); ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Last Updated</span>
                                    <span class="font-weight-medium"><?php echo date('d M Y H:i'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 CHART per SEMESTER  +  DISTRIBUSI ROLE
                 ══════════════════════════════════════ -->
            <div class="row mt-2">

                <!-- Chart Transaksi Ujian per Semester -->
                <div class="col-lg-8 mb-4">
                    <div class="content-card content-card-success">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">Transaksi Ujian per Semester</h5>
                            <i class="fas fa-chart-bar text-success"></i>
                        </div>
                        <div class="card-body">
                            <?php if (empty($chart_semesters)): ?>
                            <p class="text-muted text-center py-3">Belum ada data transaksi ujian.</p>
                            <?php else: ?>
                            <canvas id="chartSemester" height="120"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Distribusi Role User -->
                <div class="col-lg-4 mb-4">
                    <div class="content-card content-card-primary">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">Distribusi Role User</h5>
                            <i class="fas fa-chart-pie text-primary"></i>
                        </div>
                        <div class="card-body">
                            <?php foreach (['staff' => 'primary','koordinator' => 'success','koordinator' => 'warning'] as $role => $col):
                                $cnt = $role_dist[$role] ?? 0;
                                $pct = ($total_user > 0) ? round($cnt / $total_user * 100) : 0;
                            ?>
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted text-capitalize"><?php echo ucfirst($role); ?></span>
                                    <span class="badge badge-<?php echo $col; ?>"><?php echo $cnt; ?> (<?php echo $pct; ?>%)</span>
                                </div>
                                <div class="progress-bar-thin">
                                    <div class="progress-fill bg-<?php echo $col; ?>" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mt-2">
                                <span class="text-muted small">Total Jadwal</span>
                                <span class="font-weight-medium"><?php echo number_format($total_jadwal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted small">Honor Dosen</span>
                                <span class="font-weight-medium"><?php echo number_format($tbl_counts['t_transaksi_honor_dosen']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted small">PA/TA</span>
                                <span class="font-weight-medium"><?php echo number_format($tbl_counts['t_transaksi_pa_ta']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 RECENT HONOR DOSEN  +  TOP DOSEN SKS
                 ══════════════════════════════════════ -->
            <div class="row mt-2">

                <div class="col-lg-7 mb-4">
                    <div class="content-card content-card-info">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">Transaksi Honor Dosen Terbaru</h5>
                            <a href="<?= BASE_URL ?>koordinator/master_data/data_thd.php" class="text-primary small">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="activity-list">
                                <?php if (mysqli_num_rows($q_honor) === 0): ?>
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <p class="mb-0 text-muted">Belum ada data honor dosen.</p>
                                    </div>
                                </div>
                                <?php else: ?>
                                <?php while ($row = mysqli_fetch_assoc($q_honor)): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-chalkboard-teacher text-info"></i>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <p class="mb-1">
                                            <strong><?php echo htmlspecialchars($row['nama_user'] ?? '-'); ?></strong>
                                            &mdash; <?php echo htmlspecialchars($row['nama_matkul'] ?? '-'); ?>
                                        </p>
                                        <small class="text-muted">
                                            Sem <?php echo htmlspecialchars($row['semester'] ?? '-'); ?>
                                            &nbsp;·&nbsp; <?php echo ucfirst($row['bulan']); ?>
                                            &nbsp;·&nbsp; <?php echo (int)$row['jml_tm']; ?> TM, <?php echo (int)$row['sks_tempuh']; ?> SKS
                                        </small>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="content-card content-card-success">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">Top Dosen — Total SKS</h5>
                            <i class="fas fa-trophy text-warning"></i>
                        </div>
                        <div class="card-body">
                            <?php
                            $badge_colors = ['warning','secondary','danger','info','primary'];
                            $rank = 1; $has_top = false;
                            while ($row = mysqli_fetch_assoc($q_top)):
                                $has_top = true;
                                $bc = $badge_colors[$rank - 1] ?? 'secondary';
                            ?>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <span class="badge badge-<?php echo $bc; ?> mr-2">#<?php echo $rank++; ?></span>
                                    <span class="small"><?php echo htmlspecialchars($row['nama_user'] ?? '-'); ?></span>
                                </div>
                                <div class="text-right">
                                    <span class="font-weight-medium text-success"><?php echo (int)$row['total_sks']; ?> SKS</span><br>
                                    <small class="text-muted"><?php echo (int)$row['jml_trx']; ?> trx</small>
                                </div>
                            </div>
                            <?php endwhile;
                            if (!$has_top): ?>
                            <p class="text-muted text-center py-2 small">Belum ada data honor dosen.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 SEMESTER INFO BANNER
                 ══════════════════════════════════════ -->
            <div class="row mt-2">
                <div class="col-12">
                    <div class="content-card content-card-success">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-1">Semester <?php echo htmlspecialchars($semester_aktif); ?></h5>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars($sem_label); ?></p>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="mr-4 mb-2">
                                            <div class="text-muted small">Transaksi Semester Ini</div>
                                            <div class="font-weight-medium"><?php echo number_format($trx_aktif); ?></div>
                                        </div>
                                        <div class="mr-4 mb-2">
                                            <div class="text-muted small">Total Semua Semester</div>
                                            <div class="font-weight-medium"><?php echo number_format($total_transaksi); ?></div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="text-muted small">Status</div>
                                            <span class="badge-success-light">Active</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <div class="d-flex align-items-center">
                                        <div class="progress-circular mr-4">
                                            <div class="progress-circle" style="--pct:<?php echo $pct_semester; ?>">
                                                <span class="progress-circle-value"><?php echo $pct_semester; ?>%</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-muted small mb-1">Porsi Semester Aktif</div>
                                            <div class="font-weight-medium"><?php echo $pct_semester >= 50 ? 'On Track' : 'Early Stage'; ?></div>
                                            <small class="text-muted"><?php echo $trx_aktif; ?> dari <?php echo $total_transaksi; ?> transaksi</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /section-body -->
    </section>
</div>
<!-- End Main Content -->

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer_scripts.php'; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
<?php if (!empty($chart_semesters)): ?>
// ── Chart: Transaksi Ujian per Semester ──────────────────────────────────
new Chart(document.getElementById('chartSemester').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_values($chart_semesters)); ?>,
        datasets: [
            {
                label: 'Transaksi',
                data: <?php echo json_encode(array_values($chart_trx)); ?>,
                backgroundColor: 'rgba(40,199,111,0.75)',
                borderRadius: 5,
                yAxisID: 'y',
            },
            {
                label: 'Total Mahasiswa',
                data: <?php echo json_encode(array_values($chart_mhs)); ?>,
                type: 'line',
                borderColor: '#667eea',
                backgroundColor: 'rgba(102,126,234,0.08)',
                tension: 0.35,
                fill: true,
                pointRadius: 4,
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 14, font: { size: 12 } } }
        },
        scales: {
            y:  { beginAtZero: true, title: { display: true, text: 'Transaksi' }, ticks: { stepSize: 1 } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false },
                  title: { display: true, text: 'Mahasiswa' } }
        }
    }
});
<?php endif; ?>

// ── UI animations ──────────────────────────────────────────────────────────
$(document).ready(function () {
    $('.stat-card').each(function (i) {
        $(this).css({ opacity: 0, transform: 'translateY(10px)' });
        setTimeout(() => $(this).animate({ opacity: 1 }, 300), i * 100);
    });
    $('.content-card').each(function (i) {
        $(this).css({ opacity: 0 });
        setTimeout(() => $(this).animate({ opacity: 1 }, 400), 300 + i * 120);
    });
    $('.action-card').on('mouseenter', function () {
        $(this).addClass('active');
    }).on('mouseleave', function () {
        $(this).removeClass('active');
    });
    $('.activity-item').on('mouseenter', function () {
        $(this).css('transform', 'translateX(2px)');
    }).on('mouseleave', function () {
        $(this).css('transform', 'translateX(0)');
    });
    // Refresh info-item click
    $('.info-item').click(function () {
        var $fill = $(this).find('.progress-fill');
        if ($fill.length) {
            var w = $fill.width() / $(this).find('.progress-bar-thin').width() * 100;
            $fill.css('width', '0%');
            setTimeout(() => $fill.css('width', w + '%'), 300);
        }
    });
});
</script>