<?php
include '../../../config.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query = mysqli_query($koneksi, "SELECT * FROM t_panitia WHERE id_pnt = '$id'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // Hitung jumlah transaksi ujian yang menggunakan panitia ini
        $transaksi_ujian = mysqli_fetch_assoc(mysqli_query($koneksi, "
            SELECT COUNT(*) as total, SUM(jml_mhs) as total_mhs
            FROM t_transaksi_ujian 
            WHERE id_panitia = '$id'
        "));
        
        // Hitung jumlah transaksi PA/TA yang menggunakan panitia ini
        $transaksi_pata = mysqli_fetch_assoc(mysqli_query($koneksi, "
            SELECT COUNT(*) as total, SUM(jml_mhs_bimbingan) as total_bimbingan
            FROM t_transaksi_pa_ta 
            WHERE id_panitia = '$id'
        "));
        ?>
        <div class="up-detail-view">
            <table class="up-table" style="border: none;">
                <tr>
                    <th width="150">ID Panitia</th>
                    <td>: <strong><?= $data['id_pnt'] ?></strong></td>
                </tr>
                <tr>
                    <th>Jabatan</th>
                    <td>: <strong><?= htmlspecialchars($data['jbtn_pnt']) ?></strong></td>
                </tr>
                <tr>
                    <th>Honor Standar</th>
                    <td>: Rp <?= number_format($data['honor_std'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Honor P1</th>
                    <td>: Rp <?= number_format($data['honor_p1'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Honor P2</th>
                    <td>: Rp <?= number_format($data['honor_p2'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Total Transaksi Ujian</th>
                    <td>: <?= $transaksi_ujian['total'] ?? 0 ?> transaksi</td>
                </tr>
                <tr>
                    <th>Total Mahasiswa Ujian</th>
                    <td>: <?= $transaksi_ujian['total_mhs'] ?? 0 ?> mahasiswa</td>
                </tr>
                <tr>
                    <th>Total Transaksi PA/TA</th>
                    <td>: <?= $transaksi_pata['total'] ?? 0 ?> transaksi</td>
                </tr>
                <tr>
                    <th>Total Bimbingan PA/TA</th>
                    <td>: <?= $transaksi_pata['total_bimbingan'] ?? 0 ?> mahasiswa</td>
                </tr>
            </table>
        </div>
        <?php
    } else {
        echo '<p class="text-center text-muted">Data tidak ditemukan</p>';
    }
}
?>