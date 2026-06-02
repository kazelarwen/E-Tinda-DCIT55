<?php
require '../includes/auth.php';
require '../includes/db.php';

$page_title = 'Profile — E-Tinda';
$page_css   = 'profile.css';
$active_nav = 'profile';
$vendor_id  = $_SESSION['vendor_id'];
$avatar_error = $_SESSION['avatar_error'] ?? ''; 
unset($_SESSION['avatar_error']);

// Fetch vendor info
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();

// Vendor initials for avatar
$initials = strtoupper(substr($vendor['vendor_name'] ?? 'V', 0, 1));

$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);

require '../includes/header.php';
?>

<!-- Blue hero header -->
<div class="profile-hero">
    <a href="home.php" class="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        ‹ Home
    </a>
    <h2 class="profile-title">Profile</h2>

    <!-- Avatar with edit button -->
    <div class="profile-avatar-wrap" onclick="openAvatarModal()">
    <?php if (!empty($vendor['profile_image']) && $vendor['profile_image'] !== 'default.png'): ?>
        <img src="../assets/uploads/<?= htmlspecialchars($vendor['profile_image']) ?>"
             class="profile-avatar-img">
    <?php else: ?>
        <div class="profile-avatar">
            <?= $initials ?>
        </div>
    <?php endif; ?>
    <div class="profile-avatar-edit">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:12px;height:12px;">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
    </div>
</div>

    <div class="profile-name"><?= htmlspecialchars($vendor['vendor_name']) ?></div>
    <div class="profile-stall"><?= htmlspecialchars($vendor['stall_name']) ?></div>
</div>

<!-- Content -->
<div class="profile-content">

    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom:16px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>


    <!-- Account Info -->
    <div class="section-header-row">
        <span class="section-label" style="margin:0;">ACCOUNT INFO</span>
        <button class="edit-btn" onclick="openEditModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" style="width:14px;height:14px;">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit
        </button>
    </div>

    <div class="info-card">

        <div class="info-row">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="info-text">
                <span class="info-label">Vendor Name</span>
                <span class="info-value"><?= htmlspecialchars($vendor['vendor_name']) ?></span>
            </div>
        </div>

        <div class="info-divider"></div>

        <div class="info-row">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="info-text">
                <span class="info-label">Stall Name</span>
                <span class="info-value"><?= htmlspecialchars($vendor['stall_name']) ?></span>
            </div>
        </div>

        <div class="info-divider"></div>

        <div class="info-row">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="info-text">
                <span class="info-label">Contact Number</span>
                <span class="info-value">
                    <?php
                    $contact = $vendor['contact_number'] ?? '';
                    echo $contact ? '+63 ' . htmlspecialchars(ltrim($contact, '0')) : 'Not set';
                    ?>
                </span>
            </div>
        </div>

    </div>

    <!-- Account Actions -->
    <div class="section-label">ACCOUNT ACTIONS</div>
    <div class="info-card">

        <a href="../logout.php" class="info-row">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </div>
            <div class="info-text">
                <span class="info-value">Log Out</span>
            </div>
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>

        <div class="info-divider"></div>

        <a href="#" class="info-row danger" onclick="openDeleteModal(); return false;">
            <div class="info-icon danger-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    <path d="M10 11v6M14 11v6"/>
                    <path d="M9 6V4h6v2"/>
                </svg>
            </div>
            <div class="info-text">
                <span class="info-value danger-text">Delete Account</span>
            </div>
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>

    </div>

</div>

<!-- EDIT PROFILE MODAL -->
<div id="editModalOverlay" class="modal-overlay" 
     style="pointer-events:none;"
     onclick="if(event.target===this)closeEditModal()">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeEditModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2D2D2D" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <h2 class="modal-box-title">Edit Profile</h2>

        <form method="POST" action="../actions/vendor_action.php">
            <input type="hidden" name="action" value="update_profile">

            <div class="modal-field">
                <label class="modal-label">Vendor Name</label>
                <input class="modal-input" type="text" name="vendor_name"
                       value="<?= htmlspecialchars($vendor['vendor_name']) ?>" required>
            </div>

            <div class="modal-field">
                <label class="modal-label">Stall Name</label>
                <input class="modal-input" type="text" name="stall_name"
                       value="<?= htmlspecialchars($vendor['stall_name']) ?>" required>
            </div>

            <div class="modal-field">
                <label class="modal-label">Contact Number</label>
                <input class="modal-input" type="text" name="contact_number"
                       placeholder="e.g. 09123456789"
                       value="<?= htmlspecialchars($vendor['contact_number'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                Save Changes
            </button>
        </form>
    </div>
</div>

