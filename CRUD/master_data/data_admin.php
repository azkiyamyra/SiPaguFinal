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
        $admin = mysqli_query($koneksi, "select * from t_user where role_user='admin'");
        while ($adm=mysqli_fetch_assoc($admin)){
        ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= $adm['npp_user']?></td>
        <td><?= $adm['nik_user']?></td>
        <td><?= $adm['npwp_user']?></td>
        <td><?= $adm['norek_user']?></td>
        <td><?= $adm['nama_user']?></td>
        <td><?= $adm['nohp_user']?></td>
        <td><?= $adm['role_user']?></td>
        <?php
        }?>
    </tr>
</table>
