<?php
include '../../config.php';
$page_title = "Data Panitia";

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total data
$total_data = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_panitia");
$total = mysqli_fetch_assoc($total_data)['total'];
$total_pages = ceil($total / $limit);

// Proses Hapus Data
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Cek apakah ada relasi di t_transaksi_ujian atau t_transaksi_pa_ta
    $cek_relasi1 = mysqli_query($koneksi, "SELECT id_tu FROM t_transaksi_ujian WHERE id_panitia = '$id'");
    $cek_relasi2 = mysqli_query($koneksi, "SELECT id_tpt FROM t_transaksi_pa_ta WHERE id_panitia = '$id'");
    
    $errors = [];
    if (mysqli_num_rows($cek_relasi1) > 0) {
        $errors[] = "Data ini masih digunakan di tabel Transaksi Ujian";
    }
    if (mysqli_num_rows($cek_relasi2) > 0) {
        $errors[] = "Data ini masih digunakan di tabel Transaksi PA/TA";
    }
    
    if (!empty($errors)) {
        $_SESSION['error_message'] = "Tidak dapat menghapus karena:\n- " . implode("\n- ", $errors);
    } else {
        $delete = mysqli_query($koneksi, "DELETE FROM t_panitia WHERE id_pnt = '$id'");
        if ($delete) {
            $_SESSION['success_message'] = "Data panitia berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus data: " . mysqli_error($koneksi);
        }
    }
    header("Location: data_panitia.php" . ($page > 1 ? "?page=$page" : ""));
    exit();
}

