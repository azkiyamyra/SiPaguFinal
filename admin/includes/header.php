<?php
/**
 * HEADER TEMPLATE - SiPagu (FINAL)
 * Lokasi: admin/includes/header.php
 * HANYA berisi <head> dan pembuka <body>
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>SiPagu - Sistem Pengelolaan Keuangan</title>

    <!-- ================= GENERAL CSS ================= -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/fontawesome/css/all.min.css">

    <!-- ================= CSS LIBRARIES ================= -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/jqvmap/dist/jqvmap.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/summernote/summernote-bs4.css">

    <!-- ================= TEMPLATE CSS ================= -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/components.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/custom.css">

    <!-- ================= DATATABLES CSS ================= -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/datatables/datatables.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">

        <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo asset_url('favicon/favicon-96x96.png'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_url('favicon/favicon.svg'); ?>">
    <link rel="shortcut icon" href="<?php echo asset_url('favicon/favicon.ico'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo asset_url('favicon/apple-touch-icon.png'); ?>">
    <meta name="apple-mobile-web-app-title" content="SiPagu">
    <link rel="manifest" href="<?php echo asset_url('favicon/site.webmanifest'); ?>">
</head>
<body>
<div id="app">
    <div class="main-wrapper main-wrapper-1">
        <div class="navbar-bg"></div>