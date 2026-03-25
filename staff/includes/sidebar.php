<?php
/**
 * SIDEBAR TEMPLATE - SiPagu Staff
 * Lokasi: staff/includes/sidebar.php
 */

// Ambil semester aktif dari session atau database
$semester_aktif = '20262'; // Default
?>

<!-- Sidebar -->
<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">

        <!-- Brand Logo Full -->
        <div class="sidebar-brand">
            <a href="<?= BASE_URL ?>staff/index.php">
                <img src="<?= ASSETS_URL ?>/img/logoSiPagu.png" alt="Logo SiPagu"
                     style="max-height: 40px; max-width: 150px; object-fit: contain;">
            </a>
        </div>

        <!-- Brand Logo Small (Mini Sidebar) -->
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="<?= BASE_URL ?>staff/index.php">
                <img src="<?= ASSETS_URL ?>/img/logoSiPagu.png" alt="Logo SiPagu"
                     style="max-height: 30px; max-width: 40px; object-fit: contain;">
            </a>
        </div>

        <!-- User Info -->
        <div class="user-info text-center py-3 border-bottom">
            <img alt="image" src="<?= ASSETS_URL ?>/img/avatar/avatar-1.png"
                 class="rounded-circle mb-2" width="80">
            <h6 class="font-weight-bold"><?= htmlspecialchars($_SESSION['nama_user'] ?? 'Staff') ?></h6>
            <span class="badge badge-primary">Staff</span>
            <div class="mt-2 small">
                <i class="fas fa-book-open"></i> Semester Aktif: <?= $semester_aktif ?>
            </div>
        </div>

        <!-- Menu Items -->
        <ul class="sidebar-menu">
            
            <!-- ================= DASHBOARD ================= -->
            <li class="menu-header">Dashboard</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                <a href="<?= BASE_URL ?>staff/index.php" class="nav-link">
                    <i class="fas fa-fire"></i><span>Dashboard</span>
                </a>
            </li>

            <!-- ================= HONOR ================= -->
            <li class="menu-header">Honor</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'riwayat_honor.php' ? 'active' : ''; ?>">
                <a href="<?= BASE_URL ?>staff/riwayat_honor.php" class="nav-link">
                    <i class="fas fa-history"></i><span>Riwayat Honor</span>
                </a>
            </li>

            <!-- ================= PROFILE ================= -->
            <li class="menu-header">Akun</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-user"></i><span>Profil Saya</span>
                </a>
            </li>
        </ul>

        <!-- ================= FOOTER SIDEBAR ================= -->
        <div class="sidebar-footer mt-4 mb-4 p-3">
            <form action="<?= BASE_URL ?>logout.php" method="POST" class="w-100">
                <button type="submit" name="logout" class="btn btn-danger btn-lg btn-block btn-icon-split">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>
</div>

<style>
/* Main sidebar wrapper with flexbox */
#sidebar-wrapper {
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
}

/* Sidebar menu with scroll if needed */
.sidebar-menu {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    min-height: 0;
    max-height: calc(100vh - 250px);
    padding-bottom: 10px;
}

/* Custom scrollbar */
.sidebar-menu::-webkit-scrollbar {
    width: 4px;
}

.sidebar-menu::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

/* Footer positioning */
.sidebar-footer {
    flex-shrink: 0;
    position: sticky;
    bottom: 0;
    background: #fff;
    z-index: 10;
    border-top: 1px solid #f0f0f0;
}

/* User info styling */
.user-info {
    background: #f8f9fc;
    margin-bottom: 15px;
}

.btn-icon-split {
    padding: 10px 15px;
    border-radius: 6px;
    transition: all 0.3s ease;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-icon-split:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style>