<footer class="main-footer">
    <div class="footer-left">
        Copyright &copy; <?= date('Y') ?> <div class="bullet"></div> SiPagu - Sistem Informasi Honor Dosen
    </div>
    <div class="footer-right">
        v1.0.0
    </div>
</footer>
</div>
</div>

<!-- ================= GENERAL JS ================= -->
<script src="<?= ASSETS_URL ?>/modules/jquery.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/popper.js"></script>
<script src="<?= ASSETS_URL ?>/modules/bootstrap/js/bootstrap.min.js"></script>

<!-- ================= JS LIBRARIES ================= -->
<script src="<?= ASSETS_URL ?>/modules/jquery.nicescroll/jquery.nicescroll.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/moment.min.js"></script>
<script src="<?= ASSETS_URL ?>/modules/bootstrap-daterangepicker/daterangepicker.js"></script>

<!-- ================= TEMPLATE JS ================= -->
<script src="<?= ASSETS_URL ?>/js/stisla.js"></script>
<script src="<?= ASSETS_URL ?>/js/scripts.js"></script>
<script src="<?= ASSETS_URL ?>/js/custom.js"></script>

<!-- Page Specific JS -->
<script>
    $(document).ready(function() {
        // Auto hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Tooltip initialization
        $('[data-toggle="tooltip"]').tooltip();
        
        // Popover initialization
        $('[data-toggle="popover"]').popover();
    });
</script>
</body>
</html>