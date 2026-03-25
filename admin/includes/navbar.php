<?php
$role_user = $_SESSION['role_user'] ?? '';

// Pastikan session sudah dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include konfigurasi database
require_once __DIR__ . '/../../config.php';

// Ambil data user dari database berdasarkan session
$user_id = $_SESSION['id_user'] ?? 0;
$user_data = null;

if ($user_id > 0) {
    $query = mysqli_query($koneksi, 
        "SELECT * FROM t_user WHERE id_user = '$user_id'"
    );
    if ($query && mysqli_num_rows($query) > 0) {
        $user_data = mysqli_fetch_assoc($query);
    }
}

// Jika tidak ada data user, gunakan data dari session sebagai fallback
$username = $_SESSION['username'] ?? 'Admin';
$nama_user = $user_data['nama_user'] ?? $username;
?>

<style>
    /* ========== LOGOUT MODAL STYLES ========== */
.logout-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.logout-modal-overlay.active {
    display: flex;
    opacity: 1;
}

.logout-modal {
    background: var(--white);
    border-radius: 20px;
    box-shadow: var(--shadow-xl);
    width: 90%;
    max-width: 400px;
    overflow: hidden;
    transform: translateY(-20px);
    transition: transform 0.3s ease;
    border: 1px solid var(--border);
}

.logout-modal-overlay.active .logout-modal {
    transform: translateY(0);
}

.logout-modal-header {
    padding: 2rem 2rem 1rem;
    text-align: center;
    background: linear-gradient(135deg, rgba(0, 61, 122, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
}


.logout-modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
}

.logout-modal-subtitle {
    color: var(--accent);
    font-size: 0.95rem;
    line-height: 1.5;
}

.logout-modal-body {
    padding: 2rem;
    text-align: center;
}

.logout-modal-message {
    font-size: 1rem;
    color: var(--primary-dark);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.logout-modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.logout-modal-btn {
    padding: 0.75rem 2rem;
    border-radius: 999px;
    font-weight: 500;
    font-size: 0.9375rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.logout-modal-btn i {
    margin-right: 8px;
    font-size: 0.9rem;
}

.logout-modal-btn-cancel {
    background: linear-gradient(135deg, var(--light-gray) 0%, #f0f9ff 100%);
    color: var(--primary);
    border: 1px solid var(--border);
}

.logout-modal-btn-cancel:hover {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.logout-modal-btn-confirm {
    background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
    color: var(--white);
}

.logout-modal-btn-confirm:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* ========== RESPONSIVE STYLES ========== */
@media (max-width: 768px) {
    .logout-modal {
        width: 95%;
        margin: 1rem;
    }
    
    .logout-modal-header {
        padding: 1.5rem 1.5rem 1rem;
    }
    
    .logout-modal-title {
        font-size: 1.35rem;
    }
    
    .logout-modal-body {
        padding: 1.75rem;
    }
    
    .logout-modal-actions {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .logout-modal-btn {
        width: 100%;
        min-width: auto;
        padding: 0.75rem 1.5rem;
    }
}

@media (max-width: 576px) {
    .logout-modal {
        width: calc(100% - 30px);
        margin: 0 15px;
    }
    
    .logout-modal-header {
        padding: 1.25rem 1.25rem 0.75rem;
    }
    
    
    .logout-modal-title {
        font-size: 1.25rem;
    }
    
    .logout-modal-body {
        padding: 1.5rem;
    }
    
    .logout-modal-message {
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 480px) {
    .logout-modal-header {
        padding: 1rem 1rem 0.5rem;
    }
    
    
    .logout-modal-title {
        font-size: 1.15rem;
        margin-bottom: 0.25rem;
    }
    
    .logout-modal-subtitle {
        font-size: 0.85rem;
    }
    
    .logout-modal-body {
        padding: 1.25rem;
    }
    
    .logout-modal-message {
        font-size: 0.9rem;
        margin-bottom: 1.25rem;
    }
}
</style>

<!-- Notifikasi Logout Modal -->
<div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal">
        <div class="logout-modal-header">
            <h3 class="logout-modal-title">Logout Confirmation</h3>
            <p class="logout-modal-subtitle">Confirm your action</p>
        </div>
        <div class="logout-modal-body">
            <p class="logout-modal-message">
                Apakah Anda yakin ingin logout dari sistem? 
                Anda akan dialihkan ke halaman login.
            </p>
            <div class="logout-modal-actions">
                <button class="logout-modal-btn logout-modal-btn-cancel" onclick="hideLogoutModal()">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <a href="<?= BASE_URL ?>logout.php" class="logout-modal-btn logout-modal-btn-confirm">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</div>

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
        <!-- User Profile -->
        <li class="dropdown">
            <a href="#" data-toggle="dropdown"
            class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <div class="d-sm-none d-lg-inline-block">
                    <span class="font-weight-bold">
                        <?= htmlspecialchars($nama_user); ?>
                    </span>
                </div>
            </a>

            <div class="dropdown-menu dropdown-menu-right" style="width: 280px;">
                <div class="dropdown-header text-center">
                    <h6 class="text-truncate mb-0">
                        <?= htmlspecialchars($nama_user); ?>
                    </h6>
                    <small class="text-muted">
                        <?= htmlspecialchars($role_user) ?>
                    </small>

                    <?php if ($user_data): ?>
                        <small class="d-block text-muted mt-1">
                            <i class="fas fa-id-card mr-1"></i>
                            <?= htmlspecialchars($user_data['npp_user'] ?? '') ?>
                        </small>
                    <?php endif; ?>
                </div>

                <div class="dropdown-divider"></div>

                <a href="<?= BASE_URL ?>admin/profile.php" class="dropdown-item">
                    <i class="far fa-user text-info"></i> Profile
                </a>

                <a href="<?= BASE_URL ?>admin/settings.php" class="dropdown-item">
                    <i class="fas fa-cog text-warning"></i> Settings
                </a>

                <div class="dropdown-divider"></div>

                <a href="#"
                   class="dropdown-item has-icon text-danger"
                   onclick="showLogoutModal(event)">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </li>
    </ul>
</nav>

<script>
// Fungsi untuk menampilkan modal logout
function showLogoutModal(event) {
    event.preventDefault();
    const modal = document.getElementById('logoutModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Fungsi untuk menyembunyikan modal logout
function hideLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Tutup modal saat klik di luar area modal
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideLogoutModal();
    }
});

// Tutup modal dengan tombol ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideLogoutModal();
    }
});
</script>