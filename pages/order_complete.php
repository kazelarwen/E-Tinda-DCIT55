<?php
require '../includes/auth.php';

$page_title = 'Order Complete — E-Tinda';
$active_nav = 'home';
$page_css   = 'order_complete.css';

// Must come from a completed order
if (!isset($_SESSION['last_order_id'])) {
    header("Location: home.php");
    exit;
}

// Clear so they can't refresh back to this page
unset($_SESSION['last_order_id']);

$hide_nav = true;
require '../includes/header.php';
?>

<div class="complete-page">

    <div class="complete-wrap">
        <!-- Concentric rings -->
        <div class="complete-rings">
            <div class="ring ring-outer"></div>
            <div class="ring ring-mid"></div>
            <div class="ring ring-inner">
                <svg class="check-icon" viewBox="0 0 24 24" fill="none"
                     stroke="white" stroke-width="2.5" stroke-linecap="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>

        <h1 class="complete-title">Order Complete!</h1>
    </div>

    <!-- Back home button -->
    <div class="complete-footer">
        <a href="home.php" class="btn btn-primary complete-home-btn">
            Back home
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                 style="width:16px;height:16px;">
                <line x1="5" y1="12" x2="19" y2="12"/>
                <polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>
    </div>

</div>

<?php require '../includes/footer.php'; ?>