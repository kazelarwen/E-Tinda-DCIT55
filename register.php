<?php
session_start();

if (isset($_SESSION['vendor_id'])) {
    header("Location: pages/home.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'includes/db.php';

    $vendor_name = trim($_POST['vendor_name']     ?? '');
    $stall_name  = trim($_POST['stall_name']      ?? '');
    $email       = trim($_POST['email']           ?? '');
    $password    = $_POST['password']             ?? '';
    $confirm     = $_POST['confirm_password']     ?? '';

    if (empty($vendor_name) || empty($stall_name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM vendors WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $pdo->prepare("
                INSERT INTO vendors (vendor_name, stall_name, email, password)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$vendor_name, $stall_name, $email, $hashed]);

            $_SESSION['success'] = "Account created! Please log in.";
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Tinda | Sign Up</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="auth-page">

    <!-- Hero: same layout as login, different text -->
    <div class="auth-hero">
        <div class="auth-icon-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="white"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
            </svg>
        </div>
        <h1 class="auth-brand auth-brand--register">Let's get started</h1>
        <p class="auth-tagline">Manage your sales every where you go!</p>
    </div>

    <!-- White card -->
    <div class="auth-sheet">

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <div class="form-group">
                <label for="vendor_name">Your Name</label>
                <input type="text" id="vendor_name" name="vendor_name"
                       placeholder="Enter your full name"
                       value="<?= htmlspecialchars($_POST['vendor_name'] ?? '') ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="stall_name">Stall Name</label>
                <input type="text" id="stall_name" name="stall_name"
                       placeholder="Enter your stall or business name"
                       value="<?= htmlspecialchars($_POST['stall_name'] ?? '') ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       placeholder="Enter your email address"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter your password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Enter your password" required>
            </div>

            <div class="auth-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
                <a href="#" class="auth-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Sign Up</button>

        </form>

        <p class="auth-switch">
            Already have an account? <a href="index.php" class="auth-link">Log in</a>
        </p>

    </div>

</body>
</html>