<?php
/**
 * HEADER TEMPLATE - SiPagu Staff
 * Lokasi: staff/includes/header.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>SiPagu - Staff Panel</title>

    <!-- ================= GENERAL CSS ================= -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/fontawesome/css/all.min.css">

    <!-- ================= CSS LIBRARIES ================= -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/jqvmap/dist/jqvmap.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/summernote/summernote-bs4.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/modules/bootstrap-daterangepicker/daterangepicker.css">

    <!-- ================= TEMPLATE CSS ================= -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/components.css">
    
    <style>
        /* Custom CSS untuk staff */
        .badge-status {
            padding: 8px 12px;
            border-radius: 30px;
            font-weight: 500;
        }
        
        .honor-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .honor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .summary-card {
            background: linear-gradient(135deg, #6777ef 0%, #003d7a 100%);
            color: white;
            border-radius: 15px;
        }
        
        .filter-section {
            background: #f8f9fc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e3e6f0;
        }
        
        .honor-section {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .honor-section h5 {
            color: #6777ef;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3e6f0;
        }
        
        .total-box {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            border-radius: 15px;
            padding: 20px;
        }
        
        .total-box h3 {
            color: #0f5132;
            font-weight: 700;
        }
        
        .btn-pdf {
            background: #dc3545;
            color: white;
            border-radius: 10px;
            padding: 8px 20px;
            transition: all 0.3s;
        }
        
        .btn-pdf:hover {
            background: #bb2d3b;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(220,53,69,0.3);
            color: white;
        }
        
        .info-profile {
            background: #f8f9fc;
            border-radius: 15px;
            padding: 20px;
            border-left: 4px solid #6777ef;
        }
    </style>
</head>
<body>
<div id="app">
    <div class="main-wrapper main-wrapper-1">
        <div class="navbar-bg"></div>