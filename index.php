<?php
// index.php
require_once 'config/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
?>
