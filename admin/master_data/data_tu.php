<?php
include '../../config.php';
$page_title = "Data Transaksi Ujian";

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
$total_data = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_ujian");
$total = mysqli_fetch_assoc($total_data)['total'];
$total_pages = ceil($total / $limit);

// Proses Hapus Data
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $delete = mysqli_query($koneksi, "DELETE FROM t_transaksi_ujian WHERE id_tu = '$id'");
    
    if ($delete) {
        $_SESSION['success_message'] = "Data transaksi ujian berhasil dihapus!";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
    header("Location: data_tu.php" . ($page > 1 ? "?page=$page" : ""));
    exit();
}

// Proses Tambah/Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $id_tu = mysqli_real_escape_string($koneksi, $_POST['id_tu'] ?? '');
        $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
        $id_panitia = mysqli_real_escape_string($koneksi, $_POST['id_panitia']);
        $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        $jml_mhs_prodi = mysqli_real_escape_string($koneksi, $_POST['jml_mhs_prodi']);
        $jml_mhs = mysqli_real_escape_string($koneksi, $_POST['jml_mhs']);
        $jml_koreksi = mysqli_real_escape_string($koneksi, $_POST['jml_koreksi']);
        $jml_matkul = mysqli_real_escape_string($koneksi, $_POST['jml_matkul']);
        $jml_pgws_pagi = mysqli_real_escape_string($koneksi, $_POST['jml_pgws_pagi']);
        $jml_pgws_sore = mysqli_real_escape_string($koneksi, $_POST['jml_pgws_sore']);
        $jml_koor_pagi = mysqli_real_escape_string($koneksi, $_POST['jml_koor_pagi']);
        $jml_koor_sore = mysqli_real_escape_string($koneksi, $_POST['jml_koor_sore']);
        
        if ($_POST['action'] == 'add') {
            $query = "INSERT INTO t_transaksi_ujian (semester, id_panitia, id_user, jml_mhs_prodi, jml_mhs, jml_koreksi, jml_matkul, jml_pgws_pagi, jml_pgws_sore, jml_koor_pagi, jml_koor_sore) 
                      VALUES ('$semester', '$id_panitia', '$id_user', '$jml_mhs_prodi', '$jml_mhs', '$jml_koreksi', '$jml_matkul', '$jml_pgws_pagi', '$jml_pgws_sore', '$jml_koor_pagi', '$jml_koor_sore')";
            $message = "Data transaksi ujian berhasil ditambahkan!";
        } elseif ($_POST['action'] == 'edit' && $id_tu) {
            $query = "UPDATE t_transaksi_ujian SET 
                      semester = '$semester',
                      id_panitia = '$id_panitia',
                      id_user = '$id_user',
                      jml_mhs_prodi = '$jml_mhs_prodi',
                      jml_mhs = '$jml_mhs',
                      jml_koreksi = '$jml_koreksi',
                      jml_matkul = '$jml_matkul',
                      jml_pgws_pagi = '$jml_pgws_pagi',
                      jml_pgws_sore = '$jml_pgws_sore',
                      jml_koor_pagi = '$jml_koor_pagi',
                      jml_koor_sore = '$jml_koor_sore'
                      WHERE id_tu = '$id_tu'";
            $message = "Data transaksi ujian berhasil diupdate!";
        }
        
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = "Gagal: " . mysqli_error($koneksi);
        }
        header("Location: data_tu.php" . ($page > 1 ? "?page=$page" : ""));
        exit();
    }
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query_edit = mysqli_query($koneksi, "SELECT * FROM t_transaksi_ujian WHERE id_tu = '$id'");
    $edit_data = mysqli_fetch_assoc($query_edit);
}

// Ambil data user untuk dropdown
$users = [];
$query_users = mysqli_query($koneksi, "SELECT id_user, npp_user, nama_user FROM t_user WHERE role_user IN ('staff', 'koordinator') ORDER BY nama_user");
while ($row = mysqli_fetch_assoc($query_users)) {
    $users[$row['id_user']] = $row['npp_user'] . ' - ' . $row['nama_user'];
}

// Ambil data panitia untuk dropdown
$panitia = [];
$query_panitia = mysqli_query($koneksi, "SELECT id_pnt, jbtn_pnt FROM t_panitia ORDER BY jbtn_pnt");
while ($row = mysqli_fetch_assoc($query_panitia)) {
    $panitia[$row['id_pnt']] = $row['jbtn_pnt'];
}

