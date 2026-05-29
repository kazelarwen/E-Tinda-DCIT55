<?php
session_start();

// Already logged in? Go to dashboard
if (isset($_SESSION['vendor_id'])) {
    header("Location: pages/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'includes/db.php';

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM vendors WHERE email = ?");
        $stmt->execute([$email]);
        $vendor = $stmt->fetch();

        if ($vendor && password_verify($password, $vendor['password'])) {
            $_SESSION['vendor_id']   = $vendor['id'];
            $_SESSION['stall_name']  = $vendor['stall_name'];
            $_SESSION['vendor_name'] = $vendor['vendor_name'];
            header("Location: pages/home.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
        `;
    } else {
        input.type = 'password';
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        `;
    }
}
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Tinda | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="auth-page">

    <!-- Blue hero section -->
    <div class="auth-hero">
        <div class="brand-icon">
            <!-- Shopping bag icon -->
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
            </svg>
        </div>
        <h1>E-Tinda</h1>
        <p>Shine down! Inazuma shines eternal!<br>Torn to oblivion!</p>
    </div>

    <!-- White sheet -->
    <div class="auth-sheet">

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
                <button type="button" class="toggle-password" onclick="togglePassword()">
                    <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
                </div>
            </div>

            <!-- Remember me + Forgot password row -->
            <div class="auth-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Login</button>
        </form>

        <p class="auth-switch">
            Don't have an account? <a href="register.php">Sign up</a>
        </p>

    </div><!-- /auth-sheet -->

</body>
</html>