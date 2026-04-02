/**
 * Design Task Manager — Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {

    // --- Mobile Sidebar Toggle ---
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('open');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }

    // --- Auto-dismiss flash alerts after 5 seconds ---
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() { alert.remove(); }, 300);
        }, 5000);
    });

    // --- Confirm delete actions ---
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // --- Modal handling ---
    document.querySelectorAll('[data-modal]').forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('active');
        });
    });

    document.querySelectorAll('.modal-close, .modal-cancel').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });

    // --- Progress bar dynamic update ---
    document.querySelectorAll('select[name="progress_percentage"]').forEach(function(select) {
        select.addEventListener('change', function() {
            const bar = this.closest('form').querySelector('.progress-bar');
            if (bar) {
                bar.style.width = this.value + '%';
            }
        });
    });

    // --- Form validation helper ---
    document.querySelectorAll('form[data-validate]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            let valid = true;
            this.querySelectorAll('[required]').forEach(function(field) {
                if (!field.value.trim()) {
                    field.style.borderColor = '#EF4444';
                    valid = false;
                } else {
                    field.style.borderColor = '';
                }
            });
            if (!valid) {
                e.preventDefault();
            }
        });
    });

    // --- Quick inline status/progress update (AJAX) ---
    document.querySelectorAll('.inline-update-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const action = this.getAttribute('action');

            fetch(action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update visual elements
                    if (data.status_html) {
                        const badge = this.closest('tr').querySelector('.status-badge');
                        if (badge) badge.outerHTML = data.status_html;
                    }
                    if (data.progress !== undefined) {
                        const bar = this.closest('tr').querySelector('.progress-bar');
                        if (bar) bar.style.width = data.progress + '%';
                        const text = this.closest('tr').querySelector('.progress-text');
                        if (text) text.textContent = data.progress + '%';
                    }
                    // Show brief success indication
                    showToast(data.message || 'Updated');
                } else {
                    showToast(data.message || 'Error', 'error');
                }
            })
            .catch(function() {
                // Fallback: submit normally
                form.submit();
            });
        });
    });

});

// --- Notification Bell System ---
(function() {
    var bell = document.getElementById('notifBell');
    var dropdown = document.getElementById('notifDropdown');
    var badge = document.getElementById('notifBadge');
    var list = document.getElementById('notifList');
    var markAllBtn = document.getElementById('notifMarkAll');

    if (!bell) return;

    // Toggle dropdown
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        var isOpen = dropdown.classList.contains('open');
        dropdown.classList.toggle('open');
        if (!isOpen) fetchNotifications();
    });

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (dropdown && !dropdown.contains(e.target) && e.target !== bell) {
            dropdown.classList.remove('open');
        }
    });

    // Mark all as read
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fetch('/api/notifications.php?action=read_all', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: ''
            }).then(function() {
                updateBadge(0);
                document.querySelectorAll('.notif-item.unread').forEach(function(el) {
                    el.classList.remove('unread');
                });
            });
        });
    }

    // Fetch and render notifications
    function fetchNotifications() {
        fetch('/api/notifications.php?action=recent')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                updateBadge(data.unread || 0);
                renderNotifications(data.notifications || []);
            })
            .catch(function() {});
    }

    // Poll for unread count every 30 seconds
    function pollCount() {
        fetch('/api/notifications.php?action=count')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var newCount = data.count || 0;
                var oldCount = parseInt(badge.textContent) || 0;
                updateBadge(newCount);
                // Play subtle animation if new notifications arrived
                if (newCount > oldCount && newCount > 0) {
                    bell.classList.add('notif-shake');
                    setTimeout(function() { bell.classList.remove('notif-shake'); }, 600);
                }
            })
            .catch(function() {});
    }

    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    function renderNotifications(items) {
        if (!items.length) {
            list.innerHTML = '<div class="notif-empty">' + (list.dataset.empty || 'No notifications') + '</div>';
            return;
        }
        var html = '';
        items.forEach(function(n) {
            var cls = n.is_read ? 'notif-item' : 'notif-item unread';
            var link = n.link || '#';
            html += '<a href="' + link + '" class="' + cls + '" data-id="' + n.id + '">';
            html += '  <div class="notif-icon">' + n.icon + '</div>';
            html += '  <div class="notif-content">';
            html += '    <div class="notif-title">' + escHtml(n.title) + '</div>';
            html += '    <div class="notif-message">' + escHtml(n.message) + '</div>';
            html += '    <div class="notif-time">' + escHtml(n.time) + '</div>';
            html += '  </div>';
            html += '</a>';
        });
        list.innerHTML = html;

        // Click handler to mark as read
        list.querySelectorAll('.notif-item').forEach(function(el) {
            el.addEventListener('click', function() {
                var nid = this.getAttribute('data-id');
                if (nid && this.classList.contains('unread')) {
                    fetch('/api/notifications.php?action=read', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + nid
                    });
                }
            });
        });
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    }

    // Initial load + polling
    pollCount();
    setInterval(pollCount, 30000);
})();

/**
 * Simple toast notification
 */
function showToast(message, type) {
    type = type || 'success';
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + type;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '999';
    toast.style.minWidth = '200px';
    toast.style.animation = 'fadeIn 0.3s ease';
    toast.innerHTML = message + '<button class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
    document.body.appendChild(toast);
    setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3000);
}
