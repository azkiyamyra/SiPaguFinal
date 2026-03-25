<?php
/**
 * DATA PA/TA - SiPagu
 * Halaman untuk melihat data PA/TA
 * Lokasi: admin/pa_ta.php
 */

// Include required files
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$page_title = "Data PA/TA";

// Ambil data
$query = mysqli_query($koneksi, "
    SELECT tp.*, u.nama_user, p.jbtn_pnt 
    FROM t_transaksi_pa_ta tp
    LEFT JOIN t_user u ON tp.id_user = u.id_user
    LEFT JOIN t_panitia p ON tp.id_panitia = p.id_pnt
    ORDER BY tp.id_tpt DESC
");

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/sidebar_admin.php';
?>

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Data PA/TA</h1>
        </div>
        
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert"><span>×</span></button>
                        <?= $_SESSION['success_message']; ?>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
    
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert"><span>×</span></button>
                        <?= $_SESSION['error_message']; ?>
                    </div>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

        <div class="section-body">


            <div class="card">
                <div class="card-header">
                    <h4>Daftar PA/TA</h4>
                    <div class="card-header-action">
                        <a href="<?= BASE_URL ?>admin/upload_tpata.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Data
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="table-1">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Semester</th>
                                    <th>Periode Wisuda</th>
                                    <th>User</th>
                                    <th>Jabatan</th>
                                    <th>Jumlah Mahasiswa Prodi</th>
                                    <th>Jumlah Mahasiswa Bimbingan</th>
                                    <th>Prodi</th>
                                    <th>Jumlah Penguji 1</th>
                                    <th>Jumlah Penguji 2</th>
                                    <th>Ketua Penguji</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['semester']) ?></td>
                                    <td><?= ucfirst($row['periode_wisuda']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['jbtn_pnt'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['jml_mhs_prodi'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['jml_mhs_bimbingan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['prodi']) ?></td>
                                    <td><?= $row['jml_pgji_1'] ?></td>
                                    <td><?= $row['jml_pgji_2'] ?></td>
                                    <td><?= $row['ketua_pgji'] ?></td>
                                    <td>
                                        <a href="../CRUD/edit_data/edit_tpata.php?id_tpt=<?= $row['id_tpt'] ?>"
                                           class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form action="../CRUD/hapus_data/hapus_tpata.php"
                                              method="POST"
                                              style="display:inline;">
                                            <input type="hidden" name="id_tpt" value="<?= $row['id_tpt'] ?>">
                                            <button class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Yakin hapus data ini?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php 
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>

<script src="<?= ASSETS_URL ?>js/page/modules-datatables.js"></script>
<script>
$(function () {
    $('#table-1').DataTable({
        pageLength: 10,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Data tidak ditemukan",
            info: "Halaman _PAGE_ dari _PAGES_",
            paginate: {
                next: "Berikutnya",
                previous: "Sebelumnya"
            }
        }
    });
});
</script>
