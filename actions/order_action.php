<?php
// order_action.php
// Handles POST from order_summary.php "Make Order" button.
// 1. Validates every cart item (stock, availability)
// 2. Inserts into orders + order_items tables
// 3. Deducts stock from products
// 4. Inserts into sales table
// 5. Sets $_SESSION['last_order_id'] and redirects to order_complete.php
// On any failure: rolls back, sets error message, redirects back to order_summary.php

require '../includes/auth.php';
require '../includes/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/home.php");
    exit;
}

// ── Cancel cart action (from the cancel modal) ───────
if (($_POST['action'] ?? '') === 'cancel_cart') {
    unset($_SESSION['cart']);
    header("Location: ../pages/home.php");
    exit;
}

$vid            = $_SESSION['vendor_id'];
$payment_method = $_POST['payment_method'] ?? 'cash';
$raw_items      = $_POST['items'] ?? [];   // ['product_id' => qty]

// Basic guard — must have items
if (empty($raw_items)) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: ../pages/order_summary.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $line_items = [];  // validated rows ready to insert
    $total      = 0;

    foreach ($raw_items as $pid => $qty) {
        $pid = (int) $pid;
        $qty = (int) $qty;

        // Skip if qty was zeroed out on the summary page
        if ($qty <= 0) continue;

        // Fetch product — must belong to this vendor and be available
        $stmt = $pdo->prepare("
            SELECT id, name, price, stock, is_available
            FROM products
            WHERE id = ? AND vendor_id = ?
        ");
        $stmt->execute([$pid, $vid]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception("Product not found.");
        }

        if (!$product['is_available']) {
            throw new Exception($product['name'] . " is no longer available.");
        }

        if ($product['stock'] < $qty) {
            throw new Exception(
                "Not enough stock for " . $product['name'] . ". " .
                "Only " . $product['stock'] . " left."
            );
        }

        $subtotal     = $product['price'] * $qty;
        $total       += $subtotal;
        $line_items[] = [
            'product_id' => $pid,
            'quantity'   => $qty,
            'unit_price' => $product['price'],
            'name'       => $product['name'],
        ];
    }

    if (empty($line_items)) {
        throw new Exception("No valid items in cart.");
    }

    // ── 1. Insert the order ──────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO orders (vendor_id, payment_method, total_amount, status, created_at)
        VALUES (?, ?, ?, 'completed', NOW())
    ");
    $stmt->execute([$vid, $payment_method, $total]);
    $order_id = $pdo->lastInsertId();

    // ── 2. Insert order items + deduct stock ─────────────
    foreach ($line_items as $item) {
        // Insert order item
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
        ]);

        // Deduct stock
        $stmt = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ? AND vendor_id = ?
        ");
        $stmt->execute([$item['quantity'], $item['product_id'], $vid]);

        // Auto-mark unavailable if stock hits zero
        $pdo->prepare("
            UPDATE products SET is_available = 0
            WHERE id = ? AND stock <= 0
        ")->execute([$item['product_id']]);
    }

    // ── 3. Record in sales table ─────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO sales (order_id, vendor_id, total_amount, sale_date, created_at)
        VALUES (?, ?, ?, CURDATE(), NOW())
    ");
    $stmt->execute([$order_id, $vid, $total]);

    // ── 4. Commit + clean up session ────────────────────
    $pdo->commit();

    unset($_SESSION['cart']);
    $_SESSION['last_order_id'] = $order_id;

    // Go to success page
    header("Location: ../pages/order_complete.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../pages/order_summary.php");
    exit;
}