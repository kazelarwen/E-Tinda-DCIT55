<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title = 'Dashboard — E-Tinda';
$active_nav = 'dashboard';
$page_css   = 'dashboard.css';
$vid        = $_SESSION['vendor_id'];

/* ── TODAY'S OVERVIEW ─────────────────────────────────── */

// [SELECT + WHERE] Today's total sales → Today's Profit tile
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0) AS today_sales
    FROM sales
    WHERE vendor_id = ? AND sale_date = CURDATE()
");
$stmt->execute([$vid]);
$today_sales = (float)$stmt->fetchColumn();

// [SELECT + JOIN + WHERE] Total items sold today → Items Sold tile
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity), 0) AS items_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.vendor_id = ? AND o.status = 'completed'
      AND DATE(o.created_at) = CURDATE()
");
$stmt->execute([$vid]);
$items_sold_today = (int)$stmt->fetchColumn();

// [SELECT + WHERE] Total stock across all products → Items Left tile
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(stock), 0) AS total_stock
    FROM products
    WHERE vendor_id = ?
");
$stmt->execute([$vid]);
$total_stock = (int)$stmt->fetchColumn();

// [SELECT + WHERE] Total completed orders today → Orders Today tile
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS orders_today
    FROM orders
    WHERE vendor_id = ? AND status = 'completed'
      AND DATE(created_at) = CURDATE()
");
$stmt->execute([$vid]);
$orders_today = (int)$stmt->fetchColumn();

// [SELECT + WHERE] Yesterday's sales → % change badge on Profit tile
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0) AS y_sales
    FROM sales
    WHERE vendor_id = ? AND sale_date = CURDATE() - INTERVAL 1 DAY
");
$stmt->execute([$vid]);
$yesterday_sales = (float)$stmt->fetchColumn();

// % change calculation (profit tile badge)
$pct_change = 0;
if ($yesterday_sales > 0) {
    $pct_change = round((($today_sales - $yesterday_sales) / $yesterday_sales) * 100);
} elseif ($today_sales > 0) {
    $pct_change = 100;
}
$pct_up = $pct_change >= 0;

/* ── SALES SUMMARY (last 7 days) ──────────────────────── */

