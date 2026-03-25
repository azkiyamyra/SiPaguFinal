<?php
/**
 * DATA HONOR DOSEN - SiPagu
 * Halaman LIST data honor dosen
 * Lokasi: admin/honor_dosen.php
 */
// Include required files
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$page_title = "Data Honor Dosen";

// Ambil data
$query = mysqli_query($koneksi, "
    SELECT th.*, j.nama_matkul, j.kode_matkul, u.nama_user 
    FROM t_transaksi_honor_dosen th
    LEFT JOIN t_jadwal j ON th.id_jadwal = j.id_jdwl
    LEFT JOIN t_user u ON j.id_user = u.id_user
    ORDER BY th.id_thd DESC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1>Data Honor Dosen</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active">
                <a href="<?= BASE_URL ?>admin/index.php">Dashboard</a>
            </div>
            <div class="breadcrumb-item">Master Data</div>
            <div class="breadcrumb-item">Honor Dosen</div>
        </div>
    </div>

    <div class="section-body">

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_SESSION['success_message']; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>    
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>    

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $_SESSION['error']; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>    
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>    


        <div class="card">
            <div class="card-header">
                <h4>Daftar Honor Dosen</h4>
                <div class="card-header-action">
                    <a href="<?= BASE_URL ?>admin/hitung_honor.php" class="btn btn-primary">
                        <i class="fas fa-calculator"></i> Hitung Honor
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
                                <th>Bulan</th>
                                <th>Mata Kuliah</th>
                                <th>Dosen</th>
                                <th>Jml TM</th>
                                <th>SKS</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['semester']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($row['bulan'])) ?></td>
                    <td>
                        <?= htmlspecialchars($row['kode_matkul']) ?> - <?= htmlspecialchars($row['nama_matkul']) ?>
                    </td>
                                <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                                <td><?= $row['jml_tm'] ?></td>
                                <td><?= $row['sks_tempuh'] ?></td>
                                <td>
                                    <a href="../CRUD/edit_data/edit_thd.php?id_thd=<?= $row['id_thd'] ?>"
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="../CRUD/hapus_data/hapus_thd.php"
                                          method="POST"
                                          style="display:inline;">
                                        <input type="hidden" name="id_thd" value="<?= $row['id_thd'] ?>">
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
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
