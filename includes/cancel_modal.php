<?php
// cancel_modal.php
// Include this on any page that needs the "Cancel order?" confirmation modal.
// Trigger it with: openCancelModal()  from JS
// It posts to: ../actions/order_action.php?action=cancel  OR redirects to home.php
?>

<!-- Modal backdrop -->
<div class="modal-backdrop" id="cancelModalBackdrop" onclick="closeCancelModal()"></div>

<!-- Cancel order modal — matches the design exactly -->
<div class="cancel-modal" id="cancelModal" role="dialog" aria-modal="true">

    <!-- X close button -->
    <button class="cancel-modal-close" onclick="closeCancelModal()" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
    </button>

    <!-- Title -->
    <h2 class="cancel-modal-title">Cancel order</h2>

    <!-- Body text -->
    <p class="cancel-modal-body">
        Do you really want to cancel your order?
    </p>

    <!-- Confirm cancel button (posts to action or redirects) -->
    <form method="POST" action="../actions/order_action.php" id="cancelOrderForm">
        <input type="hidden" name="action" value="cancel_cart">
        <button type="submit" class="btn btn-cancel-confirm btn-full">
            Cancel order
        </button>
    </form>

</div>

<script>
function openCancelModal() {
    document.getElementById('cancelModal').classList.add('modal--open');
    document.getElementById('cancelModalBackdrop').classList.add('modal--open');
    document.body.style.overflow = 'hidden';
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('modal--open');
    document.getElementById('cancelModalBackdrop').classList.remove('modal--open');
    document.body.style.overflow = '';
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeCancelModal();
});
</script>