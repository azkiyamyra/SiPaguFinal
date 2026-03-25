<?php
// =====================================================
// PROFILE USER - SiPagu Universitas Dian Nuswantoro
// =====================================================
require_once __DIR__ . '/../config.php';

// Cek session
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Ambil data user
$user_id = $_SESSION['id_user'];
$query = mysqli_query($koneksi, 
    "SELECT * FROM t_user WHERE id_user = '$user_id'"
);
$user = mysqli_fetch_assoc($query);

// Proses update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama_user = mysqli_real_escape_string($koneksi, $_POST['nama_user']);
    $nohp_user = mysqli_real_escape_string($koneksi, $_POST['nohp_user']);
    $norek_user = mysqli_real_escape_string($koneksi, $_POST['norek_user']);
    $npwp_user = mysqli_real_escape_string($koneksi, $_POST['npwp_user']);
    
    $update_query = mysqli_query($koneksi,
        "UPDATE t_user SET 
            nama_user = '$nama_user',
            nohp_user = '$nohp_user',
            norek_user = '$norek_user',
            npwp_user = '$npwp_user'
        WHERE id_user = '$user_id'"
    );
    
    if ($update_query) {
        $success = "Profile berhasil diupdate!";
        $query = mysqli_query($koneksi, 
            "SELECT * FROM t_user WHERE id_user = '$user_id'"
        );
        $user = mysqli_fetch_assoc($query);
        $_SESSION['username'] = $user['nama_user'];
    } else {
        $error = "Gagal mengupdate profile: " . mysqli_error($koneksi);
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
                    <h1 class="h3 font-weight-normal text-dark mb-1">Profile User</h1>
                    <p class="text-muted mb-0">Kelola informasi akun Anda</p>
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
                            
                            <div class="row">
                                <!-- Photo & Basic Info -->
                                <div class="col-md-4">
                                    <div class="text-center mb-4">
                                        <h4 class="mb-1"><?= htmlspecialchars($user['nama_user']) ?></h4>
                                        <div class="mb-3">
                                            <span class="badge badge-<?= 
                                                $user['role_user'] == 'admin' ? 'danger' : 
                                                ($user['role_user'] == 'koordinator' ? 'warning' : 'primary')
                                            ?>">
                                                <?= strtoupper($user['role_user']) ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-id-badge mr-1"></i>
                                            <?= htmlspecialchars($user['npp_user']) ?>
                                        </p>
                                    </div>
                                    
                                    <div class="info-card mb-4">
                                        <h6 class="info-title">Informasi Login</h6>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt text-muted mr-2"></i>
                                            <span>Terakhir Login:</span>
                                            <span class="float-right"><?= date('d M Y') ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-history text-muted mr-2"></i>
                                            <span>Status:</span>
                                            <span class="float-right text-success">Aktif</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Profile Form -->
                                <div class="col-md-8">
                                    <h5 class="mb-4">Edit Profile</h5>
                                    <form method="POST" action="">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>NPP</label>
                                                    <input type="text" class="form-control" 
                                                           value="<?= htmlspecialchars($user['npp_user']) ?>" 
                                                           readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>NIK</label>
                                                    <input type="text" class="form-control" 
                                                           value="<?= htmlspecialchars($user['nik_user']) ?>" 
                                                           readonly>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Nama Lengkap *</label>
                                            <input type="text" name="nama_user" class="form-control" 
                                                   value="<?= htmlspecialchars($user['nama_user']) ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nomor HP *</label>
                                                    <input type="text" name="nohp_user" class="form-control" 
                                                           value="<?= htmlspecialchars($user['nohp_user']) ?>" 
                                                           required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>NPWP *</label>
                                                    <input type="text" name="npwp_user" class="form-control" 
                                                           value="<?= htmlspecialchars($user['npwp_user']) ?>" 
                                                           required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Nomor Rekening *</label>
                                            <input type="text" name="norek_user" class="form-control" 
                                                   value="<?= htmlspecialchars($user['norek_user']) ?>" 
                                                   required>
                                            <small class="form-text text-muted">
                                                Pastikan nomor rekening valid untuk pembayaran honor
                                            </small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Honor per SKS</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="text" class="form-control" 
                                                       value="<?= number_format($user['honor_persks'], 0, ',', '.') ?>" 
                                                       readonly>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">/ SKS</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                Nilai ini diatur oleh administrator sistem
                                            </small>
                                        </div>
                                        
                                        <div class="form-group text-right">
                                            <a href="index.php" class="btn btn-secondary mr-2">
                                                <i class="fas fa-arrow-left mr-1"></i> Kembali
                                            </a>
                                            <button type="submit" name="update_profile" 
                                                    class="btn btn-primary">
                                                <i class="fas fa-save mr-1"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
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
/* Profile Specific Styles */
.profile-photo-wrapper {
    position: relative;
    display: inline-block;
}

.profile-photo {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.profile-photo-change {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 36px;
    height: 36px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.profile-photo-change:hover {
    background: #5a67d8;
    transform: scale(1.1);
}

.info-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
}

.info-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-color);
}

.info-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    font-size: 0.875rem;
    color: var(--text-primary);
}

.info-item i {
    width: 20px;
}

/* Form Styles */
.form-group label {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 6px;
}

.form-control {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 10px 15px;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-control:read-only {
    background-color: #f8fafc;
    color: #718096;
}

.input-group-text {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: var(--text-secondary);
    font-weight: 500;
}


/* Alert Styles */
.alert {
    border: none;
    border-radius: 6px;
    padding: 12px 20px;
    margin-bottom: 20px;
}

.alert-success {
    background-color: rgba(40, 199, 111, 0.1);
    color: #28c76f;
    border-left: 4px solid #28c76f;
}

.alert-danger {
    background-color: rgba(234, 84, 85, 0.1);
    color: #ea5455;
    border-left: 4px solid #ea5455;
}

/* Badge Styles */
.badge {
    padding: 6px 12px;
    font-weight: 500;
    border-radius: 20px;
}

.badge-danger {
    background: rgba(234, 84, 85, 0.1);
    color: #ffffff;
}

.badge-warning {
    background: rgba(255, 159, 67, 0.1);
    color: #ff9f43;
}

.badge-primary {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

/* Responsive */
@media (max-width: 768px) {
    .profile-photo {
        width: 120px;
        height: 120px;
    }
    
    .btn {
        padding: 8px 16px;
        font-size: 0.875rem;
    }
    
    .info-card {
        padding: 15px;
    }
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
</style>

<script>
$(document).ready(function() {
    // Photo change functionality
    $('.profile-photo-change').click(function() {
        $('#photoInput').click();
    });
    
    // Preview photo when selected
    $('#photoInput').change(function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                $('.profile-photo').attr('src', e.target.result);
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Form validation
    $('form').submit(function(e) {
        var nohp = $('[name="nohp_user"]').val();
        var norek = $('[name="norek_user"]').val();
        var npwp = $('[name="npwp_user"]').val();
        
        // Simple validation
        if (!nohp.match(/^[0-9+\-\s]+$/)) {
            alert('Nomor HP hanya boleh berisi angka dan tanda +');
            e.preventDefault();
            return false;
        }
        
        if (!norek.match(/^[0-9\s]+$/)) {
            alert('Nomor rekening hanya boleh berisi angka');
            e.preventDefault();
            return false;
        }
        
        if (!npwp.match(/^[0-9.\-]+$/)) {
            alert('Format NPWP tidak valid');
            e.preventDefault();
            return false;
        }
    });
    
    // Format input fields
    $('[name="norek_user"]').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        $(this).val(value);
    });
});
</script>