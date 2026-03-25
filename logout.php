<?php
session_start();
include 'config.php';

if (isset($_SESSION['id_user'])) {
    mysqli_query($koneksi,
        "UPDATE t_user 
         SET remember_token=NULL 
         WHERE id_user='{$_SESSION['id_user']}'"
    );
}

// hapus cookie
setcookie('remember_token', '', time() - 3600, '/');

// hapus session
session_destroy();

header("Location: login.php");
// header("Location: login.php?pesan=logout");
exit;
?>