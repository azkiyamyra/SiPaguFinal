<?php
include '../../config.php';
$page_title = "Data Koordinator";

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total data
$total_data = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_user WHERE role_user='koordinator'");
$total = mysqli_fetch_assoc($total_data)['total'];
$total_pages = ceil($total / $limit);

// Proses Hapus Data
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Cek apakah ada relasi di tabel lain
    $cek_jadwal = mysqli_query($koneksi, "SELECT id_jdwl FROM t_jadwal WHERE id_user = '$id'");
    $cek_tpata = mysqli_query($koneksi, "SELECT id_tpt FROM t_transaksi_pa_ta WHERE id_user = '$id'");
    $cek_tu = mysqli_query($koneksi, "SELECT id_tu FROM t_transaksi_ujian WHERE id_user = '$id'");
    
    $errors = [];
    if (mysqli_num_rows($cek_jadwal) > 0) {
        $errors[] = "Data ini masih digunakan sebagai pengampu mata kuliah di tabel Jadwal";
    }
    if (mysqli_num_rows($cek_tpata) > 0) {
        $errors[] = "Data ini masih digunakan di tabel Transaksi PA/TA";
    }
    if (mysqli_num_rows($cek_tu) > 0) {
        $errors[] = "Data ini masih digunakan di tabel Transaksi Ujian";
    }
    
    if (!empty($errors)) {
        $_SESSION['error_message'] = "Tidak dapat menghapus karena:\n- " . implode("\n- ", $errors);
    } else {
        $delete = mysqli_query($koneksi, "DELETE FROM t_user WHERE id_user = '$id' AND role_user = 'koordinator'");
        if ($delete) {
            $_SESSION['success_message'] = "Data koordinator berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus data: " . mysqli_error($koneksi);
        }
    }
    header("Location: data_koordinator.php" . ($page > 1 ? "?page=$page" : ""));
    exit();
}

