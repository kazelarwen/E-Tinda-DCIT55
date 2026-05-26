<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title = 'Transaction History — E-Tinda';
$page_css   = 'transaction_history.css';
$active_nav = 'history';
$vid        = $_SESSION['vendor_id'];

// Fetch all completed orders grouped by date
$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.total_amount,
        o.created_at,
        DATE(o.created_at) AS sale_date,
        o.payment_method
    FROM orders o
    WHERE o.vendor_id = ? AND o.status = 'completed'
    ORDER BY o.created_at DESC
");
$stmt->execute([$vid]);
$orders = $stmt->fetchAll();

// Group by date label
$grouped = [];
foreach ($orders as $order) {
    $date = $order['sale_date'];
    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($date === $today)         $label = 'TODAY';
    elseif ($date === $yesterday) $label = 'YESTERDAY';
    else                          $label = strtoupper(date('d M Y', strtotime($date)));

    $grouped[$label][] = $order;
}

require '../includes/header.php';
?>

<div class="page-content txn-page">

    <!-- Page title — no back arrow (this is a root nav page) -->
    <div class="txn-title-row">
        <h1 class="txn-title">Transaction History</h1>
    </div>

    <?php if (empty($grouped)): ?>
        <div class="txn-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3 3"/>
            </svg>
            <p>No transactions yet.</p>
        </div>

    <?php else: ?>
        <?php foreach ($grouped as $label => $items): ?>

        <!-- Date group label -->
        <p class="txn-group-label"><?= $label ?></p>

        <!-- List of transactions for this date -->
        <div class="txn-list">
            <?php foreach ($items as $order):
                $id_padded = str_pad($order['id'], 3, '0', STR_PAD_LEFT);
            ?>
            <a href="transaction_detail.php?id=<?= $order['id'] ?>" class="txn-row">
                <div class="txn-row-left">
                    <span class="txn-row-id">ID: <?= $id_padded ?></span>
                    <span class="txn-row-amount">₱<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <svg class="txn-row-chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </a>
            <?php endforeach; ?>
        </div>

        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php require '../includes/footer.php'; ?>