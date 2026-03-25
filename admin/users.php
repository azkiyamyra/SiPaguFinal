<?php
/**
 * DATA USER - SiPagu
 * Lokasi: admin/users.php
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config.php';

$page_title = "Data User";

// ambil data user
$query = mysqli_query($koneksi, "SELECT * FROM t_user ORDER BY id_user DESC");

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/sidebar_admin.php';
?>

<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1>Data User</h1>
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
                <h4>Daftar User</h4>
                <div class="card-header-action">
                    <a href="<?= BASE_URL ?>admin/upload_user.php" class="btn btn-primary">
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
                            <th>NPP</th>
                            <th>Nama</th>
                            <th>NIK</th>
                            <th>NPWP</th>
                            <th>No Rekening</th>
                            <th>No HP</th>
                            <th>Honor / SKS</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['npp_user']) ?></td>
                            <td><?= htmlspecialchars($row['nama_user']) ?></td>
                            <td><?= htmlspecialchars($row['nik_user']) ?></td>
                            <td><?= htmlspecialchars($row['npwp_user']) ?></td>
                            <td><?= htmlspecialchars($row['norek_user']) ?></td>
                            <td><?= htmlspecialchars($row['nohp_user']) ?></td>
                            <td><?= htmlspecialchars($row['honor_persks']) ?></td>
                            <td>
                                <span class="badge badge-<?= 
                                    $row['role_user'] === 'admin' ? 'danger' :
                                    ($row['role_user'] === 'koordinator' ? 'warning' : 'secondary')
                                ?>">
                                    <?= ucfirst($row['role_user']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">

                                    <!-- EDIT -->
                                    <a href="../CRUD/edit_data/edit_user.php?id_user=<?= $row['id_user'] ?>"
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- HAPUS -->
                                    <form action="../CRUD/hapus_data/hapus_user.php"
                                          method="POST"
                                          style="display:inline;">
                                        <input type="hidden" name="id_user"
                                               value="<?= $row['id_user'] ?>">
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
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/footer_scripts.php';
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