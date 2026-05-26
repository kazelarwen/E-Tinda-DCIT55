<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title = 'Inventory — E-Tinda';
$page_css   = 'inventory.css';
$active_nav = 'inventory';
$vid        = $_SESSION['vendor_id'];

$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);

$stmt = $pdo->prepare("
    SELECT * FROM products
    WHERE vendor_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$vid]);
$products = $stmt->fetchAll();

require '../includes/header.php';
?>

<div class="form-topbar">
    <h1 class="form-topbar-title">Inventory</h1>
</div>

<div class="page-content inv-page">

    <?php if ($success): ?>
        <div class="alert alert-success" style="margin:12px 20px 0;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error" style="margin:12px 20px 0;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="inv-add-wrap">
        <a href="add_product.php" class="inv-add-btn">
            Add product <span class="inv-add-plus">+</span>
        </a>
    </div>

    <?php if (empty($products)): ?>
        <div class="inv-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <p>No products yet. Add your first product!</p>
        </div>

    <?php else: ?>
    <div class="inv-list" id="invList">
        <?php foreach ($products as $p): ?>
        <div class="inv-item" id="item-<?= $p['id'] ?>">

            <label class="inv-checkbox-wrap">
                <input type="checkbox" class="inv-checkbox" value="<?= $p['id'] ?>">
                <span class="inv-checkmark"></span>
            </label>

            <div class="inv-img-wrap">
                <?php if (!empty($p['image']) && $p['image'] !== 'placeholder.png'): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($p['image']) ?>"
                         alt="<?= htmlspecialchars($p['name']) ?>"
                         class="inv-img"
                         onerror="this.src='../assets/img/placeholder.png'">
                <?php else: ?>
                    <div class="inv-img-placeholder">🛍</div>
                <?php endif; ?>
            </div>

            <div class="inv-info">
                <span class="inv-name"><?= htmlspecialchars($p['name']) ?></span>
                <span class="inv-price">₱<?= number_format($p['price'], 0) ?></span>
                <?php if (!$p['is_available'] || $p['stock'] <= 0): ?>
                    <span class="inv-unavailable">Unavailable</span>
                <?php else: ?>
                    <span class="inv-stock">Stock: <?= $p['stock'] ?></span>
                <?php endif; ?>
            </div>

            <a href="edit_product.php?id=<?= $p['id'] ?>" class="inv-edit-btn" title="Edit">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </a>

        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Delete bar: hidden by default, sits above navbar -->
<div id="invDeleteBar" style="
    display: none;
    position: fixed;
    bottom: var(--nav-height);
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    max-width: 430px;
    background: #3171C6;
    padding: 10px 16px;
    z-index: 250;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 -2px 12px rgba(45,45,45,0.15);
">
    <span id="invDeleteCount" style="
        color: #fff;
        font-size: 14px;
        font-weight: 500;
        font-family: 'Poppins', sans-serif;
    "></span>
    <button id="invDeleteBtn" style="
        background: #C0392B;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 22px;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    ">Delete</button>
</div>

<!-- Confirm modal: hidden by default -->
<div id="invModalOverlay" style="
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
        <button id="invModalClose" style="
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
        <div style="font-size: 18px; font-weight: 700; color: #2D2D2D; margin-bottom: 8px;">
            Delete items
        </div>
        <div id="invModalBody" style="
            font-size: 14px;
            color: rgba(45,45,45,0.45);
            margin-bottom: 22px;
            line-height: 1.5;
        ">Do you really want to delete the selected items?</div>
        <button id="invModalConfirmBtn" style="
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
        ">Delete items</button>
    </div>
</div>

<script>
(function () {
    var checkboxes  = document.querySelectorAll('.inv-checkbox');
    var deleteBar   = document.getElementById('invDeleteBar');
    var countLabel  = document.getElementById('invDeleteCount');
    var deleteBtn   = document.getElementById('invDeleteBtn');
    var overlay     = document.getElementById('invModalOverlay');
    var modalClose  = document.getElementById('invModalClose');
    var modalBody   = document.getElementById('invModalBody');
    var confirmBtn  = document.getElementById('invModalConfirmBtn');

    function getChecked() {
        return Array.prototype.filter.call(checkboxes, function(cb) {
            return cb.checked;
        });
    }

    function updateBar() {
        var checked = getChecked();
        var n = checked.length;

        if (n > 0) {
            var noun = n === 1 ? 'item' : 'items';
            countLabel.textContent = n + ' selected';
            modalBody.textContent  = 'Do you really want to delete ' + n + ' ' + noun + '?';
            confirmBtn.textContent = 'Delete ' + n + ' ' + noun;
            deleteBar.style.display   = 'flex';
            overlay.style.display     = 'flex'; /* pre-load flex so display:flex is ready */
            overlay.style.display     = 'none'; /* keep hidden until button tapped */
        } else {
            deleteBar.style.display = 'none';
            overlay.style.display   = 'none';
        }
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function () {
            var item = this.closest('.inv-item');
            if (this.checked) {
                item.classList.add('inv-item--checked');
            } else {
                item.classList.remove('inv-item--checked');
            }
            updateBar();
        });
    });

    deleteBtn.addEventListener('click', function () {
        overlay.style.display = 'flex';
    });

    modalClose.addEventListener('click', function () {
        overlay.style.display = 'none';
    });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            overlay.style.display = 'none';
        }
    });

    confirmBtn.addEventListener('click', function () {
        var ids = getChecked().map(function(cb) { return cb.value; });
        if (!ids.length) return;

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '../actions/product_action.php';

        var actionInput   = document.createElement('input');
        actionInput.type  = 'hidden';
        actionInput.name  = 'action';
        actionInput.value = 'bulk_delete';
        form.appendChild(actionInput);

        ids.forEach(function(id) {
            var input   = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    });
})();
</script>

<?php require '../includes/footer.php'; ?>