<?php
require_once __DIR__ . '/config.php';

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    if (isset($_SESSION['role_id'])) {
        switch ($_SESSION['role_id']) {
            case 1:
                redirect('admin/dashboard.php');
                break;
            case 2:
                redirect('admin/dashboard.php');
                break;
            case 3:
                redirect('user/dashboard.php');
                break;
            default:
                redirect('index.php');
        }
    } else {
        redirect('index.php');
    }
    exit;
}

$page_title = "Login - SiPagu";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiPagu | <?php echo $page_title; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/custom.css'); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo asset_url('favicon/favicon-96x96.png'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_url('favicon/favicon.svg'); ?>">
    <link rel="shortcut icon" href="<?php echo asset_url('favicon/favicon.ico'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo asset_url('favicon/apple-touch-icon.png'); ?>">
    <meta name="apple-mobile-web-app-title" content="SiPagu">
    <link rel="manifest" href="<?php echo asset_url('favicon/site.webmanifest'); ?>">
</head>
<body class="login-page">
    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Background Pattern -->
    <div class="login-bg-pattern"></div>

    <!-- Navigation -->
    <nav class="nav" id="navbar">
        <div class="container">
            <div class="nav-content">
                <!-- Logo dengan gambar -->
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <div class="logo-container">
                        <div class="logo-image">
                            <img src="<?php echo asset_url('img/logoSiPagu.png'); ?>" alt="SiPagu Logo" class="logo-img">
                        </div>
                        <div class="logo-text">
                            Si<span>Pagu</span>
                        </div>
                    </div>
                </a>
                
                <!-- Modern Hamburger Menu -->
                <button class="hamburger-menu" id="hamburgerMenu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
                
                <!-- Menu Overlay (Mobile) -->
                <div class="menu-overlay" id="menuOverlay"></div>
                
                <!-- Navigation Links -->
                <div class="nav-links" id="navLinks">
                    <a href="<?php echo BASE_URL; ?>" class="nav-link">
                        <i class="fas fa-home"></i>
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Login Content -->
    <main class="login-main">
        <div class="login-card fade-in">
            <!-- Login Header dengan Logo Foto -->
            <div class="login-header">
                <!-- Logo Foto saja (tanpa teks) -->
                <div class="login-logo-container">
                    <div class="login-logo-image">
                        <img src="<?php echo asset_url('img/logoSiPagu.png'); ?>" alt="SiPagu Logo" class="login-logo-img">
                    </div>
                </div>
                <h1 class="login-title">Masuk ke Sistem</h1>
                <p class="login-subtitle">Akses sistem terintegrasi untuk pengelolaan honor fakultas</p>
            </div>
            
            <!-- Login Form -->
            <div class="login-form">
                
                <!-- Notifikasi Server-Side -->
                <?php
                $pesan = $_GET['pesan'] ?? '';
                $notif_type = '';
                $notif_icon = '';
                $notif_label = '';
                $notif_title = '';
                $notif_desc  = '';

                if ($pesan === 'npp_salah') {
                    $notif_type  = 'error';
                    $notif_icon  = 'fa-user-slash';
                    $notif_label = 'NPP Tidak Ditemukan';
                    $notif_title = 'NPP tidak terdaftar';
                    $notif_desc  = 'NPP yang kamu masukkan tidak ada dalam sistem. Periksa kembali NPP kamu.';
                } elseif ($pesan === 'password_salah') {
                    $notif_type  = 'error';
                    $notif_icon  = 'fa-lock-open';
                    $notif_label = 'Kata Sandi Salah';
                    $notif_title = 'Kata sandi tidak sesuai';
                    $notif_desc  = 'NPP kamu ditemukan, namun kata sandi yang dimasukkan salah. Coba lagi.';
                } elseif ($pesan === 'logout') {
                    $notif_type  = 'success';
                    $notif_icon  = 'fa-circle-check';
                    $notif_label = 'Berhasil Keluar';
                    $notif_title = 'Sampai jumpa!';
                    $notif_desc  = 'Kamu telah berhasil keluar dari sistem SiPagu.';
                }
                ?>
                <script>
                function dismissNotif(id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-8px) scale(0.97)';
                        setTimeout(function() { el.remove(); }, 320);
                    }
                }
                </script>
                <?php if ($notif_type): ?>
                <div class="notif-wrapper" id="serverNotif">
                    <div class="notif-card notif-<?= $notif_type ?>">
                        <div class="notif-side"></div>
                        <div class="notif-icon-wrap">
                            <i class="fas <?= $notif_icon ?>"></i>
                        </div>
                        <div class="notif-content">
                            <span class="notif-label"><?= $notif_label ?></span>
                            <p class="notif-title"><?= $notif_title ?></p>
                            <p class="notif-desc"><?= $notif_desc ?></p>
                        </div>
                        <button class="notif-dismiss" onclick="dismissNotif('serverNotif')" aria-label="Tutup">
                            <i class="fas fa-xmark"></i>
                        </button>
                        <div class="notif-timer" id="serverNotifTimer"></div>
                    </div>
                </div>
                <?php endif; ?>

                <form action="login_aksi.php" method="post" id="loginForm">
                    <div class="form-group">
                        <label for="npp" class="form-label">NPP</label>
                        <div class="input-group">
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="text" name="npp_user" id="npp" class="form-control" placeholder="masukkan NPP" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Kata Sandi</label>
                        <div class="input-group">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" name="pw_user" id="password" class="form-control" placeholder="Masukkan kata sandi" required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" name="remember" value="1" id="remember">
                            <label for="remember">Ingat saya</label>
                        </div>
                        <a href="#" class="forgot-password">Lupa kata sandi?</a>
                    </div>
                    
                    <button type="submit" class="login-btn" id="loginButton">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk ke Sistem
                    </button>
                    
                    <div class="login-footer">
                        <p>Belum punya akun? <a href="<?php echo BASE_URL; ?>#contact">Hubungi administrator</a></p>
                    </div>
                </form>
                
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <a href="<?php echo BASE_URL; ?>" class="footer-logo">
                        Si<span>Pagu</span>
                    </a>
                    <p class="footer-desc">Sistem Penghonoran Fakultas Terintegrasi yang menyelesaikan masalah sinkronisasi penghonoran dengan standar universitas dan perhitungan pajak yang akurat.</p>
                    
                    <div class="footer-contact">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            info@sipagu.ac.id
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            (021) 1234-5678
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            Gedung D
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="footer-links-title">Fitur Utama</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_URL; ?>#features"><i class="fas fa-circle" style="color: var(--info);"></i> Standarisasi Pagu</a></li>
                        <li><a href="<?php echo BASE_URL; ?>#features"><i class="fas fa-circle" style="color: var(--danger);"></i> Kalkulasi Pajak</a></li>
                        <li><a href="<?php echo BASE_URL; ?>#features"><i class="fas fa-circle" style="color: var(--success);"></i> Distribusi Digital</a></li>
                        <li><a href="<?php echo BASE_URL; ?>#features"><i class="fas fa-circle" style="color: var(--purple);"></i> Pelaporan</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="footer-links-title">Dukungan</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-circle" style="color: var(--warning);"></i> Panduan Pengguna</a></li>
                        <li><a href="#"><i class="fas fa-circle" style="color: var(--teal);"></i> FAQ</a></li>
                        <li><a href="#"><i class="fas fa-circle" style="color: var(--info);"></i> Kontak Teknis</a></li>
                        <li><a href="#"><i class="fas fa-circle" style="color: var(--success);"></i> Update Sistem</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="footer-links-title">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-circle" style="color: var(--purple);"></i> Kebijakan Privasi</a></li>
                        <li><a href="#"><i class="fas fa-circle" style="color: var(--primary);"></i> Syarat Layanan</a></li>
                        <li><a href="#"><i class="fas fa-circle" style="color: var(--danger);"></i> SLA</a></li>
                        <li><a href="#"><i class="fas fa-circle" style="color: var(--warning);"></i> Sertifikasi Keamanan</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                Copyright &copy; <?= date('Y') ?>
                <div class="bullet"></div>
                SiPagu - Sistem Pengelolaan Keuangan Suci & Azkiya
            </div>
        </div>
    </footer>

    <script src="<?php echo asset_url('js/custom.js'); ?>"></script>
    
    <script>
        // DOM Content Loaded untuk Login Page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - Login Page');
            
            // Toggle password visibility
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const eyeIcon = this.querySelector('i');
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    }
                });
            }
            
            // Form submission handling
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const nppInput = document.getElementById('npp');
                    const passwordInput = document.getElementById('password');
                    
                    // Validasi client-side
                    if (!nppInput.value.trim()) {
                        e.preventDefault();
                        showAlert('error', 'NPP wajib diisi', 'Silakan masukkan NPP kamu untuk melanjutkan.');
                        nppInput.focus();
                        return false;
                    }
                    
                    if (!passwordInput.value.trim()) {
                        e.preventDefault();
                        showAlert('error', 'Kata sandi wajib diisi', 'Silakan masukkan kata sandi kamu untuk melanjutkan.');
                        passwordInput.focus();
                        return false;
                    }
                    
                    // Menampilkan loading state
                    const loginButton = document.getElementById('loginButton');
                    if (loginButton) {
                        const originalText = loginButton.innerHTML;
                        loginButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                        loginButton.disabled = true;
                        
                        // Reset button after 5 seconds if form doesn't submit
                        setTimeout(() => {
                            loginButton.innerHTML = originalText;
                            loginButton.disabled = false;
                        }, 5000);
                    }
                    
                    return true;
                });
            }
            
            // ---- Notifikasi client-side (validasi form) ----
            function showAlert(type, message, desc) {
                let existing = document.getElementById('clientNotif');
                if (existing) {
                    dismissNotif('clientNotif');
                }

                const cfg = {
                    error:   { icon: 'fa-exclamation-circle', label: 'Perhatian' },
                    success: { icon: 'fa-circle-check',       label: 'Berhasil'  },
                    warning: { icon: 'fa-triangle-exclamation', label: 'Peringatan' }
                };
                const c = cfg[type] || cfg.error;
                const descHtml = desc ? '<p class="notif-desc">' + desc + '</p>' : '';

                const wrap = document.createElement('div');
                wrap.id = 'clientNotif';
                wrap.className = 'notif-wrapper';
                wrap.innerHTML =
                    '<div class="notif-card notif-' + type + '">' +
                        '<div class="notif-side"></div>' +
                        '<div class="notif-icon-wrap"><i class="fas ' + c.icon + '"></i></div>' +
                        '<div class="notif-content">' +
                            '<span class="notif-label">' + c.label + '</span>' +
                            '<p class="notif-title">' + message + '</p>' +
                            descHtml +
                        '</div>' +
                        '<button class="notif-dismiss" onclick="dismissNotif(\'clientNotif\')">' +
                            '<i class="fas fa-xmark"></i>' +
                        '</button>' +
                        '<div class="notif-timer" id="clientNotifTimer"></div>' +
                    '</div>';

                const form = document.getElementById('loginForm');
                form.parentNode.insertBefore(wrap, form);
                startTimer('clientNotifTimer', 5000, 'clientNotif');
            }

            function dismissNotif(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-8px) scale(0.97)';
                    setTimeout(function() { el.remove(); }, 320);
                }
            }

            function startTimer(timerId, duration, wrapId) {
                const bar = document.getElementById(timerId);
                if (!bar) return;
                bar.style.transition = 'none';
                bar.style.width = '100%';
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        bar.style.transition = 'width ' + duration + 'ms linear';
                        bar.style.width = '0%';
                    });
                });
                setTimeout(() => dismissNotif(wrapId), duration);
            }

            // Auto-start timer for server-side notif
            document.addEventListener('DOMContentLoaded', function() {
                if (document.getElementById('serverNotif')) {
                    startTimer('serverNotifTimer', 7000, 'serverNotif');
                }
            });
        });
    </script>
</body>
</html>