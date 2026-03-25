<?php
include '../../config.php';
?>

<table border="1">
    <tr>
        <th>No</th>
        <th>Semester</th>
        <th>Periode Wisuda</th>
        <th>Staff</th>
        <th>Panitia</th>
        <th>Jumlah Mahasiswa Prodi</th>
        <th>Jumlah Mahasiswa Bimbingan</th>
        <th>Prodi</th>
        <th>Jumlah Penguji 1</th>
        <th>Jumlah Penguji 2</th>
        <th>Ketua Penguji</th>
    </tr>
        <?php
        $no = 1;
        $query_tpata = mysqli_query($koneksi, "
    SELECT 
        tpata.*,
        p.jbtn_pnt,
        u.nama_user
    FROM t_transaksi_pa_ta tpata
    LEFT JOIN t_panitia p ON tpata.id_panitia = p.id_pnt
    LEFT JOIN t_user u ON tpata.id_user = u.id_user
");
        while ($tpata=mysqli_fetch_assoc($query_tpata)){
        ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= $tpata['semester']?></td>
        <td><?= $tpata['periode_wisuda']?></td>
        <td><?= $tpata['id_user']?></td>
        <td><?= $tpata['id_panitia']?></td>
        <td><?= $tpata['jml_mhs_prodi']?></td>
        <td><?= $tpata['jml_mhs_bimbingan']?></td>
        <td><?= $tpata['prodi']?></td>
        <td><?= $tpata['jml_pgji_1']?></td>
        <td><?= $tpata['jml_pgji_2']?></td>
        <td><?= $tpata['ketua_pgji']?></td>
        <?php
        }?>
    </tr>
</table>