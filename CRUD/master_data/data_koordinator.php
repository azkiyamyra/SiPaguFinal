<?php
include '../../config.php';
?>

<table border="1">
    <tr>
        <th>No</th>
        <th>NPP</th>
        <th>NIK</th>
        <th>NPWP</th>
        <th>No Rekening</th>
        <th>Nama</th>
        <th>No Handphone</th>
        <th>Role</th>
    </tr>
        <?php
        $no = 1;
        $koor = mysqli_query($koneksi, "select * from t_user where role_user='koordinator'");
        while ($ko=mysqli_fetch_assoc($koor)){
        ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= $ko['npp_user']?></td>
        <td><?= $ko['nik_user']?></td>
        <td><?= $ko['npwp_user']?></td>
        <td><?= $ko['norek_user']?></td>
        <td><?= $ko['nama_user']?></td>
        <td><?= $ko['nohp_user']?></td>
        <td><?= $ko['role_user']?></td>
        <?php
        }?>
    </tr>
</table>