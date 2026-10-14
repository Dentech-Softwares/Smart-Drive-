// Notifications JavaScript

var BASE_URL = window.BASE_URL || '/';

function getApiUrl() {
    return BASE_URL + 'api/notifications.php';
}

function updateNotificationBadge(count) {
    var badges = document.querySelectorAll('.notification-badge');
    badges.forEach(function(badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    });
}

function getNotificationBadgeCount() {
    var badge = document.querySelector('.notification-badge');
    if (badge && badge.textContent) {
        return parseInt(badge.textContent) || 0;
    }
    return 0;
}

function markAsRead(notificationId, callback) {
    fetch(getApiUrl(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_read',
            notification_id: notificationId
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            var row = document.querySelector('.notification-item[data-notification-id="' + notificationId + '"]');
            if (row) {
                row.classList.remove('unread');

                var count = getNotificationBadgeCount();
                count = count > 0 ? count - 1 : 0;
                updateNotificationBadge(count);
            }
        }
        if (typeof callback === 'function') {
            callback(data);
        }
    });
}

function markAllRead() {
    fetch(getApiUrl(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_all_read'
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            var rows = document.querySelectorAll('.notification-item.unread');
            rows.forEach(function(row) {
                row.classList.remove('unread');
            });

            updateNotificationBadge(0);
        }
    });
}

// Single notification click: mark as read then navigate
document.addEventListener('click', function(e) {
    var item = e.target.closest('.notification-item');
    var btn = e.target.closest('[data-action="mark-read"]');

    if (btn) {
        var id = btn.getAttribute('data-notification-id');
        e.stopPropagation();
        markAsRead(id);
        return;
    }

    if (item) {
        var nid = item.getAttribute('data-notification-id');
        var link = item.getAttribute('data-link');

        if (nid && item.classList.contains('unread')) {
            markAsRead(nid, function() {
                if (link) {
                    window.location.href = link;
                }
            });
        } else if (link) {
            window.location.href = link;
        }
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    var dropdown = document.querySelector('.notification-dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        var menu = dropdown.querySelector('.dropdown-menu');
        if (menu) {
            menu.classList.remove('active');
        }
    }
});

// Auto-refresh notification count every 30 seconds
setInterval(function() {
    fetch(getApiUrl() + '?action=count')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.count !== undefined) {
                updateNotificationBadge(data.count);
            }
        });
}, 30000);
