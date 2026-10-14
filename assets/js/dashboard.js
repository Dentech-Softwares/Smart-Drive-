// Dashboard JavaScript

let bookingTrendChart = null;

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
                    backgroundColor: 'rgba(22, 163, 74, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                    tension: chartType === 'line' ? 0.4 : 0,
                    fill: chartType === 'line',
                    barThickness: 35,
                    maxBarThickness: 45,
                    barPercentage: 0.5,
                    categoryPercentage: 0.6,
                    borderRadius: chartType === 'bar' ? 8 : 0,
                    borderSkipped: 'bottom'
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
                        ticks: {
                            stepSize: 1,
                            precision: 0,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        },
                        grid: {
                            color: '#E5E7EB',
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
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
    
    // Booking Trend Chart (AJAX-driven, two datasets)
    loadTrendChart();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        initDashboardAutoRefresh();
        bindTrendControls();
    });
} else {
    initCharts();
    initDashboardAutoRefresh();
    bindTrendControls();
}

function initDashboardAutoRefresh() {
    const dashboardUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/') + 'api/dashboard-stats.php';
    
    function refreshDashboard() {
        fetch(dashboardUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.stats) {
                updateStatCards(data.stats);
                updateNotificationBadge(data.stats.unreadNotifications);
                updateRecentBookings(data.recentBookings);
            }
        })
        .catch(() => {});
    }
    
    setInterval(refreshDashboard, 30000);
}

function updateStatCards(stats) {
    const statMap = {
        totalVehicles: { selector: '.stat-total-vehicles .stat-number', fallback: 0 },
        availableVehicles: { selector: '.stat-available-vehicles .stat-number', fallback: 0 },
        activeBookings: { selector: '.stat-active-bookings .stat-number', fallback: 0 },
        pendingBookings: { selector: '.stat-pending-bookings .stat-number', fallback: 0 },
        totalClients: { selector: '.stat-total-clients .stat-number', fallback: 0 },
        totalDrivers: { selector: '.stat-total-drivers .stat-number', fallback: 0 },
        pendingPayments: { selector: '.stat-pending-payments .stat-number', fallback: 0 },
        activeTrips: { selector: '.stat-active-trips .stat-number', fallback: 0 }
    };
    
    Object.keys(stats).forEach(key => {
        if (key === 'revenue' || key === 'unreadNotifications') return;
        const mapping = statMap[key];
        if (!mapping) return;
        const el = document.querySelector(mapping.selector);
        if (el) {
            const newVal = String(stats[key]);
            if (el.textContent.trim() !== newVal) {
                el.textContent = newVal;
                el.style.transform = 'scale(1.15)';
                el.style.transition = 'transform 0.3s ease';
                setTimeout(() => { el.style.transform = 'scale(1)'; }, 300);
            }
        }
    });
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        const current = parseInt(badge.textContent) || 0;
        if (current !== count) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }
}

function updateRecentBookings(bookings) {
    if (!bookings || !bookings.length) return;
    const tbody = document.querySelector('#recentBookingsTable tbody');
    if (!tbody) return;
    
    const existingRefs = Array.from(tbody.querySelectorAll('tr')).map(tr => tr.dataset.ref);
    const newRefs = bookings.map(b => String(b.id));
    
    if (JSON.stringify(existingRefs) === JSON.stringify(newRefs)) return;
    
    tbody.innerHTML = bookings.map(booking => `
        <tr data-ref="${booking.id}">
            <td><strong>${escapeHtml(booking.booking_reference)}</strong></td>
            <td>${escapeHtml(booking.client_name)}</td>
            <td>${escapeHtml(booking.brand + ' ' + booking.model)}</td>
            <td>${escapeHtml(booking.formatted_amount)}</td>
            <td>${escapeHtml(booking.status_label)}</td>
            <td>
                <a href="${BASE_URL}admin/bookings/details.php?id=${booking.id}" class="btn btn-sm btn-outline">View</a>
            </td>
        </tr>
    `).join('');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function filterTrend() {
    const start = document.getElementById('trendStartDate').value;
    const end = document.getElementById('trendEndDate').value;
    if (start && end) {
        loadTrendChart(start, end);
    }
}

function bindTrendControls() {
    const btn = document.getElementById('trendFilterBtn');
    if (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            filterTrend();
        });
    }
}

function toggleTrendState(state) {
    const loading = document.getElementById('trendLoading');
    const zero = document.getElementById('trendZeroState');
    const canvas = document.getElementById('bookingTrendChart');
    if (loading) loading.style.display = state === 'loading' ? 'grid' : 'none';
    if (zero) zero.style.display = state === 'empty' ? 'grid' : 'none';
    if (canvas) canvas.style.display = state === 'chart' ? 'block' : 'none';
}

