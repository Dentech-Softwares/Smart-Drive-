const BookingActions = (function() {
    let baseUrl = '';
    let csrfToken = '';
    let currentUserRole = '';
    let currentUserId = 0;

    function init(config = {}) {
        baseUrl = config.baseUrl || (window.BASE_URL || '');
        csrfToken = config.csrfToken || '';
        currentUserRole = config.userRole || '';
        currentUserId = config.userId || 0;
    }

    function showToast(icon, title, text, timer = 3000) {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                toast: true,
                position: 'top-end',
                icon,
                title,
                text,
                showConfirmButton: false,
                timer,
                timerProgressBar: true,
                customClass: {
                    popup: 'swal-toast'
                }
            });
        }
        const prefix = icon === 'success' ? '✓' : icon === 'error' ? '✗' : icon === 'warning' ? '!' : 'i';
        alert(`${prefix} ${title}: ${text}`);
        return Promise.resolve();
    }

    function showConfirm(title, text, icon = 'question', confirmText = 'Yes', cancelText = 'Cancel') {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                title,
                text,
                icon,
                showCancelButton: true,
                confirmButtonColor: '#8B0000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmText,
                cancelButtonText: cancelText,
                reverseButtons: true
            });
        }
        return Promise.resolve({ isConfirmed: confirm(`${title}\n\n${text}`) });
    }

    function setButtonLoading(button, loading = true) {
        if (!button) return;
        if (loading) {
            button.disabled = true;
            button.dataset.originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
        } else {
            button.disabled = false;
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
                delete button.dataset.originalHtml;
            }
        }
    }

    function updateStatusBadge(status) {
        const badges = {
            'Pending': '<span class="badge bg-warning text-dark">Pending</span>',
            'Approved': '<span class="badge bg-info">Awaiting Payment</span>',
            'Payment Submitted': '<span class="badge bg-primary">Payment Submitted</span>',
            'Confirmed': '<span class="badge bg-success">Confirmed</span>',
            'Active': '<span class="badge bg-primary">Active</span>',
            'Completed': '<span class="badge bg-success">Completed</span>',
            'Cancelled': '<span class="badge bg-danger">Cancelled</span>'
        };
        const badgeHtml = badges[status] || `<span class="badge bg-secondary">${status}</span>`;
        const statusElements = document.querySelectorAll('.booking-status-badge, [data-booking-status]');
        statusElements.forEach(el => el.innerHTML = badgeHtml);
    }

    function updateActionButtons(bookingId, status) {
        const container = document.getElementById('booking-actions');
        if (!container) return;

        let buttonsHtml = '';
        const isClient = currentUserRole === 'client';
        const isDriver = currentUserRole === 'driver';

        if (isClient) {
            if (['Pending', 'Approved', 'Payment Submitted', 'Confirmed'].includes(status)) {
                buttonsHtml += `
                    <button onclick="BookingActions.cancelBooking(${bookingId})" 
                            class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);">
                        <i class="fas fa-times-circle"></i> Cancel Booking
                    </button>`;
            }
            if (status === 'Confirmed') {
                const pickupReached = document.body.dataset.pickupReached === 'true';
                if (pickupReached) {
                    buttonsHtml += `
                        <button onclick="BookingActions.startTrip(${bookingId})" 
                                class="btn btn-primary">
                            <i class="fas fa-play-circle"></i> Start Trip
                        </button>`;
                } else {
                    buttonsHtml += `
                        <button class="btn btn-secondary" disabled>
                            <i class="fas fa-clock"></i> Available from pickup time
                        </button>`;
                }
            }
            if (status === 'Active') {
                buttonsHtml += `
                    <button onclick="BookingActions.endTrip(${bookingId})" 
                            class="btn btn-danger">
                        <i class="fas fa-stop-circle"></i> End Trip
                    </button>`;
            }
            if (status === 'Completed') {
                const hasUnpaidPenalty = document.body.dataset.hasUnpaidPenalty === 'true';
                if (hasUnpaidPenalty) {
                    buttonsHtml += `
                        <a href="${baseUrl}client/payment.php?booking_id=${bookingId}&pay_penalty=1" 
                           class="btn btn-danger">
                            <i class="fas fa-exclamation-circle"></i> Pay Penalty
                        </a>`;
                }
            }
        } else if (isDriver) {
            if (status === 'Confirmed') {
                buttonsHtml += `
                    <button onclick="BookingActions.updateTripStatus(${bookingId}, 'Active')" 
                            class="btn btn-primary">
                        <i class="fas fa-play"></i> Start Trip
                    </button>`;
            } else if (status === 'Active') {
                buttonsHtml += `
                    <button onclick="BookingActions.updateTripStatus(${bookingId}, 'Completed')" 
                            class="btn btn-success">
                        <i class="fas fa-stop"></i> End Trip
                    </button>`;
            }
        }

        container.innerHTML = buttonsHtml;
    }

    async function apiRequest(action, data) {
        const payload = { action, ...data };
        if (csrfToken) payload.csrf_token = csrfToken;

        const response = await fetch(`${baseUrl}api/booking-actions.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned invalid response');
        }

        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || `HTTP ${response.status}`);
        }
        return result;
    }

    async function cancelBooking(bookingId, buttonEl = null) {
        const confirmed = await showConfirm(
            'Cancel Booking?',
            'This action cannot be undone. Are you sure you want to cancel this booking?',
            'warning',
            'Yes, cancel it',
            'Keep booking'
        );
        if (!confirmed.isConfirmed) return;

        const btn = buttonEl || event?.target?.closest('button');
        setButtonLoading(btn, true);

        try {
            const result = await apiRequest('cancel_booking', { booking_id: bookingId });
            await showToast('success', 'Cancelled', result.message);
            updateUIAfterAction(bookingId, 'Cancelled');
        } catch (error) {
            await showToast('error', 'Error', error.message);
        } finally {
            setButtonLoading(btn, false);
        }
    }

    async function startTrip(bookingId, buttonEl = null) {
        const confirmed = await showConfirm(
            'Start Trip?',
            'This will mark the trip as active and notify the driver. Continue?',
            'question',
            'Yes, start trip',
            'Not now'
        );
        if (!confirmed.isConfirmed) return;

        const btn = buttonEl || event?.target?.closest('button');
        setButtonLoading(btn, true);

        try {
            const result = await apiRequest('start_trip', { booking_id: bookingId });
            await showToast('success', 'Trip Started', result.message);
            updateUIAfterAction(bookingId, 'Active');
        } catch (error) {
            await showToast('error', 'Error', error.message);
        } finally {
            setButtonLoading(btn, false);
        }
    }

    async function endTrip(bookingId, buttonEl = null) {
        const confirmed = await showConfirm(
            'End Trip?',
            'This will complete the trip. Late returns will incur a penalty of KSh 200 per hour.',
            'warning',
            'Yes, end trip',
            'Continue trip'
        );
        if (!confirmed.isConfirmed) return;

        const btn = buttonEl || event?.target?.closest('button');
        setButtonLoading(btn, true);

        try {
            const result = await apiRequest('end_trip', { booking_id: bookingId });
            let message = result.message;
            if (result.penalty > 0) {
                message += ` Late return penalty: ${formatCurrency(result.penalty)}.`;
            }
            await showToast(result.penalty > 0 ? 'warning' : 'success', 'Trip Ended', message);
            updateUIAfterAction(bookingId, 'Completed', result.penalty > 0);
        } catch (error) {
            await showToast('error', 'Error', error.message);
        } finally {
            setButtonLoading(btn, false);
        }
    }

    async function updateTripStatus(bookingId, newStatus, buttonEl = null) {
        const actionText = newStatus === 'Active' ? 'start' : 'end';
        const confirmed = await showConfirm(
            `Update Trip Status?`,
            `This will ${actionText} the trip and update the booking and vehicle status.`,
            'question',
            `Yes, ${actionText} trip`,
            'Cancel'
        );
        if (!confirmed.isConfirmed) return;

        const btn = buttonEl || event?.target?.closest('button');
        setButtonLoading(btn, true);

        try {
            const result = await apiRequest('update_status', { booking_id: bookingId, status: newStatus });
            await showToast('success', 'Updated', result.message);
            updateUIAfterAction(bookingId, newStatus);
        } catch (error) {
            await showToast('error', 'Error', error.message);
        } finally {
            setButtonLoading(btn, false);
        }
    }

    function updateUIAfterAction(bookingId, newStatus, hasPenalty = false) {
        updateStatusBadge(newStatus);
        updateActionButtons(bookingId, newStatus);
        
        if (hasPenalty) {
            document.body.dataset.hasUnpaidPenalty = 'true';
        }
    }

    function formatCurrency(amount) {
        const symbol = 'KSh ';
        return symbol + Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    return {
        init,
        cancelBooking,
        startTrip,
        endTrip,
        updateTripStatus,
        updateStatusBadge,
        updateActionButtons,
        showToast,
        showConfirm
    };
})();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = BookingActions;
}