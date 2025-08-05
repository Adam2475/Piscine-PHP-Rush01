document.addEventListener('DOMContentLoaded', function() {
    const notificationsBtn = document.getElementById('notifications-btn');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    const notificationsCount = document.getElementById('notifications-count');
    const notificationsList = document.getElementById('notifications-list');

    if (!notificationsBtn) {
        console.log('Notifications button not found');
        return;
    }

    loadNotifications();
    
    notificationsBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (notificationsDropdown.style.display === 'none' || notificationsDropdown.style.display === '') {
            notificationsDropdown.style.display = 'block';
            loadNotifications();
            setTimeout(() => {
                markAllAsRead();
            }, 2000);
        } else {
            notificationsDropdown.style.display = 'none';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!notificationsDropdown.contains(e.target) && !notificationsBtn.contains(e.target)) {
            notificationsDropdown.style.display = 'none';
        }
    });

    function loadNotifications() {
        fetch('/api/notifications', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            updateNotificationUI(data.notifications, data.unreadCount);
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            updateNotificationUI([], 0);
        });
    }

    function markAllAsRead() {
        const currentCount = parseInt(notificationsCount.getAttribute('data-count') || '0');
        if (currentCount === 0) return;

        fetch('/api/notifications/mark-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                notificationsCount.textContent = '';
                notificationsCount.style.display = 'none';
                notificationsCount.setAttribute('data-count', '0');
                
                loadNotifications();
            }
        })
        .catch(error => {
            console.error('Error marking notifications as read:', error);
        });
    }

    function updateNotificationUI(notifications, count) {
        if (count > 0) {
            notificationsCount.textContent = count;
            notificationsCount.style.display = 'flex';
            notificationsCount.setAttribute('data-count', count);
        } else {
            notificationsCount.textContent = '';
            notificationsCount.style.display = 'none';
            notificationsCount.setAttribute('data-count', '0');
        }

        if (notifications && notifications.length > 0) {
            const displayNotifications = notifications.slice(0, 5);
            
            notificationsList.innerHTML = displayNotifications.map((notification, index) => 
                `<div class="notification-item" data-index="${index}">
                    <div class="notification-content">${notification}</div>
                </div>`
            ).join('');
            
            if (notifications.length > 5) {
                notificationsList.innerHTML += `
                    <div class="notification-item more-notifications" style="text-align: center; font-style: italic; color: #666; border-top: 1px solid #eee; padding-top: 10px;">
                        And ${notifications.length - 5} more notifications...
                    </div>`;
            }
            
            if (count > 0) {
                notificationsList.innerHTML += `
                    <div class="notification-actions" style="border-top: 1px solid #eee; padding: 10px; text-align: center;">
                        <button id="mark-all-read-btn" class="mark-all-read-btn" style="background: #28a745; color: white; border: none; border-radius: 4px; padding: 5px 10px; font-size: 12px; cursor: pointer;">
                            Mark All as Read
                        </button>
                    </div>`;
                
                const markAllBtn = document.getElementById('mark-all-read-btn');
                if (markAllBtn) {
                    markAllBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        markAllAsRead();
                    });
                }
            }
        } else {
            notificationsList.innerHTML = '<p class="no-notifications" style="padding: 20px; text-align: center; color: #666; margin: 0;">No notifications</p>';
        }
    }

    setInterval(function() {
        if (notificationsDropdown.style.display === 'none' || notificationsDropdown.style.display === '') {
            loadNotifications();
        }
    }, 15000);

    let focusInterval;
    
    window.addEventListener('focus', function() {
        loadNotifications();
        
        focusInterval = setInterval(function() {
            if (notificationsDropdown.style.display === 'none' || notificationsDropdown.style.display === '') {
                loadNotifications();
            }
        }, 5000);
    });
    
    window.addEventListener('blur', function() {
        if (focusInterval) {
            clearInterval(focusInterval);
        }
    });
});
