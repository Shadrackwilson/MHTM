<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | MHTM" : "MWAKASEGE HOUSE TENANT MANAGEMENT (MHTM)"; ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/MHTM/assets/css/style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php 
if (isLoggedIn()) {
?>
<div id="wrapper">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/MHTM/includes/sidebar.php'; ?>
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-primary d-md-none">
                    <i class="fas fa-align-left"></i>
                </button>
                <div class="ms-auto d-flex align-items-center">
                    <img src="<?php echo (!empty($_SESSION['admin_photo'])) ? '/MHTM/uploads/admins/' . $_SESSION['admin_photo'] : '/MHTM/assets/img/default-admin.png'; ?>" 
                         class="rounded-circle me-2 border" width="30" height="30" style="object-fit: cover;">
                    <span class="me-3 d-none d-md-inline">Welcome, <strong><?php echo $_SESSION['admin_name']; ?></strong></span>
                    <a href="/MHTM/logout.php" class="btn btn-outline-danger btn-sm rounded-pill">Logout</a>
                </div>
            </div>
        </nav>
        <div class="container-fluid">
            <?php displayFlash(); ?>
<?php 
}
?>
