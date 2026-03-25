<?php
include '../../config.php';
$page_title = "Data Jadwal";

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
$total_data = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_jadwal");
$total = mysqli_fetch_assoc($total_data)['total'];
$total_pages = ceil($total / $limit);

// Proses Hapus Data
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Cek apakah ada relasi di t_transaksi_honor_dosen
    $cek_relasi = mysqli_query($koneksi, "SELECT id_thd FROM t_transaksi_honor_dosen WHERE id_jadwal = '$id'");
    
    if (mysqli_num_rows($cek_relasi) > 0) {
        $_SESSION['error_message'] = "Tidak dapat menghapus karena data ini masih digunakan di Transaksi Honor Dosen!";
    } else {
        $delete = mysqli_query($koneksi, "DELETE FROM t_jadwal WHERE id_jdwl = '$id'");
        if ($delete) {
            $_SESSION['success_message'] = "Data jadwal berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus data: " . mysqli_error($koneksi);
        }
    }
    header("Location: data_jadwal.php" . ($page > 1 ? "?page=$page" : ""));
    exit();
}

// Proses Tambah/Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $id_jdwl = mysqli_real_escape_string($koneksi, $_POST['id_jdwl'] ?? '');
        $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
        $kode_matkul = mysqli_real_escape_string($koneksi, $_POST['kode_matkul']);
        $nama_matkul = mysqli_real_escape_string($koneksi, $_POST['nama_matkul']);
        $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
        $jml_mhs = mysqli_real_escape_string($koneksi, $_POST['jml_mhs']);
        
        if ($_POST['action'] == 'add') {
            $query = "INSERT INTO t_jadwal (semester, kode_matkul, nama_matkul, id_user, jml_mhs) 
                      VALUES ('$semester', '$kode_matkul', '$nama_matkul', '$id_user', '$jml_mhs')";
            $message = "Data jadwal berhasil ditambahkan!";
        } elseif ($_POST['action'] == 'edit' && $id_jdwl) {
            $query = "UPDATE t_jadwal SET 
                      semester = '$semester',
                      kode_matkul = '$kode_matkul',
                      nama_matkul = '$nama_matkul',
                      id_user = '$id_user',
                      jml_mhs = '$jml_mhs'
                      WHERE id_jdwl = '$id_jdwl'";
            $message = "Data jadwal berhasil diupdate!";
        }
        
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = "Gagal: " . mysqli_error($koneksi);
        }
        header("Location: data_jadwal.php" . ($page > 1 ? "?page=$page" : ""));
        exit();
    }
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query_edit = mysqli_query($koneksi, "SELECT * FROM t_jadwal WHERE id_jdwl = '$id'");
    $edit_data = mysqli_fetch_assoc($query_edit);
}

// Ambil data user untuk dropdown
$users = [];
$query_users = mysqli_query($koneksi, "SELECT id_user, npp_user, nama_user FROM t_user WHERE role_user IN ('staff', 'koordinator') ORDER BY nama_user");
while ($row = mysqli_fetch_assoc($query_users)) {
    $users[$row['id_user']] = $row['npp_user'] . ' - ' . $row['nama_user'];
}