// Generate semester options
function generateSemesterOptionsTU($selected = '') {
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
            <h1><i class="fas fa-file-signature mr-2"></i>Data Transaksi Ujian</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data</div>
                <div class="breadcrumb-item">Transaksi Ujian</div>
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
            $total_tu = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM t_transaksi_ujian"));
            $total_mhs = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(jml_mhs) as total_mhs, SUM(jml_mhs_prodi) as total_prodi FROM t_transaksi_ujian"));
            $total_pengawas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(jml_pgws_pagi + jml_pgws_sore) as total_pengawas, SUM(jml_koor_pagi + jml_koor_sore) as total_koordinator FROM t_transaksi_ujian"));
            ?>
            <div class="up-stat-row">
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_tu ?></div>
                    <div class="up-stat-label">Total Transaksi</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_mhs['total_mhs'] ?? 0 ?></div>
                    <div class="up-stat-label">Total Mahasiswa</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_pengawas['total_pengawas'] ?? 0 ?></div>
                    <div class="up-stat-label">Total Pengawas</div>
                </div>
            </div>

            <!-- MAIN CARD -->
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon">
                        <i class="fas fa-pen-alt"></i>
                    </div>
                    <h5>Daftar Transaksi Ujian</h5>
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
                                    <th>Panitia</th>
                                    <th>Staff</th>
                                    <th>Mhs Prodi</th>
                                    <th>Mhs</th>
                                    <th>Koreksi</th>
                                    <th>Matkul</th>
                                    <th>Pgws Pagi</th>
                                    <th>Pgws Sore</th>
                                    <th>Koor Pagi</th>
                                    <th>Koor Sore</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                $query_tu = mysqli_query($koneksi, "
                                    SELECT 
                                        tu.*,
                                        p.jbtn_pnt,
                                        u.nama_user,
                                        u.npp_user
                                    FROM t_transaksi_ujian tu
                                    LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
                                    LEFT JOIN t_user u ON tu.id_user = u.id_user
                                    ORDER BY tu.semester DESC, tu.id_tu DESC
                                    LIMIT $offset, $limit
                                ");

                                while ($tu = mysqli_fetch_assoc($query_tu)) {
                                ?>
                                <tr>
                                    <td><span class="up-badge up-badge-default"><?= $no++ ?></span></td>
                                    <td>
                                        <?php 
                                        $semester = $tu['semester'];
                                        $tahun = substr($semester, 0, 4);
                                        $kode = substr($semester, -1);
                                        $badge_class = ($kode == '1') ? 'badge-ganjil' : 'badge-genap';
                                        ?>
                                        <span class="up-badge <?= $badge_class ?>">
                                            <?= $tahun ?> <?= ($kode == '1' ? 'Ganjil' : 'Genap') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($tu['jbtn_pnt']): ?>
                                            <span class="up-badge up-badge-warning">
                                                <?= htmlspecialchars($tu['jbtn_pnt']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tu['nama_user']): ?>
                                            <div class="d-flex align-items-center">
                                                <div class="up-avatar-xs bg-primary text-white mr-2">
                                                    <?= strtoupper(substr($tu['nama_user'], 0, 1)) ?>
                                                </div>
                                                <?= htmlspecialchars($tu['nama_user']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><strong><?= $tu['jml_mhs_prodi'] ?></strong></td>
                                    <td class="text-center"><strong><?= $tu['jml_mhs'] ?></strong></td>
                                    <td class="text-center"><?= $tu['jml_koreksi'] ?></td>
                                    <td class="text-center"><?= $tu['jml_matkul'] ?></td>
                                    <td class="text-center"><span class="up-badge up-badge-success"><?= $tu['jml_pgws_pagi'] ?></span></td>
                                    <td class="text-center"><span class="up-badge up-badge-info"><?= $tu['jml_pgws_sore'] ?></span></td>
                                    <td class="text-center"><span class="up-badge up-badge-primary"><?= $tu['jml_koor_pagi'] ?></span></td>
                                    <td class="text-center"><span class="up-badge up-badge-secondary"><?= $tu['jml_koor_sore'] ?></span></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="#" onclick="viewData(<?= $tu['id_tu'] ?>)" class="up-btn-icon up-btn-icon-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" onclick="editData(<?= $tu['id_tu'] ?>)" class="up-btn-icon up-btn-icon-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $tu['id_tu'] ?>&page=<?= $page ?>" class="up-btn-icon up-btn-icon-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus data transaksi ujian ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                                <?php if (mysqli_num_rows($query_tu) == 0): ?>
                                <tr>
                                    <td colspan="13" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada data transaksi ujian</p>
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

<!-- Modal Form Tambah/Edit Transaksi Ujian -->
<div class="modal fade" id="tuModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Data Transaksi Ujian</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="tuForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_tu" id="tuId" value="">
                    
                    <div class="up-form-grid" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="up-form-group">
                            <label class="up-form-label">Semester <span class="req">*</span></label>
                            <select class="up-select" name="semester" id="semester" required>
                                <option value="">Pilih Semester</option>
                                <?= generateSemesterOptionsTU($edit_data['semester'] ?? '') ?>
                            </select>
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Panitia <span class="req">*</span></label>
                            <select class="up-select" name="id_panitia" id="id_panitia" required>
                                <option value="">Pilih Panitia</option>
                                <?php foreach ($panitia as $id => $nama): ?>
                                    <option value="<?= $id ?>" <?= ($edit_data && $edit_data['id_panitia'] == $id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nama) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Staff/Dosen <span class="req">*</span></label>
                            <select class="up-select" name="id_user" id="id_user" required>
                                <option value="">Pilih Staff/Dosen</option>
                                <?php foreach ($users as $id => $nama): ?>
                                    <option value="<?= $id ?>" <?= ($edit_data && $edit_data['id_user'] == $id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nama) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Jml Mhs Prodi</label>
                            <input type="number" class="up-input" name="jml_mhs_prodi" id="jml_mhs_prodi" min="0" value="0">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Jml Mahasiswa</label>
                            <input type="number" class="up-input" name="jml_mhs" id="jml_mhs" min="0" value="0">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Jml Koreksi</label>
                            <input type="number" class="up-input" name="jml_koreksi" id="jml_koreksi" min="0" value="0">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Jml Mata Kuliah</label>
                            <input type="number" class="up-input" name="jml_matkul" id="jml_matkul" min="0" value="0">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Pengawas Pagi</label>
                            <input type="number" class="up-input" name="jml_pgws_pagi" id="jml_pgws_pagi" min="0" value="0">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Pengawas Sore</label>
                            <input type="number" class="up-input" name="jml_pgws_sore" id="jml_pgws_sore" min="0" value="0">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Koordinator Pagi</label>
                            <input type="number" class="up-input" name="jml_koor_pagi" id="jml_koor_pagi" min="0" value="0">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Koordinator Sore</label>
                            <input type="number" class="up-input" name="jml_koor_sore" id="jml_koor_sore" min="0" value="0">
                        </div>
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

<!-- Modal Detail Transaksi Ujian -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi Ujian</h5>
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
        document.getElementById('modalTitle').textContent = 'Tambah Data Transaksi Ujian';
        document.getElementById('formAction').value = 'add';
        document.getElementById('tuId').value = '';
        document.getElementById('tuForm').reset();
        $('#tuModal').modal('show');
    }
}

function editData(id) {
    fetch('ajax/get_tu.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit Data Transaksi Ujian';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('tuId').value = data.data.id_tu;
                document.getElementById('semester').value = data.data.semester;
                document.getElementById('id_panitia').value = data.data.id_panitia;
                document.getElementById('id_user').value = data.data.id_user;
                document.getElementById('jml_mhs_prodi').value = data.data.jml_mhs_prodi;
                document.getElementById('jml_mhs').value = data.data.jml_mhs;
                document.getElementById('jml_koreksi').value = data.data.jml_koreksi;
                document.getElementById('jml_matkul').value = data.data.jml_matkul;
                document.getElementById('jml_pgws_pagi').value = data.data.jml_pgws_pagi;
                document.getElementById('jml_pgws_sore').value = data.data.jml_pgws_sore;
                document.getElementById('jml_koor_pagi').value = data.data.jml_koor_pagi;
                document.getElementById('jml_koor_sore').value = data.data.jml_koor_sore;
                $('#tuModal').modal('show');
            }
        });
}

function viewData(id) {
    fetch('ajax/view_tu.php?id=' + id)
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