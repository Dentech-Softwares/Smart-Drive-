<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo getSetting('site_name', 'Smart Drive Car Hire'); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/animations.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/responsive.css">
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
    <script>
    const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
    (function() {
        const IDLE_TIMEOUT = 5 * 60 * 1000;
        let idleTimer;
        const logoutUrl = BASE_URL + 'logout.php';
        
        function resetIdleTimer() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(function() {
                window.location.href = logoutUrl;
            }, IDLE_TIMEOUT);
        }
        
        const events = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
        events.forEach(function(evt) {
            document.addEventListener(evt, resetIdleTimer, true);
        });
        
        resetIdleTimer();
    })();
    </script>
</head>
<body>
<?php
