<?php
session_start();
if (!isset($_SESSION['vendor_id'])) {
    header("Location: /E-Tinda-DCIT55/index.php");
    exit;
}