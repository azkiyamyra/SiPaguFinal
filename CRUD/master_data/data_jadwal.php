<?php
include '../../config.php';
?>

<table border="1">
    <tr>
        <th>No</th>
        <th>Semester</th>
        <th>Kode Mata Kuliah</th>
        <th>Nama Mata Kuliah</th>
        <th>Staff</th>
        <th>Jumlah Mahasiswa</th>
    </tr>
        <?php
        $no = 1;
        $jadwal = mysqli_query($koneksi, "select * from t_jadwal");
        while ($jdw=mysqli_fetch_assoc($jadwal)){
        ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= $jdw['semester']?></td>
        <td><?= $jdw['kode_matkul']?></td>
        <td><?= $jdw['nama_matkul']?></td>
        <td><?= $jdw['id_user']?></td>
        <td><?= $jdw['jml_mhs']?></td>
        <?php
        }?>
    </tr>
</table>