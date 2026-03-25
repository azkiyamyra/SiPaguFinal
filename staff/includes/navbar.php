<?php
/**
 * NAVBAR TEMPLATE - SiPagu Staff
 * Lokasi: staff/includes/navbar.php
 */
?>
<nav class="navbar navbar-expand-lg main-navbar">
    <form class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li>
                <a href="#" data-toggle="sidebar" class="nav-link nav-link-lg">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li>
                <a href="#" data-toggle="search" class="nav-link nav-link-lg d-sm-none">
                    <i class="fas fa-search"></i>
                </a>
            </li>
        </ul>
    </form>

    <ul class="navbar-nav navbar-right">
        <li class="dropdown">
            <a href="#" data-toggle="dropdown"
               class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <img alt="image" src="<?= ASSETS_URL ?>/img/avatar/avatar-1.png"
                     class="rounded-circle mr-1">
                <div class="d-sm-none d-lg-inline-block">
                    Hi, <?= htmlspecialchars($_SESSION['nama_user'] ?? 'Staff'); ?>
                </div>
            </a>
           
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-title">
                    Logged in as Staff
                </div>
                <a href="#" class="dropdown-item has-icon">
                    <i class="far fa-user"></i> Profile
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= BASE_URL ?>logout.php" class="dropdown-item has-icon text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </li>
    </ul>
</nav>