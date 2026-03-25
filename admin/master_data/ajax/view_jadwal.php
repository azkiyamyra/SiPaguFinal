<?php
include '../../../config.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query = mysqli_query($koneksi, "
        SELECT j.*, u.nama_user, u.npp_user 
        FROM t_jadwal j
        LEFT JOIN t_user u ON j.id_user = u.id_user
        WHERE j.id_jdwl = '$id'
    ");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // Hitung total honor berdasarkan t_transaksi_honor_dosen jika ada
        $total_sks = 0;
        $total_tm = 0;
        $total_honor = 0;
        
        $honor_query = mysqli_query($koneksi, "
            SELECT SUM(sks_tempuh) as total_sks, SUM(jml_tm) as total_tm 
            FROM t_transaksi_honor_dosen 
            WHERE id_jadwal = '$id'
        ");
        if ($honor_data = mysqli_fetch_assoc($honor_query)) {
            $total_sks = $honor_data['total_sks'] ?? 0;
            $total_tm = $honor_data['total_tm'] ?? 0;
        }
        
        $semester = $data['semester'];
        $tahun = substr($semester, 0, 4);
        $kode = substr($semester, -1);
        $semester_label = $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
        $badge_class = ($kode == '1') ? 'badge-ganjil' : 'badge-genap';
        ?>
        <div class="up-detail-view">
            <table class="up-table" style="border: none;">
                <tr>
                    <th width="150">ID Jadwal</th>
                    <td>: <strong><?= $data['id_jdwl'] ?></strong></td>
                </tr>
                <tr>
                    <th>Semester</th>
                    <td>: <span class="up-badge <?= $badge_class ?>"><?= $semester_label ?></span></td>
                </tr>
                <tr>
                    <th>Kode MK</th>
                    <td>: <strong><?= htmlspecialchars($data['kode_matkul']) ?></strong></td>
                </tr>
                <tr>
                    <th>Nama MK</th>
                    <td>: <?= htmlspecialchars($data['nama_matkul']) ?></td>
                </tr>
                <tr>
                    <th>Staff/Dosen</th>
                    <td>: <?= htmlspecialchars($data['nama_user'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>NPP</th>
                    <td>: <?= htmlspecialchars($data['npp_user'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>Jumlah Mahasiswa</th>
                    <td>: <?= $data['jml_mhs'] ?> orang</td>
                </tr>
                <tr>
                    <th>Total SKS Tempuh</th>
                    <td>: <?= $total_sks ?> SKS</td>
                </tr>
                <tr>
                    <th>Total Tatap Muka</th>
                    <td>: <?= $total_tm ?> kali</td>
                </tr>
            </table>
        </div>
        <?php
    } else {
        echo '<p class="text-center text-muted">Data tidak ditemukan</p>';
    }
}
?>