<!-- AVATAR MODAL -->
<div id="avatarModalOverlay" class="modal-overlay" style="pointer-events:none;" onclick="if(event.target===this)closeAvatarModal()">
    <div class="modal-box" style="text-align:center;">
        <button class="modal-close-btn" onclick="closeAvatarModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2D2D2D" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
        <?php if ($avatar_error): ?>
        <div style="font-size:13px;color:#C0392B;margin-bottom:12px;">
            <?= htmlspecialchars($avatar_error) ?>
        </div>
        <?php endif; ?>

        <h2 class="modal-box-title">Profile Picture</h2>

        <!-- Current avatar -->
        <div style="margin: 0 auto 20px;">
            <?php if (!empty($vendor['profile_image']) && $vendor['profile_image'] !== 'default.png'): ?>
                <img src="../assets/uploads/<?= htmlspecialchars($vendor['profile_image']) ?>"
                     style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
            <?php else: ?>
                <div class="profile-avatar" style="margin:0 auto;width:80px;height:80px;font-size:32px;">
                    <?= $initials ?>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" action="../actions/vendor_action.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_avatar">

            <label class="modal-upload-label" for="profile_image">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" style="width:20px;height:20px;">
                    <path d="M4 16l4-4 4 4 4-6 4 6"/>
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                </svg>
                <span id="avatarLabel">Tap to choose an image</span>
                <input type="file" id="profile_image" name="profile_image"
                       accept="image/*" style="display:none;"
                       onchange="previewAvatar(this)">
            </label>

            <img id="avatarPreview" src="" alt="Preview"
                 style="display:none;width:80px;height:80px;border-radius:50%;object-fit:cover;margin:12px auto;">

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:12px;">
                Save Picture
            </button>
        </form>
    </div>
</div>

<!-- DELETE ACCOUNT MODAL -->
<div id="deleteModalOverlay" class="modal-overlay" 
     style="pointer-events:none;"
     onclick="if(event.target===this)closeDeleteModal()">
    <div class="modal-box" style="text-align:center;">
        <button class="modal-close-btn" onclick="closeDeleteModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2D2D2D" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <div style="width:52px;height:52px;background:#FDEAEA;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C0392B" stroke-width="2">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/>
                <path d="M9 6V4h6v2"/>
            </svg>
        </div>

        <div style="font-size:18px;font-weight:700;color:#2D2D2D;margin-bottom:8px;">Delete Account?</div>
        <div style="font-size:13px;color:rgba(45,45,45,0.55);margin-bottom:20px;line-height:1.5;">
            This action is <strong>permanent</strong> and cannot be undone.<br>
            Enter your password to continue.
        </div>

        <form method="POST" action="../actions/vendor_action.php">
            <input type="hidden" name="action" value="delete_account">

            <div style="position:relative;margin-bottom:16px;">
                <input type="password" id="delete-password" name="password"
                    placeholder="Enter your password" required
                    style="width:100%;padding:12px 44px 12px 14px;border:1px solid #E0E0E0;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;color:#2D2D2D;outline:none;box-sizing:border-box;">
                <button type="button" onclick="toggleDeletePassword()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;display:flex;align-items:center;">
                    <svg id="delete-eye-icon" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" width="18" height="18">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>

            <?php if ($error): ?>
                <div style="font-size:13px;color:#C0392B;margin-bottom:12px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <button type="button" onclick="closeDeleteModal()" style="padding:12px;background:#F2F2F7;color:#2D2D2D;border:none;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:12px;background:#C0392B;color:#fff;border:none;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">Confirm</button>
            </div>
        </form>
    </div>
</div>



<script>
function openEditModal()  { const el = document.getElementById('editModalOverlay'); el.style.display = 'flex'; el.style.pointerEvents = 'all'; document.body.style.overflow = 'hidden'; }
function closeEditModal() { const el = document.getElementById('editModalOverlay'); el.style.display = 'none'; el.style.pointerEvents = 'none'; document.body.style.overflow = ''; }
function openAvatarModal()  { const el = document.getElementById('avatarModalOverlay'); el.style.display = 'flex'; el.style.pointerEvents = 'all'; document.body.style.overflow = 'hidden'; }
function closeAvatarModal() { const el = document.getElementById('avatarModalOverlay'); el.style.display = 'none'; el.style.pointerEvents = 'none'; document.body.style.overflow = ''; }
function openDeleteModal()  { const el = document.getElementById('deleteModalOverlay'); el.style.display = 'flex'; el.style.pointerEvents = 'all'; document.body.style.overflow = 'hidden'; }
function closeDeleteModal() { const el = document.getElementById('deleteModalOverlay'); el.style.display = 'none'; el.style.pointerEvents = 'none'; document.body.style.overflow = ''; }

function toggleDeletePassword() {
    const input = document.getElementById('delete-password');
    const icon  = document.getElementById('delete-eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
        input.type = 'password';
        icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    }
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('avatarPreview');
            preview.src = e.target.result;
            preview.style.display = 'block';
            document.getElementById('avatarLabel').textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeEditModal(); closeAvatarModal(); closeDeleteModal(); }
});

<?php if (!empty($error)): ?> openDeleteModal(); <?php endif; ?>
<?php if (!empty($avatar_error)): ?> openAvatarModal(); <?php endif; ?>
</script>
<?php require '../includes/footer.php'; ?>