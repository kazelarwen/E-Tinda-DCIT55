<?php
// product_action.php
// Handles all product CRUD: add, edit, delete
// Called via POST from add_product.php and edit_product.php

require '../includes/auth.php';
require '../includes/db.php';

$vid    = $_SESSION['vendor_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─────────────────────────────────────────────────────
// ADD
// ─────────────────────────────────────────────────────
if ($action === 'add') {

    $name     = trim($_POST['name']     ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $stock    = (int)($_POST['stock']   ?? 0);

    // Server-side validation
    if ($name === '' || $price <= 0 || $category === '') {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: ../pages/add_product.php");
        exit;
    }

    if ($stock < 0) $stock = 0;

    // File upload (optional)
    $image = 'placeholder.png';

    if (!empty($_FILES['image']['name'])) {
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $max_size = 2 * 1024 * 1024; // 2 MB
        $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Invalid image type. Allowed: JPG, PNG, GIF, WEBP.";
            header("Location: ../pages/add_product.php");
            exit;
        }

        if ($_FILES['image']['size'] > $max_size) {
            $_SESSION['error'] = "Image must be under 2MB.";
            header("Location: ../pages/add_product.php");
            exit;
        }

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = "Image upload failed. Please try again.";
            header("Location: ../pages/add_product.php");
            exit;
        }

        $image    = uniqid('prod_') . '.' . $ext;
        $dest     = __DIR__ . '/../assets/uploads/' . $image;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $_SESSION['error'] = "Could not save image. Check folder permissions.";
            header("Location: ../pages/add_product.php");
            exit;
        }
    }

    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO products (vendor_id, name, category, price, stock, image, is_available)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$vid, $name, $category, $price, $stock, $image]);

    $_SESSION['success'] = "Product added successfully.";
    header("Location: ../pages/inventory.php");
    exit;
}

// ─────────────────────────────────────────────────────
// EDIT
// ─────────────────────────────────────────────────────
if ($action === 'edit') {

    $id       = (int)($_POST['id']      ?? 0);
    $name     = trim($_POST['name']     ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $stock    = (int)($_POST['stock']   ?? 0);

    if (!$id || $name === '' || $price <= 0 || $category === '') {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: ../pages/edit_product.php?id=$id");
        exit;
    }

    if ($stock < 0) $stock = 0;

    // Verify product belongs to this vendor + get current image
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$id, $vid]);
    $current = $stmt->fetch();

    if (!$current) {
        $_SESSION['error'] = "Product not found.";
        header("Location: ../pages/inventory.php");
        exit;
    }

    $image = $current['image']; // keep existing image by default

    // Replace image only if a new one was uploaded
    if (!empty($_FILES['image']['name'])) {
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $max_size = 2 * 1024 * 1024;
        $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Invalid image type. Allowed: JPG, PNG, GIF, WEBP.";
            header("Location: ../pages/edit_product.php?id=$id");
            exit;
        }

        if ($_FILES['image']['size'] > $max_size) {
            $_SESSION['error'] = "Image must be under 2MB.";
            header("Location: ../pages/edit_product.php?id=$id");
            exit;
        }

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = "Image upload failed. Please try again.";
            header("Location: ../pages/edit_product.php?id=$id");
            exit;
        }

        $new_image = uniqid('prod_') . '.' . $ext;
        $dest      = __DIR__ . '/../assets/uploads/' . $new_image;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            // Delete old image file (if it's not the placeholder)
            if ($image !== 'placeholder.png') {
                $old_path = __DIR__ . '/../assets/uploads/' . $image;
                if (file_exists($old_path)) unlink($old_path);
            }
            $image = $new_image;
        } else {
            $_SESSION['error'] = "Could not save image. Check folder permissions.";
            header("Location: ../pages/edit_product.php?id=$id");
            exit;
        }
    }

    // Re-enable product if stock is now > 0
    $is_available = $stock > 0 ? 1 : 0;

    $stmt = $pdo->prepare("
        UPDATE products
        SET name = ?,
            category = ?,
            price = ?,
            stock = ?,
            image = ?,
            is_available = ?
        WHERE id = ? AND vendor_id = ?
    ");
    $stmt->execute([$name, $category, $price, $stock, $image, $is_available, $id, $vid]);

    $_SESSION['success'] = "Product updated successfully.";
    header("Location: ../pages/inventory.php");
    exit;
}

// ─────────────────────────────────────────────────────
// DELETE
// ─────────────────────────────────────────────────────
if ($action === 'delete') {

    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

    if (!$id) {
        header("Location: ../pages/inventory.php");
        exit;
    }

    // Get image filename before deleting
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$id, $vid]);
    $product = $stmt->fetch();

    if ($product) {
        // Delete from DB
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?");
        $stmt->execute([$id, $vid]);

        // Delete image file
        if ($product['image'] !== 'placeholder.png') {
            $img_path = __DIR__ . '/../assets/uploads/' . $product['image'];
            if (file_exists($img_path)) unlink($img_path);
        }

        $_SESSION['success'] = "Product deleted.";
    }

    header("Location: ../pages/inventory.php");
    exit;
}

// ─────────────────────────────────────────────────────
// BULK DELETE
// ─────────────────────────────────────────────────────
if ($action === 'bulk_delete') {

    $ids = $_POST['ids'] ?? [];

    if (!empty($ids)) {
        // Cast every value to int and strip out zeros
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params       = array_merge($ids, [$vid]);

            // Fetch image filenames first so we can delete the files
            $stmt = $pdo->prepare("
                SELECT image FROM products
                WHERE id IN ($placeholders) AND vendor_id = ?
            ");
            $stmt->execute($params);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Delete the rows — vendor_id prevents deleting other vendors' products
            $pdo->prepare("
                DELETE FROM products
                WHERE id IN ($placeholders) AND vendor_id = ?
            ")->execute($params);

            // Delete image files from disk
            foreach ($images as $img) {
                if ($img && $img !== 'placeholder.png') {
                    $path = __DIR__ . '/../assets/uploads/' . $img;
                    if (file_exists($path)) unlink($path);
                }
            }
        }
    }

    $_SESSION['success'] = 'Selected products deleted.';
    header('Location: ../pages/inventory.php');
    exit;
}

// If we get here, unknown action
header("Location: ../pages/inventory.php");
exit;

// If we get here, unknown action
header("Location: ../pages/inventory.php");
exit;