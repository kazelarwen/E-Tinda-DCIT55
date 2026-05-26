<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title = 'Add Product - E-Tinda';
$active_nav = 'inventory';
$page_css   = 'add_product.css';

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

require '../includes/header.php';
?>

<main class="add-product-page">
    <header class="add-product-topbar" style="padding-left: 10px;">
        <a href="inventory.php" class="add-product-back" aria-label="Back to inventory">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </a>
        <h1 class="add-product-title">Add product</h1>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-error" style="margin:0 26px 17px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form
        method="POST"
        action="../actions/product_action.php"
        enctype="multipart/form-data"
        id="addProductForm"
        class="add-product-form">
        <input type="hidden" name="action" value="add">

        <!-- Product name -->
        <div class="add-product-body">
            <div class="add-product-group">
                <label class="add-product-label" for="name">Product name</label>
                <input
                    class="add-product-input"
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your product name"
                    required
                    autocomplete="off">
            </div>

            <!-- Price -->
            <div class="add-product-group">
                <label class="add-product-label" for="price">Price</label>
                <div class="add-product-price">
                    <span class="add-product-prefix">PHP</span>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        required>
                </div>
            </div>

            <!-- Category -->
            <div class="add-product-group">
                <label class="add-product-label" for="category">Category</label>
                <div class="add-product-select-wrap">
                    <select class="add-product-select" id="category" name="category" required>
                        <option value="" disabled selected>Choose category</option>
                        <option value="Drinks">Drinks</option>
                        <option value="Cookies">Cookies</option>
                        <option value="Bread">Bread</option>
                        <option value="Snacks">Snacks</option>
                        <option value="Others">Others</option>
                    </select>
                    <svg class="add-product-chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </div>
            </div>

            <!-- Stock -->
            <div class="add-product-group">
                <label class="add-product-label" for="stock">Add Stock</label>
                <div class="add-product-stock">
                    <button type="button" aria-label="Decrease stock" onclick="changeQty(-1)">-</button>
                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        value="0"
                        min="0"
                        inputmode="numeric">
                    <button type="button" aria-label="Increase stock" onclick="changeQty(1)">+</button>
                </div>
            </div>

            <!-- Image -->
            <div class="add-product-group">
                <label class="add-product-label" for="image">Product image <span>(optional)</span></label>
                <label class="add-product-upload" for="image">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                        <path d="M4 16l4-4 4 4 4-6 4 6"/>
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                    </svg>
                    <p id="uploadLabel">Tap to choose an image</p>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/*"
                        hidden
                        onchange="previewImage(this)">
                </label>
                <img class="add-product-preview" id="imgPreview" src="" alt="Product preview">
            </div>
        </div>

        <div class="add-product-actions">
            <a href="inventory.php" class="btn btn-danger">Cancel</a>
            <button type="submit" class="btn btn-primary">Add</button>
        </div>
    </form>
</main>

<script>
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
</script>

<?php require '../includes/footer.php'; ?>
