<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title  = 'Home — E-Tinda';
$page_css    = 'home.css';
$active_nav  = 'home';
$vendor_id   = $_SESSION['vendor_id'];

/* ── Products query ───────────────────────────────── */
$active_category = $_GET['category'] ?? 'all';

// All distinct categories this vendor has
$stmt = $pdo->prepare("
    SELECT DISTINCT category FROM products
    WHERE vendor_id = ? AND category IS NOT NULL AND category != ''
    ORDER BY category
");
$stmt->execute([$vendor_id]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Products — filtered or all
if ($active_category === 'all') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC");
    $stmt->execute([$vendor_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? AND category = ? ORDER BY created_at DESC");
    $stmt->execute([$vendor_id, $active_category]);
}
$products = $stmt->fetchAll();

// Category → SVG path map
$cat_icons = [
    'Drinks'  => '<path d="M5 3h14M6 3l1 17h10L18 3M10 9h4"/>',
    'Cookies' => '<circle cx="12" cy="12" r="9"/><circle cx="9" cy="10" r="1" fill="currentColor"/><circle cx="14" cy="9" r="1" fill="currentColor"/><circle cx="11" cy="14" r="1" fill="currentColor"/>',
    'Bread'   => '<path d="M3 11a6 6 0 0112 0v1H3v-1z"/><rect x="2" y="12" width="14" height="5" rx="1"/>',
    'Snacks'  => '<path d="M12 2a10 10 0 100 20A10 10 0 0012 2z"/><path d="M8 12h8M12 8v8"/>',
    'default' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
];

// Vendor initials for avatar
$initials = strtoupper(substr($_SESSION['vendor_name'] ?? 'V', 0, 1));

require '../includes/header.php';
?>

<!-- ── Blue top bar ──────────────────────────────── -->
<div class="topbar">
    <div class="topbar-search">
        <!-- Search icon -->
        <svg viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
            type="text"
            placeholder="Search Item"
            oninput="filterProducts(this.value)">
    </div>

    <!-- Avatar → goes to profile -->
    <a href="profile.php" class="topbar-avatar">
        <?= $initials ?>
    </a>
</div>

<!-- ── Page content (scrollable) ─────────────────── -->
<div class="page-content">

    <!-- Category tabs -->
    <div class="categories">

        <!-- All Products tab -->
        <a href="home.php" class="cat-tab <?= $active_category === 'all' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                <?= $cat_icons['default'] ?>
            </svg>
            <span>All Products</span>
        </a>

        <!-- Dynamic category tabs -->
        <?php foreach ($categories as $cat): ?>
        <a href="home.php?category=<?= urlencode($cat) ?>"
           class="cat-tab <?= $active_category === $cat ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                <?= $cat_icons[$cat] ?? $cat_icons['default'] ?>
            </svg>
            <span><?= htmlspecialchars($cat) ?></span>
        </a>
        <?php endforeach; ?>

    </div><!-- /.categories -->

    <!-- Product grid -->
    <div class="product-grid" id="productGrid">

        <!-- Add New Product tile -->
        <a href="add_product.php" class="product-card-add">
            <span class="plus">+</span>
            <span>Add New Product</span>
        </a>

        <!-- Product cards -->
        <?php foreach ($products as $p): ?>
        <div class="product-card" data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">

            <!-- Image -->
            <?php if (!empty($p['image']) && $p['image'] !== 'placeholder.png'): ?>
                <img class="card-img"
                     src="../assets/uploads/<?= htmlspecialchars($p['image']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>"
                     onerror="this.parentElement.innerHTML='<div class=\'card-img-placeholder\'>🛍</div>'">
            <?php else: ?>
                <div class="card-img-placeholder">🛍</div>
            <?php endif; ?>

            <div class="card-body">
                <div class="card-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="card-price">₱<?= number_format($p['price'], 0) ?></div>
            </div>

            <div class="card-footer">
                <?php if ($p['stock'] <= 0 || !$p['is_available']): ?>
                    <span class="badge-out">Out of stock</span>
                <?php else: ?>
                    <span></span><!-- spacer -->
                    <button class="btn-add-icon"
                            onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>', <?= $p['price'] ?>)">
                        +
                    </button>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>

        <!-- Empty state -->
        <?php if (empty($products)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:40px 20px;color:var(--text-muted);font-size:13px;">
            No products yet. Add your first product!
        </div>
        <?php endif; ?>

    </div><!-- /.product-grid -->

</div><!-- /.page-content -->

<!-- Cart bar — slides up when items are added -->
<div class="cart-bar" id="cartBar">
    <button class="cart-cancel" onclick="clearCart()">Cancel</button>

    <!-- Clicking the item count saves cart to PHP session then goes to order summary -->
    <button class="cart-proceed" id="cartBarLink" onclick="goToSummary()">
        <svg viewBox="0 0 24 24">
            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
        </svg>
        <span id="cartCountLabel">0 items</span>
        <svg viewBox="0 0 24 24" style="width:16px;height:16px;">
            <polyline points="9 18 15 12 9 6"/>
        </svg>
    </button>
</div>

<script>
// Cart stored in sessionStorage — survives page navigation
let cart = JSON.parse(sessionStorage.getItem('etinda_cart') || '[]');

function saveCart()  { sessionStorage.setItem('etinda_cart', JSON.stringify(cart)); }

function updateCartBar() {
    const total = cart.reduce((sum, i) => sum + i.qty, 0);
    const bar   = document.getElementById('cartBar');
    const label = document.getElementById('cartCountLabel');
    label.textContent = total + (total === 1 ? ' item' : ' items');
    bar.classList.toggle('visible', total > 0);
}

function addToCart(id, name, price) {
    const existing = cart.find(i => i.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id, name, price, qty: 1 });
    }
    saveCart();
    updateCartBar();

    // Quick visual feedback
    const btn = event.currentTarget;
    btn.style.transform = 'scale(1.3)';
    setTimeout(() => { btn.style.transform = ''; }, 180);
}

function clearCart() {
    cart = [];
    saveCart();
    updateCartBar();
}

// POST cart to PHP session, then navigate to order summary
function goToSummary() {
    if (cart.length === 0) return;

    const btn = document.getElementById('cartBarLink');
    btn.disabled = true;
    btn.style.opacity = '0.7';

    fetch('../actions/save_cart.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ cart })
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            window.location.href = 'order_summary.php';
        } else {
            alert('Could not load order summary: ' + (data.msg || 'unknown error'));
            btn.disabled = false;
            btn.style.opacity = '';
        }
    })
    .catch(() => {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.style.opacity = '';
    });
}

// Search: hide cards that don't match
function filterProducts(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.dataset.name || '';
        card.style.display = name.includes(q) ? '' : 'none';
    });
}

// Restore cart state on page load
updateCartBar();
</script>

<?php require '../includes/footer.php'; ?>