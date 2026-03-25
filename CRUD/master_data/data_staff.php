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
        $staff = mysqli_query($koneksi, "select * from t_user where role_user='staff'");
        while ($stf=mysqli_fetch_assoc($staff)){
        ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= $stf['npp_user']?></td>
        <td><?= $stf['nik_user']?></td>
        <td><?= $stf['npwp_user']?></td>
        <td><?= $stf['norek_user']?></td>
        <td><?= $stf['nama_user']?></td>
        <td><?= $stf['nohp_user']?></td>
        <td><?= $stf['role_user']?></td>
        <?php
        }?>
    </tr>
</table>