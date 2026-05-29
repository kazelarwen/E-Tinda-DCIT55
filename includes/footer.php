<?php
// footer.php — bottom nav + closing tags
// $active_nav must be set before requiring header.php
$active_nav = $active_nav ?? '';
?>

<?php if (empty($hide_nav)): ?>
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="home.php" class="nav-item <?= $active_nav === 'home' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none">
                <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/>
                <path d="M9 21V12h6v9"/>
            </svg>
            <span>Home</span>
        </a>
        <a href="inventory.php" class="nav-item <?= $active_nav === 'inventory' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span>Inventory</span>
        </a>
        <a href="dashboard.php" class="nav-item <?= $active_nav === 'dashboard' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none">
                <rect x="3" y="3" width="7" height="11" rx="1"/>
                <rect x="14" y="10" width="7" height="11" rx="1"/>
                <rect x="3" y="17" width="7" height="4" rx="1"/>
                <rect x="14" y="3" width="7" height="4" rx="1"/>
            </svg>
            <span>Dashboard</span>
        </a>
        <a href="transaction_history.php" class="nav-item <?= $active_nav === 'history' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3 3"/>
            </svg>
            <span>History</span>
        </a>
    </nav>
<?php endif; ?>

</div><!-- /.app-layout -->
<script src="../assets/js/main.js"></script>
</body>
</html>