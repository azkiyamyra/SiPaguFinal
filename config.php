<?php
/**
 * =========================================
 * CONFIGURATION FILE - SiPagu (FINAL)
 * Lokasi: ROOT PROJECT (SiPagu/config.php)
 * =========================================
 */

/* ================= TIMEZONE ================= */
date_default_timezone_set('Asia/Jakarta');

/* ================= DATABASE ================= */
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_sistem_honor_udinus';

$koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

/* ================= BASE URL ================= */
define('BASE_URL', 'http://localhost/SiPagu/');
define('ASSETS_URL', BASE_URL . 'assets/');

/* ================= SERVER PATH ================= */
define('ROOT_PATH', __DIR__ . '/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('LAYOUTS_PATH', ROOT_PATH . 'layouts/');
define('AUTH_PATH', ROOT_PATH . 'auth/');

/* ================= ERROR REPORTING ================= */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ================= HELPER FUNCTIONS ================= */

/**
 * Asset URL helper
 */
function asset_url($path) {
    return ASSETS_URL . ltrim($path, '/');
}

/**
 * Include layout
 */
function include_layout($filename) {
    $filepath = LAYOUTS_PATH . $filename;
    if (file_exists($filepath)) {
        include $filepath;
    } else {
        echo "<!-- Layout tidak ditemukan: {$filename} -->";
    }
}

/**
 * Sanitize input
 */
function sanitize($data) {
    global $koneksi;
    return mysqli_real_escape_string($koneksi, htmlspecialchars(trim($data)));
}

/**
 * Redirect helper
 */
function redirect($path) {
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}