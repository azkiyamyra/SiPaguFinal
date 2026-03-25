<?php
/**
 * DATA PANITIA - SiPagu
 * Lokasi: admin/panitia.php
 */

// Include required files
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$page_title = "Data Panitia";

// ambil data
$query = mysqli_query($koneksi, "SELECT * FROM t_panitia ORDER BY id_pnt DESC");

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/sidebar_admin.php';
?>

<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1>Data Panitia</h1>
    </div>

        <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_SESSION['success_message']; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>    
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>    

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $_SESSION['error_message']; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>    
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>    


    <div class="section-body">
        <div class="card">
            <div class="card-header">
                <h4>Daftar Panitia</h4>
                <div class="card-header-action">
                    <a href="<?= BASE_URL ?>admin/upload_panitia.php"
                       class="btn btn-primary">
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
                            <th>Jabatan</th>
                            <th>Honor Standar</th>
                            <th>Honor Periode 1</th>
                            <th>Honor Periode 2</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['jbtn_pnt']) ?></td>
                            <td>Rp <?= number_format($row['honor_std'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($row['honor_p1'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($row['honor_p2'], 0, ',', '.') ?></td>
                            <td>
                                <div class="btn-group">

                                    <!-- EDIT -->
                                    <a href="../CRUD/edit_data/edit_panitia.php?id_pnt=<?= $row['id_pnt'] ?>"
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- HAPUS -->
                                    <form action="../CRUD/hapus_data/hapus_panitia.php"
                                          method="POST"
                                          style="display:inline;">
                                        <input type="hidden" name="id_pnt"
                                               value="<?= $row['id_pnt'] ?>">
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Yakin hapus data ini?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>

                                </div>
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
