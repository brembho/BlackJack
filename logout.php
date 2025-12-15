<?php
require_once "includes/system_logger.php";
session_start();
$_SESSION = array();
session_destroy();
header("Location: login.php");
exit();
?>