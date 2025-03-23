<?php
session_start();
session_destroy();
// Redirect ke situs yang diinginkan
header("Location: ../auth/login.php");
exit;
?>