function loadTrendChart(startDate, endDate) {
    const ctx = document.getElementById('bookingTrendChart');
    if (!ctx) return;

    toggleTrendState('loading');

    const params = new URLSearchParams();
    if (startDate) params.set('start_date', startDate);
    if (endDate) params.set('end_date', endDate);

    const url = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/') + 'api/bookings-trend.php?' + params.toString();

    fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
        .then(response => response.json())
        .then(data => {
            if (!data || !data.success) {
                toggleTrendState('empty');
                destroyTrendChart();
                return;
            }
            const total = data.totalBookings || [];
            const completed = data.completedBookings || [];
            const allZero = total.length === 0 || (total.every(v => v === 0) && completed.every(v => v === 0));
            if (allZero) {
                toggleTrendState('empty');
                destroyTrendChart();
                return;
            }
            toggleTrendState('chart');
            renderBookingTrendChart(ctx, data);
        })
        .catch(() => {
            toggleTrendState('empty');
            destroyTrendChart();
        });
}

function formatTrendCurrency(amount, symbol) {
    const num = Number(amount) || 0;
    return symbol + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function renderBookingTrendChart(ctx, data) {
    destroyTrendChart();

    const currencySymbol = data.currencySymbol || 'KSh ';
    const maxVal = Math.max(...(data.totalBookings || []), ...(data.completedBookings || []), 1);
    const yMax = Math.ceil(maxVal * 1.25);

    bookingTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [
                {
                    label: 'Total Bookings',
                    data: data.totalBookings || [],
                    borderColor: '#A82229',
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#A82229',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 3,
                    pointHoverBackgroundColor: '#ffffff',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: function (context) {
                        const chart = context.chart;
                        const c = chart.ctx;
                        const area = chart.chartArea || { top: 0, bottom: ctx.canvas.height || 300 };
                        const grad = c.createLinearGradient(0, area.top, 0, area.bottom);
                        grad.addColorStop(0, 'rgba(168, 34, 41, 0.35)');
                        grad.addColorStop(1, 'rgba(168, 34, 41, 0.0)');
                        return grad;
                    }
                },
                {
                    label: 'Completed Trips',
                    data: data.completedBookings || [],
                    borderColor: '#0d6efd',
                    borderDash: [6, 5],
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#0d6efd',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#ffffff',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: function (context) {
                        const chart = context.chart;
                        const c = chart.ctx;
                        const area = chart.chartArea || { top: 0, bottom: ctx.canvas.height || 300 };
                        const grad = c.createLinearGradient(0, area.top, 0, area.bottom);
                        grad.addColorStop(0, 'rgba(13, 110, 253, 0.18)');
                        grad.addColorStop(1, 'rgba(13, 110, 253, 0.0)');
                        return grad;
                    }
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#64748b',
                        font: { size: 12, weight: '600' },
                        usePointStyle: true,
                        padding: 16
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#f1f5f9',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(168, 34, 41, 0.45)',
                    borderWidth: 1,
                    padding: 14,
                    cornerRadius: 10,
                    displayColors: false,
                    titleFont: { size: 13, weight: '600' },
                    bodyFont: { size: 14, weight: '600' },
                    callbacks: {
                        title: function (context) {
                            return context[0].label;
                        },
                        label: function (context) {
                            const val = context.parsed.y;
                            if (val === null || val === undefined) return '';
                            return context.dataset.label + ': ' + val;
                        },
                        afterLabel: function (context) {
                            if (context.dataset.label === 'Total Bookings') {
                                const rev = data.revenue && data.revenue[context.dataIndex];
                                if (rev !== undefined && rev !== null && Number(rev) > 0) {
                                    return 'Revenue: ' + formatTrendCurrency(rev, currencySymbol);
                                }
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: yMax,
                    grid: {
                        color: 'rgba(0,0,0,0.04)',
                        drawBorder: false
                    },
                    ticks: {
                        stepSize: Math.max(1, Math.ceil(maxVal / 5)),
                        color: '#64748b',
                        font: { size: 12, weight: '500' }
                    },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#64748b',
                        font: { size: 12, weight: '500' }
                    },
                    border: { display: false }
                }
            },
            animation: {
                duration: 1400,
                easing: 'easeInOutQuart'
            }
        }
    });
}

function destroyTrendChart() {
    if (bookingTrendChart) {
        try { bookingTrendChart.destroy(); } catch (e) { /* ignore */ }
        bookingTrendChart = null;
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

/* Clickable table rows */
(function() {
    const clickableRows = document.querySelectorAll('.clickable-row[data-booking-id]');
    clickableRows.forEach(function(row) {
        row.addEventListener('click', function() {
            const bookingId = row.getAttribute('data-booking-id');
            if (bookingId) {
                window.location.href = BASE_URL + 'admin/bookings/details.php?id=' + bookingId;
            }
        });
    });
})();
/* End Clickable table rows */

// Legacy clickable-rows handler
(function() {
    const clickableTables = document.querySelectorAll('.data-table.clickable-rows');
    clickableTables.forEach(function(table) {
        const rows = table.querySelectorAll('tbody tr[data-href]');
        rows.forEach(function(row) {
            row.addEventListener('click', function() {
                const href = row.getAttribute('data-href');
                if (href) window.location.href = href;
            });
        });
    });
})();
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
