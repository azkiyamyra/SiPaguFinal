<?php
include '../../../config.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query = mysqli_query($koneksi, "
        SELECT 
            thd.*,
            j.kode_matkul,
            j.nama_matkul,
            j.jml_mhs,
            u.nama_user AS nama_dosen,
            u.npp_user,
            u.honor_persks
        FROM t_transaksi_honor_dosen thd
        LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
        LEFT JOIN t_user u ON j.id_user = u.id_user
        WHERE thd.id_thd = '$id'
    ");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        $bulan_indonesia = [
            'januari' => 'Januari', 'februari' => 'Februari', 'maret' => 'Maret',
            'april' => 'April', 'mei' => 'Mei', 'juni' => 'Juni',
            'juli' => 'Juli', 'agustus' => 'Agustus', 'september' => 'September',
            'oktober' => 'Oktober', 'november' => 'November', 'desember' => 'Desember'
        ];
        $bulan_indo = $bulan_indonesia[$data['bulan']] ?? ucfirst($data['bulan']);
        
        $semester = $data['semester'];
        $tahun = substr($semester, 0, 4);
        $kode = substr($semester, -1);
        $semester_label = $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
        
        $honor = $data['sks_tempuh'] * ($data['honor_persks'] ?? 0);
        ?>
        <div class="up-detail-view">
            <table class="up-table" style="border: none;">
                <tr>
                    <th width="150">ID Transaksi</th>
                    <td>: <strong><?= $data['id_thd'] ?></strong></td>
                </tr>
                <tr>
                    <th>Semester</th>
                    <td>: <?= $semester_label ?></td>
                </tr>
                <tr>
                    <th>Bulan</th>
                    <td>: <?= $bulan_indo ?></td>
                </tr>
                <tr>
                    <th>Dosen</th>
                    <td>: <?= htmlspecialchars($data['nama_dosen'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>NPP</th>
                    <td>: <?= htmlspecialchars($data['npp_user'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>Kode MK</th>
                    <td>: <strong><?= htmlspecialchars($data['kode_matkul'] ?: '-') ?></strong></td>
                </tr>
                <tr>
                    <th>Nama MK</th>
                    <td>: <?= htmlspecialchars($data['nama_matkul'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>Jumlah Mahasiswa</th>
                    <td>: <?= $data['jml_mhs'] ?? 0 ?> orang</td>
                </tr>
                <tr>
                    <th>Jumlah Tatap Muka</th>
                    <td>: <?= $data['jml_tm'] ?> kali</td>
                </tr>
                <tr>
                    <th>SKS Tempuh</th>
                    <td>: <?= $data['sks_tempuh'] ?> SKS</td>
                </tr>
                <tr>
                    <th>Honor per SKS</th>
                    <td>: Rp <?= number_format($data['honor_persks'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Total Honor</th>
                    <td>: <span class="up-badge up-badge-success">Rp <?= number_format($honor, 0, ',', '.') ?></span></td>
                </tr>
            </table>
        </div>
        <?php
    } else {
        echo '<p class="text-center text-muted">Data tidak ditemukan</p>';
    }
}
?>