<?php
session_start();

if (!isset($_SESSION['status_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: ../login.php?pesan=belum_login");
    exit;
}

?>