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
</head>
<body>
<?php
