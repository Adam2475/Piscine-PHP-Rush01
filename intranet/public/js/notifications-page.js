document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('select-all-notifications');
    const deleteSelectedBtn = document.getElementById('delete-selected-notifications');
    const selectedCountSpan = document.getElementById('selected-count');
    const notificationCards = document.querySelectorAll('.notification-card');
    const checkboxes = document.querySelectorAll('.notification-select');
    
    let isAllSelected = false;
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            isAllSelected = !isAllSelected;
            checkboxes.forEach(checkbox => {
                checkbox.checked = isAllSelected;
            });
            updateSelectAllButtonText();
            updateSelectedCount();
        });
    }
    
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function() {
            const selectedIndices = getSelectedIndices();
            
            if (selectedIndices.length === 0) {
                showNotificationToast('No notifications selected', 'error');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete ${selectedIndices.length} notification(s)?`)) {
                return;
            }
            
            const originalText = deleteSelectedBtn.innerHTML;
            deleteSelectedBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                Deleting...
            `;
            deleteSelectedBtn.disabled = true;
            
            fetch('/api/notifications/delete-selected', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    indices: selectedIndices
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const selectedCards = getSelectedCards();
                    selectedCards.forEach(card => {
                        card.style.animation = 'slideOut 0.3s ease-out forwards';
                        setTimeout(() => {
                            if (card.parentNode) {
                                card.parentNode.removeChild(card);
                            }
                        }, 300);
                    });
                    
                    setTimeout(() => {
                        updateNotificationCounters(data.deletedCount);
                        resetSelections();
                        
                        const remainingCards = document.querySelectorAll('.notification-card');
                        if (remainingCards.length === 0) {
                            showEmptyState();
                        }
                        
                        showNotificationToast(data.message, 'success');
                    }, 300);
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                deleteSelectedBtn.innerHTML = originalText;
                deleteSelectedBtn.disabled = false;
                showNotificationToast('Error deleting notifications', 'error');
            });
        });
    }
    
    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.notification-select:checked').length;
        const totalCount = checkboxes.length;
        
        selectedCountSpan.textContent = selectedCount;
        deleteSelectedBtn.disabled = selectedCount === 0;
        
        if (selectedCount === 0) {
            isAllSelected = false;
        } else if (selectedCount === totalCount) {
            isAllSelected = true;
        } else {
            isAllSelected = false;
        }
        updateSelectAllButtonText();
    }
    
    function updateSelectAllButtonText() {
        if (selectAllBtn) {
            if (isAllSelected) {
                selectAllBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    </svg>
                    Deselect All
                `;
            } else {
                selectAllBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor">
                        <polyline points="9,11 12,14 22,4"></polyline>
                        <path d="m21,3H3C1.895,3 1,3.895 1,5v14c0,1.105 0.895,2 2,2h18c1.105,0 2,-0.895 2,-2V5C23,3.895 22.105,3 21,3z"></path>
                    </svg>
                    Select All
                `;
            }
        }
    }
    
    function getSelectedIndices() {
        const selected = [];
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selected.push(parseInt(checkbox.value));
            }
        });
        return selected;
    }
    
    function getSelectedCards() {
        const selected = [];
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selected.push(checkbox.closest('.notification-card'));
            }
        });
        return selected;
    }
    
    function resetSelections() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        isAllSelected = false;
        updateSelectAllButtonText();
        updateSelectedCount();
    }
    
    function updateNotificationCounters(deletedCount) {
        const countBadge = document.querySelector('.notifications-count-badge');
        if (countBadge) {
            let currentCount = parseInt(countBadge.textContent) || 0;
            currentCount = Math.max(0, currentCount - deletedCount);
            countBadge.textContent = currentCount;
            if (currentCount === 0) {
                countBadge.style.display = 'none';
            }
        }
        
        const headerNotificationCount = document.getElementById('notifications-count');
        if (headerNotificationCount) {
            let currentCount = parseInt(headerNotificationCount.getAttribute('data-count') || '0');
            currentCount = Math.max(0, currentCount - deletedCount);
            if (currentCount === 0) {
                headerNotificationCount.textContent = '';
                headerNotificationCount.style.display = 'none';
                headerNotificationCount.setAttribute('data-count', '0');
            } else {
                headerNotificationCount.textContent = currentCount;
                headerNotificationCount.setAttribute('data-count', currentCount);
            }
        }
        
        const paginationInfo = document.querySelector('.pagination-info');
        if (paginationInfo) {
            const remainingCards = document.querySelectorAll('.notification-card');
            paginationInfo.textContent = `Showing ${remainingCards.length} notifications`;
        }
    }
    
    function showEmptyState() {
        const notificationsContent = document.querySelector('.notifications-content');
        if (notificationsContent) {
            notificationsContent.innerHTML = `
                <div class="notifications-empty-state">
                    <div class="empty-state-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3>No notifications</h3>
                    <p>All notifications have been deleted. New notifications will appear here.</p>
                    <a href="/" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Go to Dashboard
                    </a>
                </div>
            `;
        }
    }
    
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
    
    updateSelectedCount();
});
