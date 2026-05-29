<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title = 'Edit Product — E-Tinda';
$active_nav = 'inventory';
$page_css   = 'edit_product.css';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: inventory.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
$stmt->execute([$id, $_SESSION['vendor_id']]);
$product = $stmt->fetch();
if (!$product) { header("Location: inventory.php"); exit; }

$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);

// Check if the saved category is a custom one (not in the default list)
$default_categories = ['Drinks', 'Cookies', 'Bread', 'Snacks', 'Others'];
$is_custom = !in_array($product['category'], $default_categories);

require '../includes/header.php';
?>

<div class="form-topbar">
    <a href="inventory.php" class="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
    </a>
    <h1 class="form-topbar-title">Edit product</h1>
</div>

<div class="page-content">

    <?php if ($error): ?>
        <div class="alert alert-error" style="margin:16px 20px 0;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin:16px 20px 0;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="../actions/product_action.php"
          enctype="multipart/form-data" id="editProductForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id"     value="<?= $product['id'] ?>">

        <div class="product-form-body">

            <!-- Product name -->
            <div class="form-group">
                <label for="name">Product name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your product name"
                    value="<?= htmlspecialchars($product['name']) ?>"
                    required
                    autocomplete="off">
            </div>

            <!-- Price -->
            <div class="form-group">
                <label for="price">Price</label>
                <div class="input-prefix-wrap">
                    <span class="input-prefix">PHP</span>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        placeholder="0.00"
                        value="<?= $product['price'] ?>"
                        min="0"
                        step="0.01"
                        required>
                </div>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label for="category">Category</label>
                <div class="select-wrap">
                    <select id="category" name="category" required onchange="toggleOthers(this)">
                        <option value="" disabled>Choose category</option>
                        <?php foreach (['Drinks','Cookies','Bread','Snacks'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= $product['category'] === $cat ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>
                        <!-- If custom, select Others; otherwise check if Others was saved -->
                        <option value="Others" <?= ($is_custom || $product['category'] === 'Others') ? 'selected' : '' ?>>
                            Others
                        </option>
                    </select>
                    <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </div>

                <!-- Shows only when Others is selected -->
                <div id="othersGroup" style="margin-top: 10px; <?= $is_custom ? '' : 'display:none;' ?>">
                    <input
                        type="text"
                        id="custom_category"
                        name="custom_category"
                        placeholder="Enter category name"
                        value="<?= $is_custom ? htmlspecialchars($product['category']) : '' ?>"
                        <?= $is_custom ? 'required' : '' ?>
                        autocomplete="off">
                </div>
            </div>

            <!-- Stock -->
            <div class="form-group">
                <label>Add Stock</label>
                <div class="qty-input-wrap" style="background:var(--white);">
                    <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                    <input type="number" id="stock" name="stock" style="background:var(--white);"
                           value="<?= $product['stock'] ?>" min="0" class="qty-input">
                    <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                </div>
            </div>

            <!-- Edit image -->
            <div class="form-group">
                <label>Edit image</label>

                <?php if (!empty($product['image']) && $product['image'] !== 'placeholder.png'): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($product['image']) ?>"
                         id="imgPreview" alt="Current image" class="img-preview">
                <?php else: ?>
                    <img id="imgPreview" src="" alt="Preview" class="img-preview" style="display:none;">
                <?php endif; ?>

                <label class="upload-zone" for="image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                         style="width:26px;height:26px;margin:0 auto;">
                        <path d="M4 16l4-4 4 4 4-6 4 6"/>
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                    </svg>
                    <p id="uploadLabel">
                        <?= (!empty($product['image']) && $product['image'] !== 'placeholder.png')
                            ? 'Tap to replace image'
                            : 'Tap to choose an image' ?>
                    </p>
                    <input type="file" id="image" name="image" accept="image/*"
                           style="display:none;" onchange="previewImage(this)">
                </label>
            </div>

        </div><!-- /.product-form-body -->

        <!-- Action bar: Save + Delete -->
        <div class="form-action-bar">
            <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
            <button type="button" class="btn btn-delete btn-full" onclick="openDeleteModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     style="width:17px;height:17px;flex-shrink:0;">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14H6L5 6"/>
                    <path d="M10 11v6M14 11v6"/>
                    <path d="M9 6V4h6v2"/>
                </svg>
                Delete Product
            </button>
        </div>

    </form>

    <!-- Hidden delete form -->
    <form id="deleteForm" method="POST" action="../actions/product_action.php" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id"     value="<?= $product['id'] ?>">
    </form>

</div><!-- /.page-content -->

<!-- Delete modal -->
<div id="deleteModalOverlay" style="
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
        <button onclick="closeDeleteModal()" style="
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
            Delete product
        </div>
        <div style="
            font-size: 14px;
            color: rgba(45,45,45,0.45);
            margin-bottom: 22px;
            line-height: 1.5;
        ">Do you really want to delete this product?</div>
        <button onclick="document.getElementById('deleteForm').submit()" style="
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
        ">Delete product</button>
    </div>
</div>

<script>
function toggleOthers(select) {
    const othersGroup = document.getElementById('othersGroup');
    const customInput = document.getElementById('custom_category');

    if (select.value === 'Others') {
        othersGroup.style.display = 'block';
        customInput.required = true;
    } else {
        othersGroup.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
}

function changeQty(delta) {
    const input = document.getElementById('stock');
    const currentValue = parseInt(input.value, 10) || 0;
    input.value = Math.max(0, currentValue + delta);
}

function previewImage(input) {
    const preview = document.getElementById('imgPreview');
    const label = document.getElementById('uploadLabel');

    if (!input.files || !input.files[0]) {
        preview.style.display = 'none';
        preview.removeAttribute('src');
        label.textContent = 'Tap to choose an image';
        return;
    }

    const reader = new FileReader();
    reader.onload = event => {
        preview.src = event.target.result;
        preview.style.display = 'block';
        label.textContent = input.files[0].name;
    };
    reader.readAsDataURL(input.files[0]);
}

function openDeleteModal() {
    document.getElementById('deleteModalOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDeleteModal();
});

document.getElementById('deleteModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

<?php require '../includes/footer.php'; ?>