// Generate semester options
function generateSemesterOptions($selected = '') {
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
            <h1><i class="fas fa-calendar-alt mr-2"></i>Data Jadwal</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data</div>
                <div class="breadcrumb-item">Data Jadwal</div>
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
            $total_jadwal = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM t_jadwal"));
            $total_matkul = mysqli_num_rows(mysqli_query($koneksi, "SELECT DISTINCT kode_matkul FROM t_jadwal"));
            $semester_aktif_query = mysqli_query($koneksi, "SELECT semester FROM t_jadwal GROUP BY semester ORDER BY semester DESC LIMIT 1");
            $semester_aktif = mysqli_fetch_assoc($semester_aktif_query);
            ?>
            <div class="up-stat-row">
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_jadwal ?></div>
                    <div class="up-stat-label">Total Jadwal</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_matkul ?></div>
                    <div class="up-stat-label">Mata Kuliah</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value">
                        <?= $semester_aktif ? formatSemester($semester_aktif['semester']) : '-' ?>
                    </div>
                    <div class="up-stat-label">Semester Aktif</div>
                </div>
            </div>

            <!-- MAIN CARD -->
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon">
                        <i class="fas fa-table"></i>
                    </div>
                    <h5>Daftar Jadwal Perkuliahan</h5>
                    <div class="ml-auto d-flex align-items-center gap-2">
                        <div class="up-search-box" style="width: 250px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchInput" placeholder="Cari jadwal..." onkeyup="filterTable()">
                        </div>
                        <button class="up-btn up-btn-success ml-2" onclick="openModal('add')">
                            <i class="fas fa-plus mr-1"></i> Tambah Jadwal
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
                                    <th>Kode MK</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th>Staff</th>
                                    <th>Jumlah Mahasiswa</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                $jadwal = mysqli_query($koneksi, "
                                    SELECT j.*, u.nama_user, u.npp_user
                                    FROM t_jadwal j
                                    LEFT JOIN t_user u ON j.id_user = u.id_user
                                    ORDER BY j.semester DESC, j.kode_matkul ASC
                                    LIMIT $offset, $limit
                                ");
                                while ($jdw = mysqli_fetch_assoc($jadwal)) {
                                ?>
                                <tr>
                                    <td><span class="up-badge up-badge-default"><?= $no++ ?></span></td>
                                    <td>
                                        <?php 
                                        $semester = $jdw['semester'];
                                        $tahun = substr($semester, 0, 4);
                                        $kode = substr($semester, -1);
                                        $badge_class = ($kode == '1') ? 'badge-ganjil' : 'badge-genap';
                                        ?>
                                        <span class="up-badge <?= $badge_class ?>">
                                            <?= $tahun ?> <?= ($kode == '1' ? 'Ganjil' : 'Genap') ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($jdw['kode_matkul']) ?></strong></td>
                                    <td><?= htmlspecialchars($jdw['nama_matkul']) ?></td>
                                    <td>
                                        <?php if ($jdw['nama_user']): ?>
                                            <div class="d-flex align-items-center">
                                                <div class="up-avatar-xs bg-success text-white mr-2">
                                                    <?= strtoupper(substr($jdw['nama_user'], 0, 1)) ?>
                                                </div>
                                                <?= htmlspecialchars($jdw['nama_user']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="up-badge up-badge-info">
                                            <i class="fas fa-users mr-1"></i> <?= $jdw['jml_mhs'] ?> mhs
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="#" onclick="viewData(<?= $jdw['id_jdwl'] ?>)" class="up-btn-icon up-btn-icon-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" onclick="editData(<?= $jdw['id_jdwl'] ?>)" class="up-btn-icon up-btn-icon-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $jdw['id_jdwl'] ?>&page=<?= $page ?>" class="up-btn-icon up-btn-icon-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus jadwal <?= htmlspecialchars($jdw['kode_matkul']) ?> - <?= htmlspecialchars($jdw['nama_matkul']) ?>?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>
                                <?php if (mysqli_num_rows($jadwal) == 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada data jadwal</p>
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

<!-- Modal Form Tambah/Edit Jadwal -->
<div class="modal fade" id="jadwalModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Jadwal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="jadwalForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_jdwl" id="jadwalId" value="">
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Semester <span class="req">*</span></label>
                        <select class="up-select" name="semester" id="semester" required>
                            <option value="">Pilih Semester</option>
                            <?= generateSemesterOptions($edit_data['semester'] ?? '') ?>
                        </select>
                    </div>
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Kode Mata Kuliah <span class="req">*</span></label>
                        <input type="text" class="up-input" name="kode_matkul" id="kode_matkul" required maxlength="7" placeholder="Contoh: SI101">
                    </div>
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Nama Mata Kuliah <span class="req">*</span></label>
                        <input type="text" class="up-input" name="nama_matkul" id="nama_matkul" required maxlength="30">
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
                        <label class="up-form-label">Jumlah Mahasiswa <span class="req">*</span></label>
                        <input type="number" class="up-input" name="jml_mhs" id="jml_mhs" required min="0" value="0">
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

<!-- Modal Detail Jadwal -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Jadwal</h5>
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
        document.getElementById('modalTitle').textContent = 'Tambah Jadwal';
        document.getElementById('formAction').value = 'add';
        document.getElementById('jadwalId').value = '';
        document.getElementById('jadwalForm').reset();
        $('#jadwalModal').modal('show');
    }
}

function editData(id) {
    fetch('ajax/get_jadwal.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit Jadwal';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('jadwalId').value = data.data.id_jdwl;
                document.getElementById('semester').value = data.data.semester;
                document.getElementById('kode_matkul').value = data.data.kode_matkul;
                document.getElementById('nama_matkul').value = data.data.nama_matkul;
                document.getElementById('id_user').value = data.data.id_user;
                document.getElementById('jml_mhs').value = data.data.jml_mhs;
                $('#jadwalModal').modal('show');
            }
        });
}

function viewData(id) {
    fetch('ajax/view_jadwal.php?id=' + id)
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