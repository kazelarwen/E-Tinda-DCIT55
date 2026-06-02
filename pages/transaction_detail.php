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

$subtotal = array_sum(array_column($items, 'subtotal'));
$total    = (float)$order['total_amount'];
$discount = max(0, $subtotal - $total);

$id_padded = str_pad($order['id'], 3, '0', STR_PAD_LEFT);
$date_fmt  = date('F j, Y', strtotime($order['created_at']));
$time_fmt  = date('g:i a',  strtotime($order['created_at']));

$is_cancelled = $order['status'] === 'cancelled';

$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);

require '../includes/header.php';
?>

<div class="form-topbar">
    <a href="transaction_history.php" class="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
    </a>
    <h1 class="form-topbar-title">Transaction History</h1>
</div>

<div class="page-content detail-page">

    <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom:16px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Status badge if cancelled -->
    <?php if ($is_cancelled): ?>
        <div style="
            background: #FDEAEA;
            color: #C0392B;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: center;
        ">⚠ This order has been cancelled</div>
    <?php endif; ?>

    <div class="detail-id-row">
        <span class="detail-id-label">TRANSACTION ID:</span>
        <span class="detail-id-num"><?= $id_padded ?></span>
    </div>

    <div class="detail-datetime-row">
        <span class="detail-date"><?= $date_fmt ?></span>
        <span class="detail-time"><?= $time_fmt ?></span>
    </div>

    <div class="detail-col-headers">
        <span class="col-qty">QTY</span>
        <span class="col-item">ITEM</span>
        <span class="col-amt">AMT</span>
    </div>

    <div class="detail-items">
        <?php foreach ($items as $item): ?>
        <div class="detail-item-row">
            <span class="col-qty"><?= $item['quantity'] ?></span>
            <span class="col-item"><?= strtoupper(htmlspecialchars($item['product_name'])) ?></span>
            <span class="col-amt"><?= number_format($item['subtotal'], 0) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

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

    <!-- Cancel button — only show if not already cancelled -->
    <?php if (!$is_cancelled): ?>
    <div style="padding: 24px 0 8px;">
        <button onclick="openOrderCancelModal()" style="
            width: 100%;
            padding: 14px;
            background: #C0392B;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        ">Cancel Order</button>
    </div>
    <?php endif; ?>

</div>

<!-- Cancel Order Modal -->
<div id="cancelModalOverlay" style="
    display: none;
    position: fixed;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    max-width: 430px;
    height: 100%;
    background: rgba(45,45,45,0.45);
    z-index: 400;
    align-items: center;
    justify-content: center;
">
    <div style="
        background: #fff;
        border-radius: 16px;
        padding: 28px 24px 24px;
        width: calc(100% - 48px);
        max-width: 320px;
        text-align: center;
        position: relative;
        font-family: 'Poppins', sans-serif;
    ">
        <button onclick="closeOrderCancelModal()" style="
            position: absolute;
            top: 12px;
            right: 14px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        ">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2D2D2D" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <div style="
            width: 52px;
            height: 52px;
            background: #FDEAEA;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        ">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C0392B" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>

        <div style="font-size: 18px; font-weight: 700; color: #2D2D2D; margin-bottom: 8px;">
            Cancel Order?
        </div>
        <div style="
            font-size: 13px;
            color: rgba(45,45,45,0.55);
            margin-bottom: 22px;
            line-height: 1.5;
        ">
            This will cancel the order and restore stock for all items.
        </div>

        <form method="POST" action="../actions/order_action.php">
            <input type="hidden" name="action" value="cancel_order">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button type="button" onclick="closeOrderCancelModal()" style="
                    padding: 12px;
                    background: #F2F2F7;
                    color: #2D2D2D;
                    border: none;
                    border-radius: 10px;
                    font-family: 'Poppins', sans-serif;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                ">Keep</button>
                <button type="submit" style="
                    padding: 12px;
                    background: #C0392B;
                    color: #fff;
                    border: none;
                    border-radius: 10px;
                    font-family: 'Poppins', sans-serif;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                ">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openOrderCancelModal() {
    document.getElementById('cancelModalOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeOrderCancelModal() {
    document.getElementById('cancelModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeOrderCancelModal();
});

document.getElementById('cancelModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeOrderCancelModal();
});
</script>

<?php require '../includes/footer.php'; ?>