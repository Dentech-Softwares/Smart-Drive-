// Dashboard JavaScript

function initCharts() {
    // Booking Statistics Chart
    const bookingCtx = document.getElementById('bookingStatsChart');
    if (bookingCtx) {
        new Chart(bookingCtx, {
            type: 'bar',
            data: {
                labels: bookingCtx.dataset.labels ? bookingCtx.dataset.labels.split(',') : [],
                datasets: [{
                    label: 'Bookings',
                    data: bookingCtx.dataset.values ? bookingCtx.dataset.values.split(',').map(Number) : [],
                    backgroundColor: 'rgba(139, 0, 0, 0.8)',
                    borderColor: 'rgba(139, 0, 0, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
    
    // Reports Chart
    const reportCtx = document.getElementById('reportChart');
    if (reportCtx) {
        const labels = reportCtx.dataset.labels ? JSON.parse(reportCtx.dataset.labels) : [];
        const values = reportCtx.dataset.values ? reportCtx.dataset.values.split(',').map(v => v.trim() !== '' ? Number(v) : 0) : [];
        
        const isRevenue = values.some(v => v > 1000);
        const chartType = isRevenue ? 'line' : 'bar';
        
        new Chart(reportCtx, {
            type: chartType,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Count',
                    data: values,
                    backgroundColor: 'rgba(139, 0, 0, 0.8)',
                    borderColor: 'rgba(139, 0, 0, 1)',
                    borderWidth: 1,
                    tension: chartType === 'line' ? 0.4 : 0,
                    fill: chartType === 'line'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Vehicle Status Chart
    const vehicleCtx = document.getElementById('vehicleStatusChart');
    if (vehicleCtx) {
        new Chart(vehicleCtx, {
            type: 'doughnut',
            data: {
                labels: vehicleCtx.dataset.labels ? vehicleCtx.dataset.labels.split(',') : [],
                datasets: [{
                    data: vehicleCtx.dataset.values ? vehicleCtx.dataset.values.split(',').map(Number) : [],
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.8)',
                        'rgba(13, 110, 253, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(108, 117, 125, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', initCharts);

// Sidebar toggle for mobile
(function() {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('active');
        if (overlay) overlay.classList.add('active');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
    }

    if (toggle) {
        toggle.addEventListener('click', function() {
            if (sidebar && sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
})();

function updateTripStatus(bookingId, status) {
    Swal.fire({
        title: 'Update Trip Status?',
        text: 'This will update the booking and vehicle status.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8B0000',
        confirmButtonText: 'Yes, update!'
    }).then((result) => {
        if (result.isConfirmed) {
                fetch((typeof BASE_URL !== 'undefined' ? BASE_URL : '/') + 'api/booking-actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_status',
                    booking_id: bookingId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: data.message,
                        confirmButtonColor: '#8B0000'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to update status.',
                        confirmButtonColor: '#8B0000'
                    });
                }
            });
        }
    });
}
