<?php
/**
 * FOOTER SCRIPTS TEMPLATE - SiPagu
 * Lokasi: admin/includes/footer_scripts.php
 * FIXED: jQuery path diperbaiki
 */
?>

<!-- ================= GENERAL JS ================= -->
<!-- PERBAIKAN: Path jQuery tanpa folder jquery/ -->
<script src="<?= ASSETS_URL ?>/modules/jquery.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/popper.js"></script>
<script src="<?= ASSETS_URL ?>/modules/tooltip.js"></script>
<script src="<?= ASSETS_URL ?>/modules/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/moment.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/stisla.js"></script>

<!-- ================= LIBRARIES ================= -->
<script src="<?= ASSETS_URL ?>/modules/jquery.sparkline.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/chart.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/owlcarousel2/dist/owl.carousel.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/summernote/summernote-bs4.js"></script>
<script src="<?= ASSETS_URL ?>/modules/chocolat/dist/js/jquery.chocolat.min.js"></script>

<!-- ================= TEMPLATE JS ================= -->
<script src="<?= ASSETS_URL ?>/js/scripts.js"></script>
<script src="<?= ASSETS_URL ?>/js/custom.js"></script>

<!-- ================= DATATABLES ================= -->
<script src="<?= ASSETS_URL ?>/modules/datatables/datatables.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js"></script>

<?php
/**
 * ================= PAGE SPECIFIC JS =================
 */
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_js_path = ASSETS_PATH . '/js/page/' . $current_page . '.js';
$page_js_url  = ASSETS_URL  . '/js/page/' . $current_page . '.js';

$blocked_pages = ['upload_user', 'upload_tu', 'upload_tpata', 'upload_panitia', 'upload_thd', 'upload_jadwal'];

// Load JS hanya jika file ada dan bukan halaman terblokir
if (!in_array($current_page, $blocked_pages) && file_exists($page_js_path)) {
    echo '<script src="' . $page_js_url . '"></script>';
}
?>

</div> <!-- penutup main-wrapper -->
</div> <!-- penutup #app -->
</body>
</html>