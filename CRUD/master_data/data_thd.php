<?php
include '../../config.php';
?>

<table border= "1">
<tr>
    <th>No</th>
    <th>Semester</th>
    <th>Bulan</th>
    <th>Dosen</th>
    <th>Jadwal</th>
    <th>Jumlah Tatap Muka</th>
    <th>SKS Tempuh</th>
</tr>

<?php
$no = 1;
$query_thd = mysqli_query($koneksi, "
    SELECT 
        thd.semester,
        thd.bulan,
        thd.jml_tm,
        thd.sks_tempuh,
        j.kode_matkul,
        j.nama_matkul,
        j.jml_mhs,
        u.nama_user AS nama_dosen
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user u ON j.id_user = u.id_user
");

while ($thd = mysqli_fetch_assoc($query_thd)) {
?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= $thd['semester'] ?></td>
    <td><?= $thd['bulan'] ?></td>
    <td><?= $thd['nama_dosen'] ?></td>
    <td>
        <?= $thd['kode_matkul'] ?> - <?= $thd['nama_matkul'] ?><br>
        <small>(<?= $thd['jml_mhs'] ?> mahasiswa)</small>
    </td>
    <td><?= $thd['jml_tm'] ?></td>
    <td><?= $thd['sks_tempuh'] ?></td>
</tr>
<?php } ?>

</table>