<?php
session_start();
require_once __DIR__ . '/config.php';

$npp_user = mysqli_real_escape_string($koneksi, $_POST['npp_user']);
$pw_input = $_POST['pw_user'];

// Ambil user berdasarkan NPP
$query = mysqli_query($koneksi,
    "SELECT * FROM t_user WHERE npp_user='$npp_user' LIMIT 1"
);

if (mysqli_num_rows($query) === 1) {

    $row = mysqli_fetch_assoc($query);

    // Verifikasi password
    if (password_verify($pw_input, $row['pw_user'])) {

        // SESSION
        $_SESSION['id_user']      = $row['id_user'];
        $_SESSION['npp_user']     = $row['npp_user'];
        $_SESSION['nik_user']     = $row['nik_user'];
        $_SESSION['npwp_user']    = $row['npwp_user'];
        $_SESSION['norek_user']   = $row['norek_user'];
        $_SESSION['nama_user']    = $row['nama_user'];
        $_SESSION['nohp_user']    = $row['nohp_user'];
        $_SESSION['role_user']    = $row['role_user'];
        $_SESSION['honor_persks'] = $row['honor_persks'];
        $_SESSION['status_user']  = 'login';

        // REMEMBER ME
        if (isset($_POST['remember'])) {
            $token = bin2hex(random_bytes(32));

            mysqli_query($koneksi,
                "UPDATE t_user 
                 SET remember_token='$token' 
                 WHERE id_user='{$row['id_user']}'"
            );

            setcookie(
                'remember_token',
                $token,
                time() + (60 * 60 * 24 * 7),
                '/',
                '',
                false,
                true
            );
        }

        // REDIRECT
        switch ($row['role_user']) {
            case 'admin':
                header("Location: admin/index.php");
                break;
            case 'koordinator':
                header("Location: koordinator/index.php");
                break;
            case 'staff':
                header("Location: staff/index.php");
                break;
            default:
                header("Location: index.php");
        }
        exit;

    } else {
        // NPP ditemukan tapi password salah
        header("Location: login.php?pesan=password_salah");
        exit;
    }

} else {
    // NPP tidak ditemukan di database
    header("Location: login.php?pesan=npp_salah");
    exit;
}