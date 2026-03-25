<?php
include '../../../config.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query = mysqli_query($koneksi, "
        SELECT 
            tu.*,
            p.jbtn_pnt,
            p.honor_std,
            p.honor_p1,
            p.honor_p2,
            u.nama_user,
            u.npp_user
        FROM t_transaksi_ujian tu
        LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
        LEFT JOIN t_user u ON tu.id_user = u.id_user
        WHERE tu.id_tu = '$id'
    ");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        $semester = $data['semester'];
        $tahun = substr($semester, 0, 4);
        $kode = substr($semester, -1);
        $semester_label = $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
        
        // Hitung total honor (estimasi berdasarkan honor panitia)
        $total_honor_panitia = $data['honor_std'] ?? 0;
        $total_honor_pengawas = ($data['jml_pgws_pagi'] + $data['jml_pgws_sore']) * ($data['honor_p1'] ?? 0);
        $total_honor_koordinator = ($data['jml_koor_pagi'] + $data['jml_koor_sore']) * ($data['honor_p2'] ?? 0);
        $total_honor = $total_honor_panitia + $total_honor_pengawas + $total_honor_koordinator;
        ?>
        <div class="up-detail-view">
            <table class="up-table" style="border: none;">
                <tr>
                    <th width="180">ID Transaksi</th>
                    <td>: <strong><?= $data['id_tu'] ?></strong></td>
                </tr>
                <tr>
                    <th>Semester</th>
                    <td>: <?= $semester_label ?></td>
                </tr>
                <tr>
                    <th>Panitia</th>
                    <td>: <?= htmlspecialchars($data['jbtn_pnt'] ?: '-') ?></td>
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
                    <th>Jumlah Mhs Prodi</th>
                    <td>: <?= $data['jml_mhs_prodi'] ?> orang</td>
                </tr>
                <tr>
                    <th>Jumlah Mahasiswa</th>
                    <td>: <?= $data['jml_mhs'] ?> orang</td>
                </tr>
                <tr>
                    <th>Jumlah Koreksi</th>
                    <td>: <?= $data['jml_koreksi'] ?> berkas</td>
                </tr>
                <tr>
                    <th>Jumlah Mata Kuliah</th>
                    <td>: <?= $data['jml_matkul'] ?> MK</td>
                </tr>
                <tr>
                    <th>Pengawas Pagi</th>
                    <td>: <?= $data['jml_pgws_pagi'] ?> orang</td>
                </tr>
                <tr>
                    <th>Pengawas Sore</th>
                    <td>: <?= $data['jml_pgws_sore'] ?> orang</td>
                </tr>
                <tr>
                    <th>Koordinator Pagi</th>
                    <td>: <?= $data['jml_koor_pagi'] ?> orang</td>
                </tr>
                <tr>
                    <th>Koordinator Sore</th>
                    <td>: <?= $data['jml_koor_sore'] ?> orang</td>
                </tr>
                <tr>
                    <th>Honor Standar Panitia</th>
                    <td>: Rp <?= number_format($data['honor_std'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Honor Pengawas (P1)</th>
                    <td>: Rp <?= number_format($data['honor_p1'] ?? 0, 0, ',', '.') ?> /orang</td>
                </tr>
                <tr>
                    <th>Honor Koordinator (P2)</th>
                    <td>: Rp <?= number_format($data['honor_p2'] ?? 0, 0, ',', '.') ?> /orang</td>
                </tr>
                <tr>
                    <th>Total Honor Panitia</th>
                    <td>: Rp <?= number_format($total_honor_panitia, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Total Honor Pengawas</th>
                    <td>: Rp <?= number_format($total_honor_pengawas, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Total Honor Koordinator</th>
                    <td>: Rp <?= number_format($total_honor_koordinator, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Total Honor Keseluruhan</th>
                    <td>: <span class="up-badge up-badge-success">Rp <?= number_format($total_honor, 0, ',', '.') ?></span></td>
                </tr>
            </table>
        </div>
        <?php
    } else {
        echo '<p class="text-center text-muted">Data tidak ditemukan</p>';
    }
}
?>