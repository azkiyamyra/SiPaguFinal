<?php
include '../../../config.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id' AND role_user = 'admin'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        ?>
        <div class="up-detail-view">
            <table class="up-table" style="border: none;">
                <tr>
                    <th width="120">NPP</th>
                    <td>: <strong><?= htmlspecialchars($data['npp_user']) ?></strong></td>
                </tr>
                <tr>
                    <th>NIK</th>
                    <td>: <?= htmlspecialchars($data['nik_user']) ?: '-' ?></td>
                </tr>
                <tr>
                    <th>NPWP</th>
                    <td>: <?= htmlspecialchars($data['npwp_user']) ?: '-' ?></td>
                </tr>
                <tr>
                    <th>No Rekening</th>
                    <td>: <?= htmlspecialchars($data['norek_user']) ?: '-' ?></td>
                </tr>
                <tr>
                    <th>Nama Lengkap</th>
                    <td>: <?= htmlspecialchars($data['nama_user']) ?></td>
                </tr>
                <tr>
                    <th>No Handphone</th>
                    <td>: <?= htmlspecialchars($data['nohp_user']) ?: '-' ?></td>
                </tr>
                <tr>
                    <th>Honor per SKS</th>
                    <td>: Rp <?= number_format($data['honor_persks'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Role</th>
                    <td>: <span class="up-badge up-badge-admin"><?= ucfirst($data['role_user']) ?></span></td>
                </tr>
            </table>
        </div>
        <?php
    } else {
        echo '<p class="text-center text-muted">Data tidak ditemukan</p>';
    }
}
?>