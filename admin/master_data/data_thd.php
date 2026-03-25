<?php
include '../../config.php';
$page_title = "Data Transaksi Honor Dosen";

function formatSemester($semester) {
    if (!preg_match('/^\d{4}[12]$/', $semester)) {
        return $semester;
    }
    $tahun = substr($semester, 0, 4);
    $kode  = substr($semester, -1);
    return $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
}

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total data
$total_data = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_honor_dosen");
$total = mysqli_fetch_assoc($total_data)['total'];
$total_pages = ceil($total / $limit);

// Proses Hapus Data
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $delete = mysqli_query($koneksi, "DELETE FROM t_transaksi_honor_dosen WHERE id_thd = '$id'");
    
    if ($delete) {
        $_SESSION['success_message'] = "Data honor dosen berhasil dihapus!";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
    header("Location: data_thd.php" . ($page > 1 ? "?page=$page" : ""));
    exit();
}

// Proses Tambah/Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $id_thd = mysqli_real_escape_string($koneksi, $_POST['id_thd'] ?? '');
        $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
        $bulan = mysqli_real_escape_string($koneksi, $_POST['bulan']);
        $id_jadwal = mysqli_real_escape_string($koneksi, $_POST['id_jadwal']);
        $jml_tm = mysqli_real_escape_string($koneksi, $_POST['jml_tm']);
        $sks_tempuh = mysqli_real_escape_string($koneksi, $_POST['sks_tempuh']);
        
        if ($_POST['action'] == 'add') {
            $query = "INSERT INTO t_transaksi_honor_dosen (semester, bulan, id_jadwal, jml_tm, sks_tempuh) 
                      VALUES ('$semester', '$bulan', '$id_jadwal', '$jml_tm', '$sks_tempuh')";
            $message = "Data honor dosen berhasil ditambahkan!";
        } elseif ($_POST['action'] == 'edit' && $id_thd) {
            $query = "UPDATE t_transaksi_honor_dosen SET 
                      semester = '$semester',
                      bulan = '$bulan',
                      id_jadwal = '$id_jadwal',
                      jml_tm = '$jml_tm',
                      sks_tempuh = '$sks_tempuh'
                      WHERE id_thd = '$id_thd'";
            $message = "Data honor dosen berhasil diupdate!";
        }
        
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = "Gagal: " . mysqli_error($koneksi);
        }
        header("Location: data_thd.php" . ($page > 1 ? "?page=$page" : ""));
        exit();
    }
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query_edit = mysqli_query($koneksi, "SELECT * FROM t_transaksi_honor_dosen WHERE id_thd = '$id'");
    $edit_data = mysqli_fetch_assoc($query_edit);
}

// Ambil data jadwal untuk dropdown
$jadwal_list = [];
$query_jadwal = mysqli_query($koneksi, "
    SELECT j.id_jdwl, j.kode_matkul, j.nama_matkul, u.nama_user 
    FROM t_jadwal j
    LEFT JOIN t_user u ON j.id_user = u.id_user
    ORDER BY j.semester DESC, j.kode_matkul ASC
");
while ($row = mysqli_fetch_assoc($query_jadwal)) {
    $jadwal_list[$row['id_jdwl']] = $row['kode_matkul'] . ' - ' . $row['nama_matkul'] . ' (' . ($row['nama_user'] ?? 'Tanpa Dosen') . ')';
}

// Daftar bulan
$bulan_list = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];

