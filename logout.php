<?php
session_start();
session_destroy();
setcookie('remember_vendor_id', '', time() - 3600, '/');
header("Location: index.php");
exit;