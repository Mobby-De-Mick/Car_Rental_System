<?php
if (!isset($_SESSION)) {
    session_start();
}

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}
?>