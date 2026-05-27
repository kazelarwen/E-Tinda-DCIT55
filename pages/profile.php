<?php
require '../includes/auth.php';
require '../includes/db.php';
 
$page_title = 'Profile — E-Tinda';
$page_css   = 'profile.css';
$active_nav = 'profile';
$vendor_id  = $_SESSION['vendor_id'];
 
// Fetch vendor info
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();
 
// Vendor initials for avatar
$initials = strtoupper(substr($vendor['vendor_name'] ?? 'V', 0, 1));
 
require '../includes/header.php';
?>
 
<!-- Blue hero header -->
<div class="profile-hero">
    <a href="home.php" class="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        < Home
    </a>
    <h2 class="profile-title">Profile</h2>
 
    <div class="profile-avatar">
        <?= $initials ?>
    </div>
 
    <div class="profile-name"><?= htmlspecialchars($vendor['vendor_name']) ?></div>
    <div class="profile-stall"><?= htmlspecialchars($vendor['stall_name']) ?></div>
</div>
 
<!-- Content -->
<div class="profile-content">
 
    <!-- Account Info -->
    <div class="section-label">ACCOUNT INFO</div>
    <div class="info-card">
 
        <a href="#" class="info-row">
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
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
 
        <div class="info-divider"></div>
 
        <a href="#" class="info-row">
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
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
 
        <div class="info-divider"></div>
 
        <a href="#" class="info-row">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="info-text">
                <span class="info-label">Contact Number</span>
                <span class="info-value">+63 <?= htmlspecialchars(ltrim($vendor['contact_number'] ?? 'N/A', '0')) ?></span>
            </div>
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
 
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
 
        <a href="#" class="info-row danger" onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.')">
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
 
<?php require '../includes/footer.php'; ?>