// Proses Tambah/Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user'] ?? '');
        $npp_user = mysqli_real_escape_string($koneksi, $_POST['npp_user']);
        $nik_user = mysqli_real_escape_string($koneksi, $_POST['nik_user']);
        $npwp_user = mysqli_real_escape_string($koneksi, $_POST['npwp_user']);
        $norek_user = mysqli_real_escape_string($koneksi, $_POST['norek_user']);
        $nama_user = mysqli_real_escape_string($koneksi, $_POST['nama_user']);
        $nohp_user = mysqli_real_escape_string($koneksi, $_POST['nohp_user']);
        $pw_user = isset($_POST['pw_user']) ? md5($_POST['pw_user']) : '';
        $honor_persks = mysqli_real_escape_string($koneksi, $_POST['honor_persks'] ?? 0);
        
        if ($_POST['action'] == 'add') {
            $query = "INSERT INTO t_user (npp_user, nik_user, npwp_user, norek_user, nama_user, nohp_user, pw_user, role_user, honor_persks) 
                      VALUES ('$npp_user', '$nik_user', '$npwp_user', '$norek_user', '$nama_user', '$nohp_user', '$pw_user', 'koordinator', '$honor_persks')";
            $message = "Data koordinator berhasil ditambahkan!";
        } elseif ($_POST['action'] == 'edit' && $id_user) {
            if (!empty($_POST['pw_user'])) {
                $query = "UPDATE t_user SET 
                          npp_user = '$npp_user',
                          nik_user = '$nik_user',
                          npwp_user = '$npwp_user',
                          norek_user = '$norek_user',
                          nama_user = '$nama_user',
                          nohp_user = '$nohp_user',
                          pw_user = '$pw_user',
                          honor_persks = '$honor_persks'
                          WHERE id_user = '$id_user' AND role_user = 'koordinator'";
            } else {
                $query = "UPDATE t_user SET 
                          npp_user = '$npp_user',
                          nik_user = '$nik_user',
                          npwp_user = '$npwp_user',
                          norek_user = '$norek_user',
                          nama_user = '$nama_user',
                          nohp_user = '$nohp_user',
                          honor_persks = '$honor_persks'
                          WHERE id_user = '$id_user' AND role_user = 'koordinator'";
            }
            $message = "Data koordinator berhasil diupdate!";
        }
        
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = "Gagal: " . mysqli_error($koneksi);
        }
        header("Location: data_koordinator.php" . ($page > 1 ? "?page=$page" : ""));
        exit();
    }
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query_edit = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id' AND role_user = 'koordinator'");
    $edit_data = mysqli_fetch_assoc($query_edit);
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-users-cog mr-2"></i>Data Koordinator</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data</div>
                <div class="breadcrumb-item">Data Koordinator</div>
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
                <div class="up-alert-content">
                    <?php 
                    $error_msg = $_SESSION['error_message'];
                    if (strpos($error_msg, "\n") !== false) {
                        $errors = explode("\n", $error_msg);
                        echo '<strong>' . array_shift($errors) . '</strong>';
                        echo '<ul style="margin-top: 8px; margin-bottom: 0; padding-left: 20px;">';
                        foreach ($errors as $err) {
                            if (trim($err)) echo '<li>' . htmlspecialchars(trim($err)) . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo htmlspecialchars($error_msg);
                    }
                    ?>
                </div>
                <button class="up-alert-close" onclick="this.closest('.up-alert').remove()"><span>×</span></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- STATS CARDS -->
            <?php
            $total_koor = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM t_user WHERE role_user='koordinator'"));
            $total_matkul_diampu = mysqli_fetch_assoc(mysqli_query($koneksi, "
                SELECT COUNT(DISTINCT j.id_jdwl) as total 
                FROM t_jadwal j 
                INNER JOIN t_user u ON j.id_user = u.id_user 
                WHERE u.role_user = 'koordinator'
            "));
            ?>
            <div class="up-stat-row">
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_koor ?></div>
                    <div class="up-stat-label">Total Koordinator</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_matkul_diampu['total'] ?? 0 ?></div>
                    <div class="up-stat-label">Mata Kuliah Diampu</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value">
                        <i class="fas fa-university text-info"></i>
                    </div>
                    <div class="up-stat-label">Program Studi</div>
                </div>
            </div>

            <!-- MAIN CARD -->
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <h5>Daftar Koordinator</h5>
                    <div class="ml-auto d-flex align-items-center gap-2">
                        <div class="up-search-box" style="width: 250px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchInput" placeholder="Cari koordinator..." onkeyup="filterTable()">
                        </div>
                        <button class="up-btn up-btn-success ml-2" onclick="openModal('add')">
                            <i class="fas fa-plus mr-1"></i> Tambah Koordinator
                        </button>
                    </div>
                </div>
                <div class="up-card-body">
                    <div class="up-table-responsive">
                        <table class="up-table up-table-hover" id="dataTable">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>NPP</th>
                                    <th>NIK</th>
                                    <th>NPWP</th>
                                    <th>No Rekening</th>
                                    <th>Nama</th>
                                    <th>No Handphone</th>
                                    <th>Honor/SKS</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                $koor = mysqli_query($koneksi, "SELECT * FROM t_user WHERE role_user='koordinator' ORDER BY nama_user ASC LIMIT $offset, $limit");
                                while ($ko = mysqli_fetch_assoc($koor)) {
                                ?>
                                <tr>
                                    <td><span class="up-badge up-badge-default"><?= $no++ ?></span></td>
                                    <td><strong><?= htmlspecialchars($ko['npp_user']) ?></strong></td>
                                    <td><?= htmlspecialchars($ko['nik_user']) ?: '-' ?></td>
                                    <td><?= htmlspecialchars($ko['npwp_user']) ?: '-' ?></td>
                                    <td><?= htmlspecialchars($ko['norek_user']) ?: '-' ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="up-avatar-sm bg-warning text-white mr-2">
                                                <?= strtoupper(substr($ko['nama_user'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($ko['nama_user']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($ko['nohp_user']) ?: '-' ?></td>
                                    <td>
                                        <span class="up-badge up-badge-success">
                                            Rp <?= number_format($ko['honor_persks'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="#" onclick="viewData(<?= $ko['id_user'] ?>)" class="up-btn-icon up-btn-icon-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" onclick="editData(<?= $ko['id_user'] ?>)" class="up-btn-icon up-btn-icon-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $ko['id_user'] ?>&page=<?= $page ?>" class="up-btn-icon up-btn-icon-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus koordinator <?= htmlspecialchars($ko['nama_user']) ?>?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>
                                <?php if (mysqli_num_rows($koor) == 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada data koordinator</p>
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

<!-- Modal Form Tambah/Edit Koordinator -->
<div class="modal fade" id="koordinatorModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Koordinator</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="koordinatorForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_user" id="userId" value="">
                    
                    <div class="up-form-grid" style="grid-template-columns: repeat(2, 1fr);">
                        <div class="up-form-group">
                            <label class="up-form-label">NPP <span class="req">*</span></label>
                            <input type="text" class="up-input" name="npp_user" id="npp_user" required maxlength="20">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">NIK <span class="req">*</span></label>
                            <input type="text" class="up-input" name="nik_user" id="nik_user" required maxlength="16">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">NPWP</label>
                            <input type="text" class="up-input" name="npwp_user" id="npwp_user" maxlength="20">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">No Rekening</label>
                            <input type="text" class="up-input" name="norek_user" id="norek_user" maxlength="30">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Nama Lengkap <span class="req">*</span></label>
                            <input type="text" class="up-input" name="nama_user" id="nama_user" required maxlength="100">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">No Handphone</label>
                            <input type="text" class="up-input" name="nohp_user" id="nohp_user" maxlength="20">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Honor per SKS</label>
                            <input type="number" class="up-input" name="honor_persks" id="honor_persks" min="0" value="0">
                        </div>
                        
                        <div class="up-form-group">
                            <label class="up-form-label">Password <span class="req" id="passwordReq">*</span></label>
                            <input type="password" class="up-input" name="pw_user" id="pw_user" <?= !$edit_data ? 'required' : '' ?>>
                            <small class="up-form-hint" id="passwordHint">Minimal 6 karakter</small>
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

<!-- Modal Detail Koordinator -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Koordinator</h5>
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
        document.getElementById('modalTitle').textContent = 'Tambah Koordinator';
        document.getElementById('formAction').value = 'add';
        document.getElementById('userId').value = '';
        document.getElementById('koordinatorForm').reset();
        document.getElementById('passwordReq').style.display = 'inline';
        document.getElementById('pw_user').required = true;
        document.getElementById('passwordHint').textContent = 'Minimal 6 karakter';
        $('#koordinatorModal').modal('show');
    }
}

function editData(id) {
    fetch('ajax/get_koordinator.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit Koordinator';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('userId').value = data.data.id_user;
                document.getElementById('npp_user').value = data.data.npp_user;
                document.getElementById('nik_user').value = data.data.nik_user;
                document.getElementById('npwp_user').value = data.data.npwp_user;
                document.getElementById('norek_user').value = data.data.norek_user;
                document.getElementById('nama_user').value = data.data.nama_user;
                document.getElementById('nohp_user').value = data.data.nohp_user;
                document.getElementById('honor_persks').value = data.data.honor_persks;
                document.getElementById('passwordReq').style.display = 'none';
                document.getElementById('pw_user').required = false;
                document.getElementById('passwordHint').textContent = 'Kosongkan jika tidak ingin mengubah password';
                $('#koordinatorModal').modal('show');
            }
        });
}

function viewData(id) {
    fetch('ajax/view_koordinator.php?id=' + id)
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