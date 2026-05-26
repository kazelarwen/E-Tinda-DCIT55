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
                    <select id="category" name="category" required>
                        <option value="" disabled>Choose category</option>
                        <?php foreach (['Drinks','Cookies','Bread','Snacks','Others'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= $product['category'] === $cat ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
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

<!-- ── Delete confirmation modal ───────────────────── -->
<div class="modal-backdrop" id="deleteBackdrop" onclick="closeDeleteModal()"></div>

<div class="modal-sheet" id="deleteModal" role="dialog" aria-modal="true"
     aria-labelledby="modalTitle">

    <button class="modal-close" onclick="closeDeleteModal()" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
    </button>

    <h2 class="modal-title" id="modalTitle">Delete product</h2>
    <p class="modal-body">
        Do you really want to delete this product?<br>This action cannot be undone.
    </p>

    <button class="btn btn-danger btn-full modal-confirm-btn"
            onclick="document.getElementById('deleteForm').submit()">
        Delete product
    </button>

</div>

<script>
function changeQty(delta) {
    const input = document.getElementById('stock');
    input.value = Math.max(0, (parseInt(input.value, 10) || 0) + delta);
}

function previewImage(input) {
    const preview = document.getElementById('imgPreview');
    const label   = document.getElementById('uploadLabel');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            label.textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openDeleteModal() {
    document.getElementById('deleteBackdrop').classList.add('show');
    document.getElementById('deleteModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteBackdrop').classList.remove('show');
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Close on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDeleteModal();
});
</script>

<?php require '../includes/footer.php'; ?>