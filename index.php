<?php
require_once "config.php";

if (current_student_id()) {
    header("Location: dashboard.php");
    exit();
}

header("Location: login.php");
exit();
?>
