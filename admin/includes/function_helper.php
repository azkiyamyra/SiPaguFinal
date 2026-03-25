<?php
/**
 * HELPER FUNCTIONS - SiPagu
 * Lokasi: admin/includes/function_helper.php
 */

/**
 * Fungsi untuk menampilkan alert Bootstrap
 */
function showAlert($type, $message) {
    $icons = [
        'success' => 'check-circle',
        'danger'  => 'exclamation-triangle',
        'warning' => 'exclamation-circle',
        'info'    => 'info-circle'
    ];
    
    $icon = $icons[$type] ?? 'info-circle';
    
    return '
    <div class="alert alert-' . $type . ' alert-dismissible show fade">
        <div class="alert-body">
            <button class="close" data-dismiss="alert">
                <span>×</span>
            </button>
            <i class="fas fa-' . $icon . ' mr-2"></i>
            ' . $message . '
        </div>
    </div>';
}

/**
 * Fungsi untuk menampilkan alert Bootstrap 5
 */
function showAlertBS5($type, $message) {
    $icons = [
        'success' => 'check-circle',
        'danger'  => 'exclamation-triangle',
        'warning' => 'exclamation-circle',
        'info'    => 'info-circle'
    ];
    
    $icon = $icons[$type] ?? 'info-circle';
    
    return '
    <div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
        <i class="fas fa-' . $icon . ' me-2"></i>
        ' . $message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

/**
 * Format angka ke Rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Validasi file Excel
 */
function validateExcelFile($file) {
    $allowed_ext = ['xls', 'xlsx'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (empty($file['name'])) {
        return 'Silakan pilih file Excel.';
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        return 'File harus bertipe XLS atau XLSX.';
    }
    
    if ($file['size'] > $max_size) {
        return 'File terlalu besar. Maksimal 10MB.';
    }
    
    return true;
}