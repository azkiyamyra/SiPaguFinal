<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['status_user']) && isset($_COOKIE['remember_token'])) {

    $token = mysqli_real_escape_string($koneksi, $_COOKIE['remember_token']);

    $q = mysqli_query(
        $koneksi,
        "SELECT * FROM t_user WHERE remember_token='$token' LIMIT 1"
    );

    if (mysqli_num_rows($q) > 0) {
        $user = mysqli_fetch_assoc($q);

        $_SESSION['id_user']     = $user['id_user'];
        $_SESSION['npp_user']    = $user['npp_user'];
        $_SESSION['role_user']   = $user['role_user'];
        $_SESSION['status_user'] = 'login';
    }
}

if (
    !isset($_SESSION['status_user']) ||
    $_SESSION['role_user'] !== 'admin'
) {
    header("Location: ../login.php?pesan=belum_login");
    exit;
}
