document.addEventListener('DOMContentLoaded', function() {
    const markAllBtn = document.getElementById('mark-all-read-page');
    const notificationCards = document.querySelectorAll('.notification-card');
    
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            const originalText = markAllBtn.innerHTML;
            markAllBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                Marking...
            `;
            markAllBtn.disabled = true;
            
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
                    notificationCards.forEach(card => {
                        card.classList.add('read');
                        const indicator = card.querySelector('.notification-indicator');
                        if (indicator) {
                            indicator.style.display = 'none';
                        }
                    });
                
                    const countBadge = document.querySelector('.notifications-count-badge');
                    if (countBadge) {
                        countBadge.textContent = '0';
                        countBadge.style.display = 'none';
                    }
                    
                    markAllBtn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                        </svg>
                        All Marked as Read
                    `;
                    markAllBtn.disabled = true;
                    markAllBtn.style.background = '#28a745';
                    
                    const headerNotificationCount = document.getElementById('notifications-count');
                    if (headerNotificationCount) {
                        headerNotificationCount.textContent = '';
                        headerNotificationCount.style.display = 'none';
                        headerNotificationCount.setAttribute('data-count', '0');
                    }
                    
                    showNotificationToast('All notifications marked as read!', 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                markAllBtn.innerHTML = originalText;
                markAllBtn.disabled = false;
                showNotificationToast('Error marking notifications as read', 'error');
            });
        });
    }
    
    document.querySelectorAll('.mark-single-read').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.notification-card');
            
            card.classList.add('read');
            const indicator = card.querySelector('.notification-indicator');
            if (indicator) {
                indicator.style.display = 'none';
            }
            
            const countBadge = document.querySelector('.notifications-count-badge');
            if (countBadge) {
                let currentCount = parseInt(countBadge.textContent) || 0;
                if (currentCount > 0) {
                    currentCount--;
                    if (currentCount === 0) {
                        countBadge.style.display = 'none';
                        const markAllBtn = document.getElementById('mark-all-read-page');
                        if (markAllBtn) {
                            markAllBtn.disabled = true;
                            markAllBtn.innerHTML = `
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                </svg>
                                All Read
                            `;
                        }
                    } else {
                        countBadge.textContent = currentCount;
                    }
                }
            }
            
            const headerNotificationCount = document.getElementById('notifications-count');
            if (headerNotificationCount) {
                let headerCount = parseInt(headerNotificationCount.getAttribute('data-count')) || 0;
                if (headerCount > 0) {
                    headerCount--;
                    if (headerCount === 0) {
                        headerNotificationCount.textContent = '';
                        headerNotificationCount.style.display = 'none';
                        headerNotificationCount.setAttribute('data-count', '0');
                    } else {
                        headerNotificationCount.textContent = headerCount;
                        headerNotificationCount.setAttribute('data-count', headerCount);
                    }
                }
            }
            
            showNotificationToast('Notification marked as read', 'success');
        });
    });
    
    setInterval(function() {
        const countBadge = document.querySelector('.notifications-count-badge');
        const hasUnread = countBadge && countBadge.style.display !== 'none' && parseInt(countBadge.textContent) > 0;
        
        if (hasUnread) {
            fetch('/api/notifications', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                const headerNotificationCount = document.getElementById('notifications-count');
                if (headerNotificationCount) {
                    if (data.unreadCount > 0) {
                        headerNotificationCount.textContent = data.unreadCount;
                        headerNotificationCount.style.display = 'flex';
                        headerNotificationCount.setAttribute('data-count', data.unreadCount);
                    } else {
                        headerNotificationCount.textContent = '';
                        headerNotificationCount.style.display = 'none';
                        headerNotificationCount.setAttribute('data-count', '0');
                    }
                }
            })
            .catch(error => {
                console.error('Error refreshing notifications:', error);
            });
        }
    }, 30000);
    
    function showNotificationToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `notification-toast ${type}`;
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                ${type === 'success' ? 
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>' : 
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
                }
                <span>${message}</span>
            </div>
        `;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : '#dc3545'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 10000;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease;
            max-width: 300px;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
});
