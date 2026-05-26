<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title = 'Order Summary — E-Tinda';
$active_nav = 'home';
$page_css   = 'order_summary.css';

// Cart lives in session: $_SESSION['cart'] = ['product_id' => qty, ...]
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: home.php");
    exit;
}

// Fetch product details for every item in cart
$ids          = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt         = $pdo->prepare(
    "SELECT * FROM products
     WHERE id IN ($placeholders)
       AND vendor_id = ?
       AND is_available = 1"
);
$stmt->execute([...$ids, $_SESSION['vendor_id']]);
$products = [];
foreach ($stmt->fetchAll() as $p) {
    $products[$p['id']] = $p;
}

// Calculate total
$total = 0;
foreach ($cart as $pid => $qty) {
    if (isset($products[$pid])) {
        $total += $products[$pid]['price'] * $qty;
    }
}

$error = $_SESSION['error'] ?? ''; unset($_SESSION['error']);

require '../includes/header.php';
?>

<div class="form-topbar">
    <a href="home.php" class="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
    </a>
    <h1 class="form-topbar-title">Order Summary</h1>
</div>

<div class="page-content">

    <?php if ($error): ?>
        <div class="alert alert-error" style="margin:16px 20px 0;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Order item rows -->
    <div class="summary-list">
        <?php foreach ($cart as $pid => $qty):
            if (!isset($products[$pid])) continue;
            $p        = $products[$pid];
            $subtotal = $p['price'] * $qty;
        ?>
        <div class="summary-row" data-id="<?= $pid ?>">
            <!-- Product image -->
            <div class="summary-img-wrap">
                <?php if ($p['image'] && $p['image'] !== 'placeholder.png'): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($p['image']) ?>"
                         alt="<?= htmlspecialchars($p['name']) ?>"
                         class="summary-img">
                <?php else: ?>
                    <div class="summary-img-placeholder">🛍</div>
                <?php endif; ?>
            </div>

            <!-- Name + price -->
            <div class="summary-info">
                <span class="summary-name"><?= htmlspecialchars($p['name']) ?></span>
                <span class="summary-price">₱<?= number_format($p['price'], 0) ?></span>
            </div>

            <!-- Subtotal top right -->
            <span class="summary-subtotal">₱<?= number_format($subtotal, 0) ?></span>

            <!-- Qty stepper -->
            <div class="summary-stepper">
                <button type="button" class="stepper-btn"
                        onclick="updateQty(<?= $pid ?>, -1)">−</button>
                <span class="stepper-val" id="qty-<?= $pid ?>"><?= $qty ?></span>
                <button type="button" class="stepper-btn stepper-plus"
                        onclick="updateQty(<?= $pid ?>, 1)">+</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Payment Method -->
    <div class="section" style="padding-top:20px;">
        <p class="section-title">Payment Method</p>
        <div class="select-wrap">
            <select id="paymentMethod" name="payment_method">
                <option value="cash" selected>Cash</option>
                <option value="gcash">GCash</option>
                <option value="others">Others</option>
            </select>
            <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </div>
    </div>

</div><!-- /.page-content -->

<!-- Sticky bottom total + action buttons -->
<div class="order-bottom-bar">
    <div class="order-total-line">
        <span class="total-label-lg">Total:</span>
        <span class="total-value-lg" id="grandTotal">₱<?= number_format($total, 0) ?></span>
    </div>
    <div class="form-action-bar two-btn" style="margin:0;padding:0 20px 20px;">
        <!-- Opens the cancel confirmation modal instead of navigating away directly -->
        <button type="button" class="btn btn-danger" style="flex:1"
                onclick="openCancelModal()">Cancel</button>

        <!-- POST form to place the order -->
        <form method="POST" action="../actions/order_action.php" id="orderForm" style="flex:1;display:flex;">
            <input type="hidden" name="payment_method" id="paymentInput" value="cash">
            <?php foreach ($cart as $pid => $qty): ?>
                <input type="hidden" name="items[<?= $pid ?>]"
                       value="<?= (int)$qty ?>" id="input-<?= $pid ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary" style="width:100%;">Make Order</button>
        </form>
    </div>
</div>

<script>
// Live cart data in JS (mirrors PHP session)
const prices = {
    <?php foreach ($cart as $pid => $qty):
        if (!isset($products[$pid])) continue; ?>
    <?= $pid ?>: <?= $products[$pid]['price'] ?>,
    <?php endforeach; ?>
};

let qtys = {
    <?php foreach ($cart as $pid => $qty): ?>
    <?= $pid ?>: <?= (int)$qty ?>,
    <?php endforeach; ?>
};

function updateQty(pid, delta) {
    qtys[pid] = Math.max(0, (qtys[pid] || 0) + delta);
    document.getElementById('qty-' + pid).textContent = qtys[pid];

    // Update hidden input
    const hiddenInput = document.getElementById('input-' + pid);
    if (hiddenInput) hiddenInput.value = qtys[pid];

    // Recalculate grand total
    let total = 0;
    for (const id in qtys) {
        total += (prices[id] || 0) * qtys[id];
    }
    document.getElementById('grandTotal').textContent = '₱' + total.toLocaleString();

    // Update subtotal in row
    const row      = document.querySelector('[data-id="' + pid + '"]');
    const subtotal = (prices[pid] || 0) * qtys[pid];
    row.querySelector('.summary-subtotal').textContent = '₱' + subtotal.toLocaleString();
}

// Sync payment method to hidden input before submit
document.getElementById('paymentMethod').addEventListener('change', function() {
    document.getElementById('paymentInput').value = this.value;
});
</script>

<?php require '../includes/cancel_modal.php'; ?>
<?php require '../includes/footer.php'; ?>