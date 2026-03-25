<?php
include '../../config.php';
?>

<table border="1">
    <tr>
        <th>No</th>
        <th>Semester</th>
        <th>Panitia</th>
        <th>Staff</th>
        <th>Jumlah Mahasiswa Prodi</th>
        <th>Jumlah Mahasiswa</th>
        <th>Jumlah Koreksi</th>
        <th>Jumlah Mata Kuliah</th>
        <th>Jumlah Pengawas Pagi</th>
        <th>Jumlah Pengawas Sore</th>
        <th>Jumlah Koordinator Pagi</th>
        <th>Jumlah Koordinator Sore</th>
    </tr>

<?php
$no = 1;
$query_tu = mysqli_query($koneksi, "
    SELECT 
        tu.*,
        p.jbtn_pnt,
        u.nama_user
    FROM t_transaksi_ujian tu
    LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
    LEFT JOIN t_user u ON tu.id_user = u.id_user
");

while ($tu = mysqli_fetch_assoc($query_tu)) {
?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= $tu['semester'] ?></td>
        <td><?= $tu['jbtn_pnt'] ?></td>
        <td><?= $tu['nama_user'] ?></td>
        <td><?= $tu['jml_mhs_prodi'] ?></td>
        <td><?= $tu['jml_mhs'] ?></td>
        <td><?= $tu['jml_koreksi'] ?></td>
        <td><?= $tu['jml_matkul'] ?></td>
        <td><?= $tu['jml_pgws_pagi'] ?></td>
        <td><?= $tu['jml_pgws_sore'] ?></td>
        <td><?= $tu['jml_koor_pagi'] ?></td>
        <td><?= $tu['jml_koor_sore'] ?></td>
    </tr>
<?php } ?>
</table>