// Proses Tambah/Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $id_pnt = mysqli_real_escape_string($koneksi, $_POST['id_pnt'] ?? '');
        $jbtn_pnt = mysqli_real_escape_string($koneksi, $_POST['jbtn_pnt']);
        $honor_std = mysqli_real_escape_string($koneksi, $_POST['honor_std']);
        $honor_p1 = mysqli_real_escape_string($koneksi, $_POST['honor_p1'] ?? 0);
        $honor_p2 = mysqli_real_escape_string($koneksi, $_POST['honor_p2'] ?? 0);
        
        if ($_POST['action'] == 'add') {
            $query = "INSERT INTO t_panitia (jbtn_pnt, honor_std, honor_p1, honor_p2) 
                      VALUES ('$jbtn_pnt', '$honor_std', '$honor_p1', '$honor_p2')";
            $message = "Data panitia berhasil ditambahkan!";
        } elseif ($_POST['action'] == 'edit' && $id_pnt) {
            $query = "UPDATE t_panitia SET 
                      jbtn_pnt = '$jbtn_pnt',
                      honor_std = '$honor_std',
                      honor_p1 = '$honor_p1',
                      honor_p2 = '$honor_p2'
                      WHERE id_pnt = '$id_pnt'";
            $message = "Data panitia berhasil diupdate!";
        }
        
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = "Gagal: " . mysqli_error($koneksi);
        }
        header("Location: data_panitia.php" . ($page > 1 ? "?page=$page" : ""));
        exit();
    }
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query_edit = mysqli_query($koneksi, "SELECT * FROM t_panitia WHERE id_pnt = '$id'");
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
            <h1><i class="fas fa-user-tie mr-2"></i>Data Panitia</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data</div>
                <div class="breadcrumb-item">Data Panitia</div>
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
            $total_panitia = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM t_panitia"));
            $max_honor = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT MAX(honor_std) as max FROM t_panitia"));
            $total_transaksi_ujian = mysqli_fetch_assoc(mysqli_query($koneksi, "
                SELECT COUNT(DISTINCT id_panitia) as total 
                FROM t_transaksi_ujian
            "));
            ?>
            <div class="up-stat-row">
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $total_panitia ?></div>
                    <div class="up-stat-label">Total Panitia</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value">
                        <?= $total_transaksi_ujian['total'] ?? 0 ?>
                    </div>
                    <div class="up-stat-label">Panitia Aktif</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value">
                        Rp <?= number_format($max_honor['max'] ?? 0, 0, ',', '.') ?>
                    </div>
                    <div class="up-stat-label">Honor Tertinggi</div>
                </div>
            </div>

            <!-- MAIN CARD -->
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon">
                        <i class="fas fa-address-card"></i>
                    </div>
                    <h5>Daftar Panitia</h5>
                    <div class="ml-auto d-flex align-items-center gap-2">
                        <div class="up-search-box" style="width: 250px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchInput" placeholder="Cari panitia..." onkeyup="filterTable()">
                        </div>
                        <button class="up-btn up-btn-success ml-2" onclick="openModal('add')">
                            <i class="fas fa-plus mr-1"></i> Tambah Panitia
                        </button>
                    </div>
                </div>
                <div class="up-card-body">
                    <div class="up-table-responsive">
                        <table class="up-table up-table-hover" id="dataTable">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>Jabatan Panitia</th>
                                    <th>Honor Standar</th>
                                    <th>Honor P1</th>
                                    <th>Honor P2</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                $pnt = mysqli_query($koneksi, "SELECT * FROM t_panitia ORDER BY jbtn_pnt ASC LIMIT $offset, $limit");
                                while ($row = mysqli_fetch_assoc($pnt)) {
                                ?>
                                <tr>
                                    <td><span class="up-badge up-badge-default"><?= $no++ ?></span></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['jbtn_pnt']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="up-badge up-badge-success">
                                            Rp <?= number_format($row['honor_std'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="up-badge up-badge-info">
                                            Rp <?= number_format($row['honor_p1'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="up-badge up-badge-warning">
                                            Rp <?= number_format($row['honor_p2'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="#" onclick="viewData(<?= $row['id_pnt'] ?>)" class="up-btn-icon up-btn-icon-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" onclick="editData(<?= $row['id_pnt'] ?>)" class="up-btn-icon up-btn-icon-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $row['id_pnt'] ?>&page=<?= $page ?>" class="up-btn-icon up-btn-icon-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus panitia <?= htmlspecialchars($row['jbtn_pnt']) ?>?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>
                                <?php if (mysqli_num_rows($pnt) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada data panitia</p>
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

<!-- Modal Form Tambah/Edit Panitia -->
<div class="modal fade" id="panitiaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Panitia</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="panitiaForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_pnt" id="panitiaId" value="">
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Jabatan Panitia <span class="req">*</span></label>
                        <input type="text" class="up-input" name="jbtn_pnt" id="jbtn_pnt" required maxlength="100">
                    </div>
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Honor Standar <span class="req">*</span></label>
                        <input type="number" class="up-input" name="honor_std" id="honor_std" required min="0" value="0">
                    </div>
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Honor P1</label>
                        <input type="number" class="up-input" name="honor_p1" id="honor_p1" min="0" value="0">
                        <small class="up-form-hint">Honor untuk penguji 1 (jika ada)</small>
                    </div>
                    
                    <div class="up-form-group">
                        <label class="up-form-label">Honor P2</label>
                        <input type="number" class="up-input" name="honor_p2" id="honor_p2" min="0" value="0">
                        <small class="up-form-hint">Honor untuk penguji 2 (jika ada)</small>
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

<!-- Modal Detail Panitia -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Panitia</h5>
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
        document.getElementById('modalTitle').textContent = 'Tambah Panitia';
        document.getElementById('formAction').value = 'add';
        document.getElementById('panitiaId').value = '';
        document.getElementById('panitiaForm').reset();
        $('#panitiaModal').modal('show');
    }
}

function editData(id) {
    fetch('ajax/get_panitia.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit Panitia';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('panitiaId').value = data.data.id_pnt;
                document.getElementById('jbtn_pnt').value = data.data.jbtn_pnt;
                document.getElementById('honor_std').value = data.data.honor_std;
                document.getElementById('honor_p1').value = data.data.honor_p1;
                document.getElementById('honor_p2').value = data.data.honor_p2;
                $('#panitiaModal').modal('show');
            }
        });
}

function viewData(id) {
    fetch('ajax/view_panitia.php?id=' + id)
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