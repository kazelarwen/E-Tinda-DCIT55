<?php
// order_action.php
require '../includes/auth.php';
require '../includes/db.php';

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

// ── Cancel ORDER action (from transaction_detail.php) ───────
if (($_POST['action'] ?? '') === 'cancel_order') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $vid      = $_SESSION['vendor_id'];

    if (!$order_id) {
        header("Location: ../pages/transaction_history.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Verify order belongs to this vendor and is completed
        $stmt = $pdo->prepare("
            SELECT * FROM orders
            WHERE id = ? AND vendor_id = ? AND status = 'completed'
        ");
        $stmt->execute([$order_id, $vid]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new Exception("Order not found or already cancelled.");
        }

        // Fetch order items to restore stock
        $stmt = $pdo->prepare("
            SELECT product_id, quantity FROM order_items WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();

        // Restore stock for each item
        foreach ($items as $item) {
            $pdo->prepare("
                UPDATE products
                SET stock = stock + ?,
                    is_available = 1
                WHERE id = ? AND vendor_id = ?
            ")->execute([$item['quantity'], $item['product_id'], $vid]);
        }

        // Mark order as cancelled
        $pdo->prepare("
            UPDATE orders SET status = 'cancelled' WHERE id = ?
        ")->execute([$order_id]);

        // Remove from sales table
        $pdo->prepare("
            DELETE FROM sales WHERE order_id = ?
        ")->execute([$order_id]);

        $pdo->commit();

        $_SESSION['success'] = "Order #" . str_pad($order_id, 3, '0', STR_PAD_LEFT) . " has been cancelled.";
        header("Location: ../pages/transaction_detail.php?id=$order_id");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../pages/transaction_detail.php?id=$order_id");
        exit;
    }
}

$vid            = $_SESSION['vendor_id'];
$payment_method = $_POST['payment_method'] ?? 'cash';
$raw_items      = $_POST['items'] ?? [];

if (empty($raw_items)) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: ../pages/order_summary.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $line_items = [];
    $total      = 0;

    foreach ($raw_items as $pid => $qty) {
        $pid = (int) $pid;
        $qty = (int) $qty;

        if ($qty <= 0) continue;

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

    $stmt = $pdo->prepare("
        INSERT INTO orders (vendor_id, payment_method, total_amount, status, created_at)
        VALUES (?, ?, ?, 'completed', NOW())
    ");
    $stmt->execute([$vid, $payment_method, $total]);
    $order_id = $pdo->lastInsertId();

    foreach ($line_items as $item) {
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

        $stmt = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ? AND vendor_id = ?
        ");
        $stmt->execute([$item['quantity'], $item['product_id'], $vid]);

        $pdo->prepare("
            UPDATE products SET is_available = 0
            WHERE id = ? AND stock <= 0
        ")->execute([$item['product_id']]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO sales (order_id, vendor_id, total_amount, sale_date, created_at)
        VALUES (?, ?, ?, CURDATE(), NOW())
    ");
    $stmt->execute([$order_id, $vid, $total]);

    $pdo->commit();

    unset($_SESSION['cart']);
    $_SESSION['last_order_id'] = $order_id;

    header("Location: ../pages/order_complete.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../pages/order_summary.php");
    exit;
}