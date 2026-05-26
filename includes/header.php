<?php
// header.php — shared HTML shell for all authenticated pages
// Set these before require-ing this file:
//   $page_title  (string)  — <title> content
//   $page_css    (string)  — filename only e.g. 'dashboard.css'
//   $active_nav  (string)  — 'home' | 'inventory' | 'dashboard' | 'history'

$page_title  = $page_title  ?? 'E-Tinda';
$page_css    = $page_css    ?? '';
$active_nav  = $active_nav  ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Global base styles (tokens, reset, layout shell, buttons, forms) -->
    <link rel="stylesheet" href="../assets/css/styles.css">

    <!-- Page-specific styles -->
    <?php if ($page_css): ?>
    <link rel="stylesheet" href="../assets/css/<?= htmlspecialchars($page_css) ?>">
    <?php endif; ?>
</head>
<body>
<div class="app-layout">