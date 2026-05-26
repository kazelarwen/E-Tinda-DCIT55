<?php
// save_cart.php
// Receives the cart as JSON from home.php JS, writes it to $_SESSION['cart'],
// then redirects to order_summary.php.
// Called via fetch() POST from the cart bar click.

require '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'POST only']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid payload']);
    exit;
}

// Build session cart: [product_id => qty]
$session_cart = [];
foreach ($data['cart'] as $item) {
    $id  = (int)($item['id']  ?? 0);
    $qty = (int)($item['qty'] ?? 0);
    if ($id > 0 && $qty > 0) {
        $session_cart[$id] = $qty;
    }
}

if (empty($session_cart)) {
    echo json_encode(['ok' => false, 'msg' => 'Empty cart']);
    exit;
}

$_SESSION['cart'] = $session_cart;

echo json_encode(['ok' => true]);