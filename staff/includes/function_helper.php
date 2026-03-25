<?php
/**
 * HELPER FUNCTIONS - SiPagu
 * Lokasi: staff/includes/function_helper.php
 */

if (!function_exists('showAlert')) {
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
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

if (!function_exists('hitungHonorStaff')) {
    /**
     * Menghitung honor staff dengan pajak 5% dan potongan 5% dari sisa
     */
    function hitungHonorStaff($nominal) {
        $pajak = $nominal * 0.05;
        $sisa = $nominal - $pajak;
        $potongan = $sisa * 0.05;
        $honor_bersih = $sisa - $potongan;
        
        return [
            'nominal' => $nominal,
            'pajak' => $pajak,
            'sisa' => $sisa,
            'potongan' => $potongan,
            'bersih' => $honor_bersih
        ];
    }
}

?>