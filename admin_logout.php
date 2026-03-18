<?php
require_once "config.php";
unset($_SESSION["admin_id"]);
header("Location: admin_login.php");
exit();
?>
