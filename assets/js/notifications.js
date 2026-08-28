// Notifications JavaScript

function toggleNotifications() {
    const dropdown = document.querySelector('.notification-dropdown .dropdown-menu');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

function markAsRead(notificationId) {
    fetch('/api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_read',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function markAllAsRead() {
    fetch('/api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_all_read'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.querySelector('.notification-dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (menu) {
            menu.classList.remove('active');
        }
    }
});

// Auto-refresh notifications every 30 seconds
setInterval(function() {
    const unreadBadge = document.querySelector('.notification-badge');
    if (unreadBadge && unreadBadge.textContent !== '0') {
        fetch('/api/notifications.php?action=count')
            .then(response => response.json())
            .then(data => {
                if (data.count !== undefined) {
                    unreadBadge.textContent = data.count;
                    unreadBadge.style.display = data.count > 0 ? 'flex' : 'none';
                }
            });
    }
}, 30000);
