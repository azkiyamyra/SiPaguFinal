<?php
// =====================================================
// SETTINGS - SiPagu Universitas Dian Nuswantoro
// =====================================================
require_once __DIR__ . '/../config.php';

// Cek session dan role admin
if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') != 'admin') {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

// Ambil data user
$user_id = $_SESSION['id_user'];
$query = mysqli_query($koneksi, 
    "SELECT * FROM t_user WHERE id_user = '$user_id'"
);
$user = mysqli_fetch_assoc($query);

// Proses ubah password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verifikasi password saat ini menggunakan password_verify (bcrypt)
    if (!password_verify($current_password, $user['pw_user'])) {
        $error = "Password saat ini salah!";
    } elseif ($new_password != $confirm_password) {
        $error = "Password baru dan konfirmasi password tidak cocok!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password baru minimal 6 karakter!";
    } else {
        $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $new_password_hash_escaped = mysqli_real_escape_string($koneksi, $new_password_hash);
        $update_query = mysqli_query($koneksi,
            "UPDATE t_user SET pw_user = '$new_password_hash_escaped' WHERE id_user = '$user_id'"
        );
        
        if ($update_query) {
            $success = "Password berhasil diubah!";
            // Clear password fields
            $_POST['current_password'] = $_POST['new_password'] = $_POST['confirm_password'] = '';
        } else {
            $error = "Gagal mengubah password: " . mysqli_error($koneksi);
        }
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<?php include __DIR__ . '/includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <!-- Header -->
        <div class="section-header pt-4 pb-0">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h1 class="h3 font-weight-normal text-dark mb-1">Settings</h1>
                    <p class="text-muted mb-0">Pengaturan sistem dan akun</p>
                </div>
                <div class="text-muted">
                    <?php echo date('l, d F Y'); ?>
                </div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card card-simple">
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?= $success ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?= $error ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>

                            
                            <!-- Tab Content -->
                            <div class="tab-content" id="settingsTabContent">
                                <!-- Password Tab -->
                                <div class="tab-pane fade show active" id="password" role="tabpanel">
                                    <div class="row">
                                        <div class="col-lg-8">
                                            <h5 class="mb-4">Ubah Password</h5>
                                            <form method="POST" action="">
                                                <div class="form-group">
                                                    <label>Password Saat Ini *</label>
                                                    <div class="input-group">
                                                        <input type="password" name="current_password" 
                                                               class="form-control" required
                                                               value="<?= $_POST['current_password'] ?? '' ?>">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text toggle-password">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Password Baru *</label>
                                                    <div class="input-group">
                                                        <input type="password" name="new_password" 
                                                               class="form-control" required
                                                               value="<?= $_POST['new_password'] ?? '' ?>">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text toggle-password">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        Minimal 6 karakter, gunakan kombinasi huruf, angka, dan simbol
                                                    </small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Konfirmasi Password Baru *</label>
                                                    <div class="input-group">
                                                        <input type="password" name="confirm_password" 
                                                               class="form-control" required
                                                               value="<?= $_POST['confirm_password'] ?? '' ?>">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text toggle-password">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <button type="submit" name="change_password" 
                                                            class="btn btn-primary">
                                                        <i class="fas fa-save mr-1"></i> Simpan Password
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="password-tips">
                                                <h6><i class="fas fa-lightbulb text-warning mr-2"></i> Tips Password</h6>
                                                <ul class="list-unstyled">
                                                    <li class="mb-2">
                                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                                        Gunkan minimal 8 karakter
                                                    </li>
                                                    <li class="mb-2">
                                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                                        Kombinasikan huruf besar & kecil
                                                    </li>
                                                    <li class="mb-2">
                                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                                        Tambahkan angka (0-9)
                                                    </li>
                                                    <li class="mb-2">
                                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                                        Gunakan simbol (!@#$%^&*)
                                                    </li>
                                                    <li class="mb-2">
                                                        <i class="fas fa-times-circle text-danger mr-2"></i>
                                                        Hindari tanggal lahir
                                                    </li>
                                                    <li>
                                                        <i class="fas fa-times-circle text-danger mr-2"></i>
                                                        Jangan gunakan kata sandi umum
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- System Tab -->
                                <div class="tab-pane fade" id="system" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5 class="mb-4">Informasi Sistem</h5>
                                            
                                            <div class="system-info">
                                                <div class="info-item mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">Nama Aplikasi</span>
                                                        <span class="font-weight-medium">SiPagu UDINUS</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="info-item mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">Versi</span>
                                                        <span class="font-weight-medium">2.0.0</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="info-item mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">PHP Version</span>
                                                        <span class="font-weight-medium"><?= phpversion() ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="info-item mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">Database</span>
                                                        <span class="font-weight-medium">MySQL</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="info-item mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">Server Time</span>
                                                        <span class="font-weight-medium"><?= date('Y-m-d H:i:s') ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="info-item mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">Timezone</span>
                                                        <span class="font-weight-medium">Asia/Jakarta</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-4">
                                                <h6 class="mb-3">Maintenance</h6>
                                                <div class="d-flex">
                                                    <button class="btn btn-outline-primary mr-2">
                                                        <i class="fas fa-database mr-1"></i> Optimize DB
                                                    </button>
                                                    <button class="btn btn-outline-secondary">
                                                        <i class="fas fa-trash-alt mr-1"></i> Clear Cache
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h5 class="mb-4">Status Sistem</h5>
                                            
                                            <div class="status-cards">
                                                <div class="status-card mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="status-icon bg-success-soft text-success mr-3">
                                                            <i class="fas fa-database"></i>
                                                        </div>
                                                        <div class="status-content">
                                                            <h6 class="mb-0">Database</h6>
                                                            <p class="text-success mb-0">Normal <span class="badge badge-success ml-2">100%</span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="status-card mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="status-icon bg-info-soft text-info mr-3">
                                                            <i class="fas fa-server"></i>
                                                        </div>
                                                        <div class="status-content">
                                                            <h6 class="mb-0">Server</h6>
                                                            <p class="text-info mb-0">Stabil <span class="badge badge-info ml-2">99.8%</span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="status-card mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="status-icon bg-warning-soft text-warning mr-3">
                                                            <i class="fas fa-hdd"></i>
                                                        </div>
                                                        <div class="status-content">
                                                            <h6 class="mb-0">Storage</h6>
                                                            <p class="text-warning mb-0">65% Terpakai</p>
                                                        </div>
                                                        <div class="ml-auto">
                                                            <div class="progress-thin" style="width: 100px;">
                                                                <div class="progress-fill bg-warning" style="width: 65%"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="status-card mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="status-icon bg-primary-soft text-primary mr-3">
                                                            <i class="fas fa-users"></i>
                                                        </div>
                                                        <div class="status-content">
                                                            <h6 class="mb-0">Users Online</h6>
                                                            <p class="text-primary mb-0">5 Aktif</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- About Tab -->
                                <div class="tab-pane fade" id="about" role="tabpanel">
                                    <div class="text-center py-4">
                                        <div class="mb-4">
                                            <img src="<?= ASSETS_URL ?>/img/logoSiPagu.png" 
                                                 alt="SiPagu Logo" 
                                                 style="max-height: 80px;">
                                        </div>
                                        
                                        <h4 class="mb-2">SiPagu UDINUS</h4>
                                        <p class="text-muted mb-4">Sistem Pengelolaan Honor Dosen</p>
                                        
                                        <div class="row">
                                            <div class="col-md-8 mx-auto">
                                                <div class="about-info">
                                                    <div class="info-item mb-3">
                                                        <i class="fas fa-code text-primary mr-2"></i>
                                                        <span>Version: 2.0.0 (Stable)</span>
                                                    </div>
                                                    
                                                    <div class="info-item mb-3">
                                                        <i class="fas fa-calendar text-primary mr-2"></i>
                                                        <span>Release: January 2025</span>
                                                    </div>
                                                    
                                                    <div class="info-item mb-3">
                                                        <i class="fas fa-building text-primary mr-2"></i>
                                                        <span>Developer: Tim SI UDINUS</span>
                                                    </div>
                                                    
                                                    <div class="info-item mb-4">
                                                        <i class="fas fa-envelope text-primary mr-2"></i>
                                                        <span>support@sipagu.udinus.ac.id</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="license-info">
                                                    <h6 class="mb-3">Lisensi & Hak Cipta</h6>
                                                    <p class="text-muted small">
                                                        © 2025 Universitas Dian Nuswantoro. All rights reserved.<br>
                                                        Sistem ini dikembangkan khusus untuk penggunaan internal.
                                                    </p>
                                                </div>
                                                
                                                <div class="mt-4">
                                                    <button class="btn btn-outline-primary">
                                                        <i class="fas fa-download mr-1"></i> User Manual
                                                    </button>
                                                    <button class="btn btn-outline-secondary ml-2">
                                                        <i class="fas fa-question-circle mr-1"></i> Help
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<!-- End Main Content -->

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php include __DIR__ . '/includes/footer_scripts.php'; ?>

<style>
/* Settings Specific Styles */
.nav-tabs {
    border-bottom: 1px solid var(--border-color);
}

.nav-tabs .nav-link {
    border: none;
    color: var(--text-secondary);
    font-weight: 500;
    padding: 12px 20px;
    border-radius: 6px 6px 0 0;
    margin-right: 5px;
    transition: all 0.2s ease;
}

.nav-tabs .nav-link:hover {
    color: #667eea;
    background-color: rgba(102, 126, 234, 0.05);
}

.nav-tabs .nav-link.active {
    color: #667eea;
    background-color: white;
    border-bottom: 2px solid #667eea;
}

.tab-content {
    padding-top: 20px;
}

/* Password Tips */
.password-tips {
    background: var(--border-color);
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
}

.password-tips h6 {
    color: var(--text-primary);
    margin-bottom: 15px;
    font-weight: 600;
}

.password-tips li {
    padding: 5px 0;
    font-size: 0.875rem;
}

/* System Info */
.system-info .info-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.system-info .info-item:last-child {
    border-bottom: none;
}

/* Status Cards */
.status-cards .status-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 15px;
    transition: all 0.2s ease;
}

.status-cards .status-card:hover {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.status-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.status-content h6 {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 3px;
    color: var(--text-primary);
}

.status-content p {
    font-size: 0.8125rem;
    margin: 0;
}

/* Progress Bar */
.progress-thin {
    height: 4px;
    background: var(--border-color);
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s ease;
}

/* About Info */
.about-info .info-item {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    color: var(--text-primary);
}

.about-info .info-item i {
    width: 20px;
}

.license-info {
    border-top: 1px solid var(--border-color);
    padding-top: 20px;
    margin-top: 20px;
}

/* Toggle Password */
.toggle-password {
    cursor: pointer;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: var(--text-secondary);
}

.toggle-password:hover {
    background: #edf2f7;
}

/* Import styles from index */
:root {
    --primary-soft: rgba(102, 126, 234, 0.08);
    --success-soft: rgba(40, 199, 111, 0.08);
    --warning-soft: rgba(255, 159, 67, 0.08);
    --info-soft: rgba(0, 207, 232, 0.08);
    --danger-soft: rgba(234, 84, 85, 0.08);
    --secondary-soft: rgba(108, 117, 125, 0.08);
    
    --card-bg: #ffffff;
    --border-color: #eef2f7;
    --text-primary: #2d3748;
    --text-secondary: #718096;
}

.card-simple {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
}

.card-simple:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.04);
}

