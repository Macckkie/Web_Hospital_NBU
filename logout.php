<?php
// logout.php - Изход от системата за Web_Hospital_NBU
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit();
?>