// Generate semester options
function generateSemesterOptionsTHD($selected = '') {
    $list = [];
    $currentYear = date('Y');
    for ($y = $currentYear - 2; $y <= $currentYear + 2; $y++) {
        $list[] = $y . '1';
        $list[] = $y . '2';
    }
    $options = '';
    foreach ($list as $semester) {
        $tahun = substr($semester, 0, 4);
        $kode = substr($semester, -1);
        $label = $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
        $selected_attr = ($selected == $semester) ? 'selected' : '';
        $options .= "<option value=\"$semester\" $selected_attr>$label</option>";
    }
    return $options;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-file-invoice-dollar mr-2"></i>Data Honor Dosen</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data</div>
                <div class="breadcrumb-item">Honor Dosen</div>
            </div>
        </div>

        <div class="section-body">
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="up-alert up-alert-success up-alert-dismissible">
                <div class="up-alert-icon"><i class="fas fa-check-circle"></i></div>
                <div class="up-alert-content"><?= $_SESSION['success_message'] ?></div>
                <button class="up-alert-close" onclick="this.closest('.up-alert').remove()"><span>×</span></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
            <div class="up-alert up-alert-danger up-alert-dismissible">
                <div class="up-alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div class="up-alert-content"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
                <button class="up-alert-close" onclick="this.closest('.up-alert').remove()"><span>×</span></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- STATS CARDS -->
            <?php
            $total_thd = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM t_transaksi_honor_dosen"));
            $total_sks = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(sks_tempuh) as total, SUM(jml_tm) as total_tm FROM t_transaksi_honor_dosen"));
            
            // Hitung total honor berdasarkan honor_persks dari t_user
            $total_honor_query = mysqli_query($koneksi, "
                SELECT SUM(thd.sks_tempuh * u.honor_persks) as total_honor
                FROM t_transaksi_honor_dosen thd
                INNER JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
                INNER JOIN t_user u ON j.id_user = u.id_user
            ");
            $total_honor = mysqli_fetch_assoc($total_honor_query);
            ?>
            <div class="up-stat-row">
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_thd ?></div>
                    <div class="up-stat-label">Total Transaksi</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_sks['total'] ?? 0 ?></div>
                    <div class="up-stat-label">Total SKS</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value">Rp <?= number_format($total_honor['total_honor'] ?? 0, 0, ',', '.') ?></div>
                    <div class="up-stat-label">Total Honor</div>
                </div>
            </div>

            <!-- MAIN CARD -->
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h5>Riwayat Honor Dosen</h5>
                    <div class="ml-auto d-flex align-items-center gap-2">
                        <div class="up-search-box" style="width: 250px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchInput" placeholder="Cari data..." onkeyup="filterTable()">
                        </div>
                        <button class="up-btn up-btn-success ml-2" onclick="openModal('add')">
                            <i class="fas fa-plus mr-1"></i> Tambah Data
                        </button>
                    </div>
                </div>
                <div class="up-card-body">
                    <div class="up-table-responsive">
                        <table class="up-table up-table-hover" id="dataTable">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>Semester</th>
                                    <th>Bulan</th>
                                    <th>Dosen</th>
                                    <th>Jadwal</th>
                                    <th>Jml TM</th>
                                    <th>SKS Tempuh</th>
                                    <th>Honor</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                $query_thd = mysqli_query($koneksi, "
                                    SELECT 
                                        thd.*,
                                        j.kode_matkul,
                                        j.nama_matkul,
                                        j.jml_mhs,
                                        u.nama_user AS nama_dosen,
                                        u.honor_persks
                                    FROM t_transaksi_honor_dosen thd
                                    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
                                    LEFT JOIN t_user u ON j.id_user = u.id_user
                                    ORDER BY thd.semester DESC, thd.bulan DESC
                                    LIMIT $offset, $limit
                                ");

                                while ($thd = mysqli_fetch_assoc($query_thd)) {
                                    $bulan = $thd['bulan'] ?? '-';
                                    $bulan_indonesia = [
                                        'januari' => 'Januari', 'februari' => 'Februari', 'maret' => 'Maret',
                                        'april' => 'April', 'mei' => 'Mei', 'juni' => 'Juni',
                                        'juli' => 'Juli', 'agustus' => 'Agustus', 'september' => 'September',
                                        'oktober' => 'Oktober', 'november' => 'November', 'desember' => 'Desember'
                                    ];
                                    $bulan_indo = $bulan_indonesia[$bulan] ?? ucfirst($bulan);
                                    
                                    $honor = $thd['sks_tempuh'] * ($thd['honor_persks'] ?? 0);
                                ?>
                                <tr>
                                    <td><span class="up-badge up-badge-default"><?= $no++ ?></span></td>
                                    <td>
                                        <?php 
                                        $semester = $thd['semester'];
                                        $tahun = substr($semester, 0, 4);
                                        $kode = substr($semester, -1);
                                        $badge_class = ($kode == '1') ? 'badge-ganjil' : 'badge-genap';
                                        ?>
                                        <span class="up-badge <?= $badge_class ?>">
                                            <?= $tahun ?> <?= ($kode == '1' ? 'Ganjil' : 'Genap') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="up-badge up-badge-info">
                                            <i class="far fa-calendar mr-1"></i> <?= $bulan_indo ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($thd['nama_dosen']): ?>
                                            <div class="d-flex align-items-center">
                                                <div class="up-avatar-xs bg-primary text-white mr-2">
                                                    <?= strtoupper(substr($thd['nama_dosen'], 0, 1)) ?>
                                                </div>
                                                <?= htmlspecialchars($thd['nama_dosen']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($thd['kode_matkul'] ?: '-') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($thd['nama_matkul'] ?: '-') ?></small>
                                        <?php if ($thd['jml_mhs']): ?>
                                            <br><span class="up-badge up-badge-default badge-sm"><?= $thd['jml_mhs'] ?> mhs</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><span class="up-badge up-badge-success"><?= $thd['jml_tm'] ?>x</span></td>
                                    <td class="text-center"><span class="up-badge up-badge-warning"><?= $thd['sks_tempuh'] ?> SKS</span></td>
                                    <td><span class="up-badge up-badge-primary">Rp <?= number_format($honor, 0, ',', '.') ?></span></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="#" onclick="viewData(<?= $thd['id_thd'] ?>)" class="up-btn-icon up-btn-icon-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" onclick="editData(<?= $thd['id_thd'] ?>)" class="up-btn-icon up-btn-icon-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $thd['id_thd'] ?>&page=<?= $page ?>" class="up-btn-icon up-btn-icon-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus data honor ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                                <?php if (mysqli_num_rows($query_thd) == 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada data honor dosen</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Menampilkan halaman <?= $page ?> dari <?= $total_pages ?> (Total <?= $total ?> data)
                        </div>
                        <ul class="up-pagination">
                            <li class="up-page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?page=<?= $page-1 ?>"><i class="fas fa-chevron-left"></i></a>
                            </li>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="up-page-item"><a class="up-page-link" href="?page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="up-page-item disabled"><a class="up-page-link">...</a></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="up-page-item ' . ($i == $page ? 'active' : '') . '">';
                                echo '<a class="up-page-link" href="?page=' . $i . '">' . $i . '</a>';
                                echo '</li>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="up-page-item disabled"><a class="up-page-link">...</a></li>';
                                }
                                echo '<li class="up-page-item"><a class="up-page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <li class="up-page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?page=<?= $page+1 ?>"><i class="fas fa-chevron-right"></i></a>
                            </li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Menampilkan <?= $total ?> data
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Form Tambah/Edit Honor Dosen -->
<div class="modal fade" id="thdModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Data Honor Dosen</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="thdForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_thd" id="thdId" value="">
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Semester <span class="req">*</span></label>
                        <select class="up-select" name="semester" id="semester" required>
                            <option value="">Pilih Semester</option>
                            <?= generateSemesterOptionsTHD($edit_data['semester'] ?? '') ?>
                        </select>
                    </div>
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Bulan <span class="req">*</span></label>
                        <select class="up-select" name="bulan" id="bulan" required>
                            <option value="">Pilih Bulan</option>
                            <?php foreach ($bulan_list as $bulan): ?>
                                <option value="<?= $bulan ?>" <?= ($edit_data && $edit_data['bulan'] == $bulan) ? 'selected' : '' ?>>
                                    <?= ucfirst($bulan) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Jadwal <span class="req">*</span></label>
                        <select class="up-select" name="id_jadwal" id="id_jadwal" required>
                            <option value="">Pilih Jadwal</option>
                            <?php foreach ($jadwal_list as $id => $nama): ?>
                                <option value="<?= $id ?>" <?= ($edit_data && $edit_data['id_jadwal'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nama) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Jumlah Tatap Muka <span class="req">*</span></label>
                        <input type="number" class="up-input" name="jml_tm" id="jml_tm" required min="0" value="0">
                    </div>
                    
                    <div class="up-form-group">
                        <label class="up-form-label">SKS Tempuh <span class="req">*</span></label>
                        <input type="number" class="up-input" name="sks_tempuh" id="sks_tempuh" required min="0" value="0">
                        <small class="up-form-hint">Jumlah SKS yang ditempuh untuk periode ini</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="up-btn up-btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="up-btn up-btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Honor Dosen -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Honor Dosen</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="up-btn up-btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function filterTable() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("dataTable");
    tr = table.getElementsByTagName("tr");
    
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td");
        let found = false;
        for (let j = 0; j < td.length - 1; j++) {
            if (td[j]) {
                txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        if (found) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

function openModal(action, id = null) {
    if (action === 'add') {
        document.getElementById('modalTitle').textContent = 'Tambah Data Honor Dosen';
        document.getElementById('formAction').value = 'add';
        document.getElementById('thdId').value = '';
        document.getElementById('thdForm').reset();
        $('#thdModal').modal('show');
    }
}

function editData(id) {
    fetch('ajax/get_thd.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit Data Honor Dosen';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('thdId').value = data.data.id_thd;
                document.getElementById('semester').value = data.data.semester;
                document.getElementById('bulan').value = data.data.bulan;
                document.getElementById('id_jadwal').value = data.data.id_jadwal;
                document.getElementById('jml_tm').value = data.data.jml_tm;
                document.getElementById('sks_tempuh').value = data.data.sks_tempuh;
                $('#thdModal').modal('show');
            }
        });
}

function viewData(id) {
    fetch('ajax/view_thd.php?id=' + id)
        .then(response => response.text())
        .then(html => {
            document.getElementById('viewContent').innerHTML = html;
            $('#viewModal').modal('show');
        });
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    document.querySelectorAll('.up-alert').forEach(function(alert) {
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.style.display = 'none';
        }, 300);
    });
}, 5000);
</script>

<?php 
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>