// [SELECT + WHERE] Weekly sales for bar chart
$stmt = $pdo->prepare("
    SELECT sale_date, SUM(total_amount) AS day_total
    FROM sales
    WHERE vendor_id = ?
      AND sale_date >= CURDATE() - INTERVAL 6 DAY
    GROUP BY sale_date
    ORDER BY sale_date ASC
");
$stmt->execute([$vid]);
$daily_sales_raw = $stmt->fetchAll();

// Fill missing days with 0
$days_map   = [];
$day_labels = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
for ($i = 6; $i >= 0; $i--) {
    $d            = date('Y-m-d', strtotime("-$i days"));
    $days_map[$d] = 0;
}
foreach ($daily_sales_raw as $row) {
    $days_map[$row['sale_date']] = (float)$row['day_total'];
}
$week_data  = array_values($days_map);
$week_dates = array_keys($days_map);
$today_str  = date('Y-m-d');
$max_val    = max(array_merge($week_data, [1]));

// [SELECT + WHERE] Cancelled orders today
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM orders
    WHERE vendor_id = ? AND status = 'cancelled'
      AND DATE(created_at) = CURDATE()
");
$stmt->execute([$vid]);
$cancelled_today = (int)$stmt->fetchColumn();

/* ── BEST SELLER ──────────────────────────────────────── */

// [SELECT + JOIN x2 + WHERE] Best-selling product today
$stmt = $pdo->prepare("
    SELECT p.name, p.category, p.price, SUM(oi.quantity) AS qty_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders   o ON oi.order_id   = o.id
    WHERE o.vendor_id = ? AND o.status = 'completed'
      AND DATE(o.created_at) = CURDATE()
    GROUP BY p.id
    ORDER BY qty_sold DESC
    LIMIT 1
");
$stmt->execute([$vid]);
$best_seller = $stmt->fetch();

/* ── LOW STOCK ────────────────────────────────────────── */

// [SELECT + JOIN + WHERE] Products whose current stock is <= 20% of their
$stmt = $pdo->prepare("
    SELECT
        p.name,
        p.stock,
        p.category,
        -- total units sold for this product (across all completed orders)
        COALESCE(sold.qty_sold, 0) AS qty_sold,
        -- original capacity = what's left + what was sold
        (p.stock + COALESCE(sold.qty_sold, 0)) AS capacity,
        -- percentage remaining  (0–100)
        CASE
            WHEN (p.stock + COALESCE(sold.qty_sold, 0)) = 0 THEN 0
            ELSE ROUND(
                (p.stock / (p.stock + COALESCE(sold.qty_sold, 0))) * 100
            )
        END AS stock_pct
    FROM products p
    LEFT JOIN (
        -- sub-query: sum quantities per product for completed orders
        SELECT oi.product_id, SUM(oi.quantity) AS qty_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.vendor_id = ? AND o.status = 'completed'
        GROUP BY oi.product_id
    ) AS sold ON sold.product_id = p.id
    WHERE p.vendor_id = ?
      -- only show if current stock is 20% or less of original capacity
      AND p.stock <= (p.stock + COALESCE(sold.qty_sold, 0)) * 0.20
    ORDER BY stock_pct ASC
    LIMIT 8
");
$stmt->execute([$vid, $vid]);
$low_stock = $stmt->fetchAll();

// Stock % for Items Left tile progress bar
// Capacity = current stock + total units already sold (true fill level)
$stmt3 = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity), 0)
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.vendor_id = ? AND o.status = 'completed'
");
$stmt3->execute([$vid]);
$total_sold_ever = (int)$stmt3->fetchColumn();

// total_capacity = what you have now + what was already sold
// e.g. 10 left + 40 sold = started with 50 → bar shows 20%
$total_capacity = $total_stock + $total_sold_ever;
$stock_pct      = ($total_capacity > 0)
    ? min(100, round(($total_stock / $total_capacity) * 100))
    : 100; // no orders yet → treat as fully stocked

require '../includes/header.php';
?>

<div class="page-content db-page">

    <!-- Page title -->
    <div class="db-title-row">
        <h1 class="db-title">Dashboard</h1>
    </div>

    <!-- ══ TODAY'S OVERVIEW ════════════════════════ -->
    <p class="section-label">TODAY'S OVERVIEW</p>

    <div class="overview-grid">

        <!-- Tile 1: Today's Profit — blue -->
        <div class="stat-tile stat-tile--blue">
            <div class="tile-header">
                <span class="tile-label">Today's Profit</span>
                <div class="tile-icon tile-icon--white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 6v12M9 9h4.5a1.5 1.5 0 010 3H9m0 0h5.5a1.5 1.5 0 010 3H9"/>
                    </svg>
                </div>
            </div>
            <div class="tile-value">₱<?= number_format($today_sales, 0) ?></div>
            <div class="tile-sub">Revenue after costs</div>
            <div class="tile-badge tile-badge--white">
                <?= $pct_up ? '▲' : '▼' ?> <?= abs($pct_change) ?>% from yesterday
            </div>
        </div>

        <!-- Tile 2: Items Sold — white -->
        <div class="stat-tile stat-tile--white">
            <div class="tile-header">
                <span class="tile-label">Items sold</span>
                <div class="tile-icon tile-icon--blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--electric-blue)" stroke-width="1.8">
                        <rect x="5" y="2" width="14" height="20" rx="2"/>
                        <path d="M9 7h6M9 11h6M9 15h4"/>
                    </svg>
                </div>
            </div>
            <div class="tile-value tile-value--dark"><?= $items_sold_today ?></div>
            <div class="tile-badge tile-badge--green">▲ from yesterday</div>
        </div>

        <!-- Tile 3: Items Left (stock) — white -->
        <div class="stat-tile stat-tile--white">
            <div class="tile-header">
                <span class="tile-label">Items left</span>
                <div class="tile-icon tile-icon--blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--electric-blue)" stroke-width="1.8">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        <path d="M9 22V12h6v10"/>
                    </svg>
                </div>
            </div>
            <div class="tile-value tile-value--dark"><?= $total_stock ?></div>
            <div class="stock-bar-wrap">
                <div class="stock-bar">
                    <div class="stock-bar-fill" style="width:<?= $stock_pct ?>%"></div>
                </div>
                <span class="stock-label"><?= $stock_pct ?>% stock</span>
            </div>
        </div>

        <!-- Tile 4: Orders Today — white -->
        <div class="stat-tile stat-tile--white">
            <div class="tile-header">
                <span class="tile-label">Orders Today</span>
                <div class="tile-icon tile-icon--blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--electric-blue)" stroke-width="1.8">
                        <rect x="5" y="2" width="14" height="20" rx="2"/>
                        <path d="M9 7h6M9 11h6M9 15h6"/>
                        <polyline points="9 15 11 17 15 13"/>
                    </svg>
                </div>
            </div>
            <div class="tile-value tile-value--dark"><?= $orders_today ?></div>
            <div class="tile-badge tile-badge--red">▼ from yesterday</div>
        </div>

    </div><!-- /.overview-grid -->

    <!-- ══ SALES SUMMARY ════════════════════════════ -->
    <p class="section-label">SALES SUMMARY</p>

    <div class="sales-card">
        <div class="sales-card-header">
            <span class="sales-card-title">Today's Sales</span>
            <span class="sales-today-pill">Today</span>
        </div>

        <div class="sales-big-value">₱<?= number_format($today_sales, 0) ?></div>
        <div class="sales-sub">Revenue before costs</div>

        <!-- 7-day mini bar chart -->
        <div class="week-chart">
            <?php foreach ($week_data as $i => $val):
                $date      = $week_dates[$i];
                $day_name  = $day_labels[(int)date('w', strtotime($date))];
                $is_today  = $date === $today_str;
                $bar_h     = $max_val > 0 ? max(4, round(($val / $max_val) * 40)) : 4;
                $label_val = $val >= 1000
                    ? '₱' . round($val / 1000, 1) . 'k'
                    : ($val > 0 ? '₱' . number_format($val, 0) : '—');
            ?>
            <div class="week-col <?= $is_today ? 'week-col--today' : '' ?>">
                <span class="week-amount"><?= $label_val ?></span>
                <div class="week-bar-wrap">
                    <div class="week-bar" style="height:<?= $bar_h ?>px"></div>
                </div>
                <span class="week-day"><?= $day_name ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cancelled-row">
            <span class="cancelled-label">Cancelled Orders</span>
            <span class="cancelled-val"><?= $cancelled_today ?></span>
        </div>
    </div>

    <!-- ══ BEST-SELLING PRODUCT ══════════════════════ -->
    <p class="section-label">BEST-SELLING PRODUCT</p>

    <?php if ($best_seller): ?>
    <div class="best-seller-card">
        <div class="best-seller-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--electric-blue)" stroke-width="1.5">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
        </div>
        <div class="best-seller-info">
            <span class="best-seller-badge">⭐ Today's Best Seller</span>
            <span class="best-seller-name"><?= htmlspecialchars($best_seller['name']) ?></span>
            <span class="best-seller-sub">
                <?= htmlspecialchars($best_seller['category']) ?> · ₱<?= number_format($best_seller['price'], 2) ?> per item
            </span>
        </div>
        <div class="best-seller-qty">
            <span class="best-qty-num"><?= $best_seller['qty_sold'] ?></span>
            <span class="best-qty-label">Sold Today</span>
        </div>
    </div>
    <?php else: ?>
    <div class="best-seller-card best-seller-empty">
        <p>No sales recorded yet today.</p>
    </div>
    <?php endif; ?>

    <!-- ══ LOW STOCK ALERTS ══════════════════════════ -->
    <p class="section-label">LOW STOCK ALERTS</p>

    <?php if (empty($low_stock)): ?>
    <div class="lowstock-card">
        <div class="lowstock-all-good">
            <div class="lowstock-good-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"
                     style="width:18px;height:18px;flex-shrink:0">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <span>ALL PRODUCTS ARE IN STOCK</span>
            </div>
        </div>
        <div class="lowstock-empty-body">
            <p>No items need restocking for now!</p>
        </div>
    </div>

    <?php else: ?>
    <div class="lowstock-card">
        <div class="lowstock-warn-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                 style="width:18px;height:18px;flex-shrink:0">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span><?= count($low_stock) ?> MENU ITEMS NEED RESTOCKING</span>
        </div>

        <div class="lowstock-list">
            <?php foreach ($low_stock as $item):
                // stock_pct comes directly from the SQL query (0–20 range since threshold is 20%)
                $pct = (int)$item['stock_pct'];

                // Bar color based on % remaining:
                // red = 0–5%, yellow = 6–15%, green = 16–20%
                if ($pct <= 5)       $bar_color = '#E24B4A'; // critical
                elseif ($pct <= 15)  $bar_color = '#EF9F27'; // warning
                else                 $bar_color = '#3DAB6E'; // borderline
            ?>
            <div class="lowstock-item">
                <div class="lowstock-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--electric-blue)"
                         stroke-width="1.8" style="width:20px;height:20px">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        <path d="M9 22V12h6v10"/>
                    </svg>
                </div>
                <div class="lowstock-item-info">
                    <span class="lowstock-item-name"><?= htmlspecialchars($item['name']) ?></span>
                    <div class="lowstock-bar-row">
                        <div class="lowstock-bar-bg">
                            <div class="lowstock-bar-fill"
                                 style="width:<?= $pct ?>%;background:<?= $bar_color ?>"></div>
                        </div>
                        <span class="lowstock-count"><?= $item['stock'] ?> Left</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div style="height:24px"></div>

</div><!-- /.page-content -->


<?php require '../includes/footer.php'; ?>