.card-title {
    font-size: 1rem;
    font-weight: 500;
    color: var(--text-primary);
}

.section-header {
    border-bottom: 1px solid var(--border-color);
}

.h3 {
    font-size: 1.75rem;
    font-weight: 400;
}

.font-weight-normal {
    font-weight: 400 !important;
}

.font-weight-medium {
    font-weight: 500 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .nav-tabs .nav-link {
        padding: 10px 15px;
        font-size: 0.875rem;
    }
    
    .password-tips {
        margin-top: 20px;
    }
    
    .status-content p {
        font-size: 0.75rem;
    }
}
</style>

<script>
$(document).ready(function() {
    // Toggle password visibility
    $('.toggle-password').click(function() {
        var input = $(this).closest('.input-group').find('input');
        var icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Save active tab to localStorage
    $('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
        localStorage.setItem('activeSettingsTab', $(e.target).attr('href'));
    });
    
    // Restore active tab
    var activeTab = localStorage.getItem('activeSettingsTab');
    if (activeTab) {
        $('#settingsTab a[href="' + activeTab + '"]').tab('show');
    }
    
    // Password strength indicator
    $('[name="new_password"]').on('input', function() {
        var password = $(this).val();
        var strength = 0;
        
        if (password.length >= 8) strength += 25;
        if (/[a-z]/.test(password)) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password)) strength += 25;
        
        // Update strength meter (you can add a visual meter if needed)
        console.log('Password strength:', strength + '%');
    });
    
    // Form validation
    $('form').submit(function(e) {
        var current = $('[name="current_password"]').val();
        var newpass = $('[name="new_password"]').val();
        var confirm = $('[name="confirm_password"]').val();
        
        if (newpass.length < 6) {
            alert('Password baru minimal 6 karakter!');
            e.preventDefault();
            return false;
        }
        
        if (newpass !== confirm) {
            alert('Password baru dan konfirmasi tidak cocok!');
            e.preventDefault();
            return false;
        }
    });
    
    // Maintenance buttons
    $('.btn-outline-primary, .btn-outline-secondary').click(function(e) {
        e.preventDefault();
        alert('Fitur ini akan segera tersedia dalam versi berikutnya.');
    });
});
</script>