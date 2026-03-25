<?php
include '../../../config.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query = mysqli_query($koneksi, "
        SELECT 
            tpata.*,
            p.jbtn_pnt,
            p.honor_std,
            p.honor_p1,
            p.honor_p2,
            u.nama_user,
            u.npp_user
        FROM t_transaksi_pa_ta tpata
        LEFT JOIN t_panitia p ON tpata.id_panitia = p.id_pnt
        LEFT JOIN t_user u ON tpata.id_user = u.id_user
        WHERE tpata.id_tpt = '$id'
    ");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        $bulan_indonesia = [
            'januari' => 'Januari', 'februari' => 'Februari', 'maret' => 'Maret',
            'april' => 'April', 'mei' => 'Mei', 'juni' => 'Juni',
            'juli' => 'Juli', 'agustus' => 'Agustus', 'september' => 'September',
            'oktober' => 'Oktober', 'november' => 'November', 'desember' => 'Desember'
        ];
        $periode_indo = $bulan_indonesia[$data['periode_wisuda']] ?? ucfirst($data['periode_wisuda']);
        
        $semester = $data['semester'];
        $tahun = substr($semester, 0, 4);
        $kode = substr($semester, -1);
        $semester_label = $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
        ?>
        <div class="up-detail-view">
            <table class="up-table" style="border: none;">
                <tr>
                    <th width="180">ID Transaksi</th>
                    <td>: <strong><?= $data['id_tpt'] ?></strong></td>
                </tr>
                <tr>
                    <th>Semester</th>
                    <td>: <?= $semester_label ?></td>
                </tr>
                <tr>
                    <th>Periode Wisuda</th>
                    <td>: <?= $periode_indo ?></td>
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
                    <th>Panitia</th>
                    <td>: <?= htmlspecialchars($data['jbtn_pnt'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>Program Studi</th>
                    <td>: <?= htmlspecialchars($data['prodi'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>Jumlah Mhs Prodi</th>
                    <td>: <?= $data['jml_mhs_prodi'] ?> orang</td>
                </tr>
                <tr>
                    <th>Jumlah Mhs Bimbingan</th>
                    <td>: <?= $data['jml_mhs_bimbingan'] ?> orang</td>
                </tr>
                <tr>
                    <th>Jumlah Penguji 1</th>
                    <td>: <?= $data['jml_pgji_1'] ?> orang</td>
                </tr>
                <tr>
                    <th>Jumlah Penguji 2</th>
                    <td>: <?= $data['jml_pgji_2'] ?> orang</td>
                </tr>
                <tr>
                    <th>Ketua Penguji</th>
                    <td>: <?= htmlspecialchars($data['ketua_pgji'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>Honor Standar Panitia</th>
                    <td>: Rp <?= number_format($data['honor_std'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Honor P1</th>
                    <td>: Rp <?= number_format($data['honor_p1'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Honor P2</th>
                    <td>: Rp <?= number_format($data['honor_p2'] ?? 0, 0, ',', '.') ?></td>
                </tr>
            </table>
        </div>
        <?php
    } else {
        echo '<p class="text-center text-muted">Data tidak ditemukan</p>';
    }
}
?>