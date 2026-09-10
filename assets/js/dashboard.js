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
    
    // Booking Trend Chart
    const trendCtx = document.getElementById('bookingTrendChart');
    if (trendCtx) {
        const trendLabels = trendCtx.dataset.labels ? trendCtx.dataset.labels.split(',') : [];
        const trendValues = trendCtx.dataset.values ? trendCtx.dataset.values.split(',').map(v => v.trim() !== '' ? Number(v) : 0) : [];
        const maxVal = Math.max(...trendValues, 1);
        const yMax = Math.ceil(maxVal * 1.25) || 5;
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Bookings',
                    data: trendValues,
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const ctx = chart.ctx;
                        const gradient = ctx.createLinearGradient(0, chart.chartArea.top, 0, chart.chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(13, 110, 253, 0.25)');
                        gradient.addColorStop(0.5, 'rgba(13, 110, 253, 0.08)');
                        gradient.addColorStop(1, 'rgba(13, 110, 253, 0.0)');
                        return gradient;
                    },
                    borderColor: '#0d6efd',
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#0d6efd',
                    pointBorderWidth: 3,
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    pointHoverBorderWidth: 4,
                    pointHoverBackgroundColor: '#ffffff',
                    tension: 0.35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#f1f5f9',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(13, 110, 253, 0.5)',
                        borderWidth: 1,
                        padding: 14,
                        cornerRadius: 10,
                        displayColors: false,
                        titleFont: { size: 13, weight: '600' },
                        bodyFont: { size: 15, weight: '700' },
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const val = context.parsed.y;
                                return val + ' booking' + (val !== 1 ? 's' : '');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: yMax,
                        ticks: {
                            stepSize: 1,
                            color: '#64748b',
                            font: { size: 12, weight: '500' },
                            padding: 8
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.04)',
                            drawBorder: false
                        },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#64748b',
                            font: { size: 12, weight: '500' },
                            padding: 8
                        },
                        border: { display: false }
                    }
                },
                animation: {
                    duration: 1800,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', initCharts);

function filterTrend() {
    const start = document.getElementById('trendStartDate').value;
    const end = document.getElementById('trendEndDate').value;
    if (start && end) {
        const url = new URL(window.location.href);
        url.searchParams.set('start_date', start);
        url.searchParams.set('end_date', end);
        window.location.href = url.toString();
    }
}

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
