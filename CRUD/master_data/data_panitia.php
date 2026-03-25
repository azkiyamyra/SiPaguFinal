<?php
include '../../config.php';
?>

<table border="1">
    <tr>
        <th>No</th>
        <th>Jabatan Panitia</th>
        <th>Honor Standar</th>
        <th>Honor P1</th>
        <th>Honor P2</th>
    </tr>
        <?php
        $no = 1;
        $pnt = mysqli_query($koneksi, "select * from t_panitia");
        while ($pnt=mysqli_fetch_assoc($pnt)){
        ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= $pnt['jbtn_pnt']?></td>
        <td><?= $pnt['honor_std']?></td>
        <td><?= $pnt['honor_p1']?></td>
        <td><?= $pnt['honor_p2']?></td>
        <?php
        }?>
    </tr>
</table>