<?php
/**
 * DATA TRANSAKSI UJIAN - SiPagu
 * Halaman LIST data transaksi ujian
 * Lokasi: admin/transaksi_ujian.php
 */

// Include required files
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$page_title = "Data Transaksi Ujian";

// Ambil data
$query = mysqli_query($koneksi, "
    SELECT tu.*, u.nama_user, p.jbtn_pnt 
    FROM t_transaksi_ujian tu
    LEFT JOIN t_user u ON tu.id_user = u.id_user
    LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
    ORDER BY tu.id_tu DESC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1>Data Transaksi Ujian</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active">
                <a href="<?= BASE_URL ?>admin/index.php">Dashboard</a>
            </div>
            <div class="breadcrumb-item">Master Data</div>
            <div class="breadcrumb-item">Transaksi Ujian</div>
        </div>
    </div>

    <div class="section-body">

        <!-- FLASH MESSAGE -->
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

        <div class="card">
            <div class="card-header">
                <h4>Daftar Transaksi Ujian</h4>
                <div class="card-header-action">
                    <a href="<?= BASE_URL ?>admin/upload_tu.php" class="btn btn-primary">
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
                                <th>Jabatan</th>
                                <th>User</th>
                                <th>Jumlah Mahasiswa Prodi</th>
                                <th>Jumlah Mahasiswa</th>
                                <th>Jumlah Koreksi</th>
                                <th>Jumlah Mata Kuliah</th>
                                <th>Jumlah Pengawas Pagi</th>
                                <th>Jumlah Pengawas Sore</th>
                                <th>Jumlah Koordinator Pagi</th>
                                <th>Jumlah Koordinator Sore</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['semester']) ?></td>
                                <td><?= htmlspecialchars($row['jbtn_pnt'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                                <td><?= $row['jml_mhs_prodi'] ?></td>
                                <td><?= $row['jml_mhs'] ?></td>
                                <td><?= $row['jml_koreksi'] ?></td>
                                <td><?= $row['jml_matkul'] ?></td>
                                <td><?= $row['jml_pgws_pagi'] ?></td>
                                <td><?= $row['jml_pgws_sore'] ?></td>
                                <td><?= $row['jml_koor_pagi'] ?></td>
                                <td><?= $row['jml_koor_sore'] ?></td>
                                <td>
                                    <a href="../CRUD/edit_data/edit_tu.php?id_tu=<?= $row['id_tu'] ?>"
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="../CRUD/hapus_data/hapus_tu.php"
                                          method="POST"
                                          style="display:inline;">
                                        <input type="hidden" name="id_tu" value="<?= $row['id_tu'] ?>">
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
