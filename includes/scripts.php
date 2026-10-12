<script src="<?php echo BASE_URL; ?>assets/js/libs/chart.umd.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/libs/sweetalert2.all.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/dashboard.js"></script>
<?php 
if (isset($extraScripts) && is_array($extraScripts)) {
    foreach ($extraScripts as $script) {
        echo '<script src="' . BASE_URL . $script . '"></script>';
    }
}
if (isset($extraJS)) echo $extraJS; 
?>
</body>
</html>
