<?php
require '../includes/auth.php';
require '../includes/db.php';

$action    = $_POST['action'] ?? '';
$vendor_id = $_SESSION['vendor_id'];

// ─────────────────────────────────────────────────────
// UPDATE PROFILE IMAGE
// ─────────────────────────────────────────────────────
if ($action === 'update_avatar') {

    if (empty($_FILES['profile_image']['name'])) {
        $_SESSION['avatar_error'] = "No image selected.";
        header("Location: ../pages/profile.php");
        exit;
    }

    $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $max_size = 2 * 1024 * 1024;
    $ext      = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $_SESSION['avatar_error'] = "Invalid image type.";
        header("Location: ../pages/profile.php");
        exit;
    }

    if ($_FILES['profile_image']['size'] > $max_size) {
        $_SESSION['avatar_error'] = "Image must be under 2MB.";
        header("Location: ../pages/profile.php");
        exit;
    }

    $new_image = 'profile_' . $vendor_id . '_' . uniqid() . '.' . $ext;
    $dest      = __DIR__ . '/../assets/uploads/' . $new_image;

    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
        $_SESSION['avatar_error'] = "Could not save image.";
        header("Location: ../pages/profile.php");
        exit;
    }

    // Delete old image if not default
    $stmt = $pdo->prepare("SELECT profile_image FROM vendors WHERE id = ?");
    $stmt->execute([$vendor_id]);
    $old = $stmt->fetchColumn();
    if ($old && $old !== 'default.png') {
        $old_path = __DIR__ . '/../assets/uploads/' . $old;
        if (file_exists($old_path)) unlink($old_path);
    }

    $stmt = $pdo->prepare("UPDATE vendors SET profile_image = ? WHERE id = ?");
    $stmt->execute([$new_image, $vendor_id]);

    $_SESSION['success'] = "Profile picture updated!";
    header("Location: ../pages/profile.php");
    exit;
}

// ─────────────────────────────────────────────────────
// UPDATE PROFILE
// ─────────────────────────────────────────────────────
if ($action === 'update_profile') {

    $vendor_name    = trim($_POST['vendor_name']    ?? '');
    $stall_name     = trim($_POST['stall_name']     ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');

    if (empty($vendor_name) || empty($stall_name)) {
        $_SESSION['error'] = "Name and stall name cannot be empty.";
        header("Location: ../pages/profile.php");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE vendors
        SET vendor_name = ?, stall_name = ?, contact_number = ?
        WHERE id = ?
    ");
    $stmt->execute([$vendor_name, $stall_name, $contact_number, $vendor_id]);

    // Update session so header reflects new name immediately
    $_SESSION['vendor_name'] = $vendor_name;
    $_SESSION['stall_name']  = $stall_name;

    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: ../pages/profile.php");
    exit;
}

// ─────────────────────────────────────────────────────
// DELETE ACCOUNT
// ─────────────────────────────────────────────────────
if ($action === 'delete_account') {

    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM vendors WHERE id = ?");
    $stmt->execute([$vendor_id]);
    $vendor = $stmt->fetch();

    if (!$vendor || !password_verify($password, $vendor['password'])) {
        $_SESSION['error'] = "Incorrect password. Please try again.";
        header("Location: ../pages/profile.php");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM sales WHERE vendor_id = ?");
    $stmt->execute([$vendor_id]);

    $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
    $stmt->execute([$vendor_id]);

    session_destroy();
    session_start();
    $_SESSION['success'] = "Account deleted successfully.";
    header("Location: ../index.php");
    exit;
}

header("Location: ../pages/profile.php");
exit;