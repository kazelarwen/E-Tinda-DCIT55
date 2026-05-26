<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title = 'Edit Product — E-Tinda';
$active_nav = 'inventory';
$page_css   = 'edit_product.css';

// Get product ID from URL
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: inventory.php");
    exit;
}

// Fetch the product — must belong to this vendor
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
$stmt->execute([$id, $_SESSION['vendor_id']]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: inventory.php");
    exit;
}

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
        <input type="hidden" name="id" value="<?= $product['id'] ?>">

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
                        <?php
                        $cats = ['Drinks','Cookies','Bread','Snacks','Others'];
                        foreach ($cats as $cat): ?>
                            <option value="<?= $cat ?>"
                                <?= $product['category'] === $cat ? 'selected' : '' ?>>
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
                <div class="qty-input-wrap">
                    <button type="button" class="qty-btn qty-minus" onclick="changeQty(-1)">−</button>
                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        value="<?= $product['stock'] ?>"
                        min="0"
                        class="qty-input">
                    <button type="button" class="qty-btn qty-plus" onclick="changeQty(1)">+</button>
                </div>
            </div>

            <!-- Current image + upload new -->
            <div class="form-group">
                <label>Product Image</label>
                <?php if ($product['image'] && $product['image'] !== 'placeholder.png'): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($product['image']) ?>"
                         alt="Current image" id="imgPreview"
                         style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:10px;">
                <?php else: ?>
                    <img id="imgPreview" src="" alt="Preview"
                         style="display:none;width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:10px;">
                <?php endif; ?>

                <label class="upload-zone" for="image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:28px;height:28px;margin:0 auto;">
                        <path d="M4 16l4-4 4 4 4-6 4 6"/>
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                    </svg>
                    <p id="uploadLabel">Tap to replace image (optional)</p>
                    <input type="file" id="image" name="image" accept="image/*"
                           style="display:none" onchange="previewImage(this)">
                </label>
            </div>

        </div><!-- /.product-form-body -->

        <!-- Single full-width Save Changes button -->
        <div class="form-action-bar one-btn">
            <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
        </div>

    </form>
</div>

<script>
function changeQty(delta) {
    const input = document.getElementById('stock');
    let val = parseInt(input.value) || 0;
    val = Math.max(0, val + delta);
    input.value = val;
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
</script>

<?php require '../includes/footer.php'; ?>