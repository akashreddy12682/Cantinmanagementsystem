/**
 * CampusBite Canteen System - Client Side JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // 2. Quantity adjustment buttons
    document.querySelectorAll('.quantity-btn-plus').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const input = e.target.closest('.quantity-control').querySelector('input');
            let val = parseInt(input.value, 10) || 1;
            if (val < 20) {
                input.value = val + 1;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    document.querySelectorAll('.quantity-btn-minus').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const input = e.target.closest('.quantity-control').querySelector('input');
            let val = parseInt(input.value, 10) || 1;
            if (val > 1) {
                input.value = val - 1;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    // 3. Auto-submit form when quantity input changes in cart
    document.querySelectorAll('.cart-qty-input').forEach(input => {
        input.addEventListener('change', () => {
            const form = input.closest('form');
            if (form) {
                form.submit();
            }
        });
    });

    // 4. Live Order Status Polling for active orders on order-details.php
    const orderTracker = document.getElementById('orderTrackingContainer');
    if (orderTracker) {
        const orderId = orderTracker.getAttribute('data-order-id');
        const currentStatus = orderTracker.getAttribute('data-current-status');
        
        // Only poll if order is in progress
        const activeStatuses = ['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup'];
        if (orderId && activeStatuses.includes(currentStatus)) {
            // Poll every 12 seconds
            setInterval(() => {
                fetch(`order-details.php?id=${orderId}&ajax=status`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.order_status && data.order_status !== currentStatus) {
                            // Status changed! Refresh page to show updated stepper & badge
                            window.location.reload();
                        }
                    })
                    .catch(err => console.debug('Polling check:', err));
            }, 12000);
        }
    }
});
