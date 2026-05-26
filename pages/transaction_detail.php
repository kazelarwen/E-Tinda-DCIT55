<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title = 'Transaction Detail — E-Tinda';
$page_css   = 'transaction_detail.css';
$active_nav = 'history';
$vid        = $_SESSION['vendor_id'];

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    header("Location: transaction_history.php");
    exit;
}

// Fetch order — must belong to this vendor
$stmt = $pdo->prepare("
    SELECT * FROM orders
    WHERE id = ? AND vendor_id = ?
");
$stmt->execute([$order_id, $vid]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: transaction_history.php");
    exit;
}

// Fetch order items with product names via JOIN
$stmt = $pdo->prepare("
    SELECT
        oi.quantity,
        oi.unit_price,
        (oi.quantity * oi.unit_price) AS subtotal,
        p.name AS product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Totals
$subtotal = array_sum(array_column($items, 'subtotal'));
$total    = (float)$order['total_amount'];
$discount = max(0, $subtotal - $total); // derived discount

$id_padded = str_pad($order['id'], 3, '0', STR_PAD_LEFT);
$date_fmt  = date('F j, Y', strtotime($order['created_at']));
$time_fmt  = date('g:i a',  strtotime($order['created_at']));

require '../includes/header.php';
?>

<!-- Back arrow + title -->
<div class="form-topbar">
    <a href="transaction_history.php" class="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
    </a>
    <h1 class="form-topbar-title">Transaction History</h1>
</div>

<div class="page-content detail-page">

    <!-- Transaction ID header row -->
    <div class="detail-id-row">
        <span class="detail-id-label">TRANSACTION ID:</span>
        <span class="detail-id-num"><?= $id_padded ?></span>
    </div>

    <!-- Date + time row -->
    <div class="detail-datetime-row">
        <span class="detail-date"><?= $date_fmt ?></span>
        <span class="detail-time"><?= $time_fmt ?></span>
    </div>

    <!-- Column headers -->
    <div class="detail-col-headers">
        <span class="col-qty">QTY</span>
        <span class="col-item">ITEM</span>
        <span class="col-amt">AMT</span>
    </div>

    <!-- Line items -->
    <div class="detail-items">
        <?php foreach ($items as $item): ?>
        <div class="detail-item-row">
            <span class="col-qty"><?= $item['quantity'] ?></span>
            <span class="col-item"><?= strtoupper(htmlspecialchars($item['product_name'])) ?></span>
            <span class="col-amt"><?= number_format($item['subtotal'], 0) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Totals section -->
    <div class="detail-totals">
        <div class="totals-row">
            <span class="totals-label">SUBTOTAL:</span>
            <span class="totals-value">₱<?= number_format($subtotal, 0) ?></span>
        </div>

        <?php if ($discount > 0): ?>
        <div class="totals-row">
            <span class="totals-label">DISCOUNT:</span>
            <span class="totals-value">₱<?= number_format($discount, 0) ?></span>
        </div>
        <?php endif; ?>

        <div class="totals-row totals-row--total">
            <span class="totals-label totals-label--total">TOTAL:</span>
            <span class="totals-value totals-value--total">₱<?= number_format($total, 0) ?></span>
        </div>

        <div class="totals-row totals-row--payment">
            <span class="totals-label totals-label--payment">PAYMENT:</span>
            <span class="totals-value totals-value--payment">
                <?= strtoupper($order['payment_method']) ?>
            </span>
        </div>
    </div>

</div>

<?php require '../includes/footer.php'; ?>