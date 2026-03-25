<?php
include '../../../config.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id' AND role_user = 'koordinator'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // Hitung jumlah mata kuliah yang diampu
        $matkul_query = mysqli_query($koneksi, "
            SELECT COUNT(*) as total_matkul, SUM(jml_mhs) as total_mhs
            FROM t_jadwal 
            WHERE id_user = '$id'
        ");
        $matkul_data = mysqli_fetch_assoc($matkul_query);
        
        // Hitung total honor dari transaksi
        $honor_query = mysqli_query($koneksi, "
            SELECT SUM(thd.sks_tempuh * u.honor_persks) as total_honor
            FROM t_transaksi_honor_dosen thd
            INNER JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
            INNER JOIN t_user u ON j.id_user = u.id_user
            WHERE j.id_user = '$id'
        ");
        $honor_data = mysqli_fetch_assoc($honor_query);
        ?>
        <div class="up-detail-view">
            <table class="up-table" style="border: none;">
                <tr>
                    <th width="150">ID User</th>
                    <td>: <strong><?= $data['id_user'] ?></strong></td>
                </tr>
                <tr>
                    <th>NPP</th>
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
                    <td>: <span class="up-badge up-badge-koordinator"><?= ucfirst($data['role_user']) ?></span></td>
                </tr>
                <tr>
                    <th>Mata Kuliah Diampu</th>
                    <td>: <?= $matkul_data['total_matkul'] ?? 0 ?> mata kuliah</td>
                </tr>
                <tr>
                    <th>Total Mahasiswa</th>
                    <td>: <?= $matkul_data['total_mhs'] ?? 0 ?> mahasiswa</td>
                </tr>
                <tr>
                    <th>Total Honor</th>
                    <td>: Rp <?= number_format($honor_data['total_honor'] ?? 0, 0, ',', '.') ?></td>
                </tr>
            </table>
        </div>
        <?php
    } else {
        echo '<p class="text-center text-muted">Data tidak ditemukan</p>';
    }
}
?>