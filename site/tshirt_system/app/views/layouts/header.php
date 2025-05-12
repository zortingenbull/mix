<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>T-Shirt Printing System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <?php include APP_PATH . '/views/layouts/navigation.php'; ?>
    <?php endif; ?>
    
    <main>