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
    var wrapper = document.getElementById('notifWrapper');

    if (!bell || !dropdown) return;

    var isOpen = false;

    // Bell hover effect
    bell.addEventListener('mouseenter', function() {
        this.style.background = '#EEF2FF';
        this.style.borderColor = '#4F46E5';
        this.style.color = '#4F46E5';
    });
    bell.addEventListener('mouseleave', function() {
        this.style.background = 'none';
        this.style.borderColor = '#e2e8f0';
        this.style.color = '#64748b';
    });

    // Toggle dropdown
    bell.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        isOpen = !isOpen;
        if (isOpen) {
            dropdown.style.display = 'flex';
            fetchNotifications();
        } else {
            dropdown.style.display = 'none';
        }
    });

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (isOpen && wrapper && !wrapper.contains(e.target)) {
            isOpen = false;
            dropdown.style.display = 'none';
        }
    });

    // Mark all as read
    if (markAllBtn) {
        markAllBtn.addEventListener('mouseenter', function() { this.style.background = '#EEF2FF'; });
        markAllBtn.addEventListener('mouseleave', function() { this.style.background = 'none'; });
        markAllBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fetch('/api/notifications.php?action=read_all', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: ''
            }).then(function() {
                updateBadge(0);
                var items = list.querySelectorAll('[data-nid]');
                items.forEach(function(el) { el.style.background = '#fff'; });
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
            .catch(function(err) {
                list.innerHTML = '<div style="padding:30px 16px;text-align:center;color:#ef4444;font-size:0.85rem;">Error loading notifications</div>';
            });
    }

    // Poll for unread count every 30 seconds
    function pollCount() {
        fetch('/api/notifications.php?action=count')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var newCount = data.count || 0;
                var oldCount = parseInt(badge.textContent) || 0;
                updateBadge(newCount);
                if (newCount > oldCount && newCount > 0) {
                    shakeBell();
                }
            })
            .catch(function() {});
    }

    function shakeBell() {
        var frames = [0, 14, -14, 8, -8, 0];
        var i = 0;
        var interval = setInterval(function() {
            bell.style.transform = 'rotate(' + frames[i] + 'deg)';
            i++;
            if (i >= frames.length) { clearInterval(interval); bell.style.transform = ''; }
        }, 80);
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
        var emptyText = list.getAttribute('data-empty') || 'No notifications';
        if (!items.length) {
            list.innerHTML = '<div style="padding:40px 16px;text-align:center;color:#94a3b8;font-size:0.88rem;">' +
                '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 10px;display:block;"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>' +
                emptyText + '</div>';
            return;
        }
        var html = '';
        items.forEach(function(n) {
            var bg = n.is_read ? '#fff' : '#EEF2FF';
            var link = n.link || '#';
            html += '<a href="' + link + '" data-nid="' + n.id + '" data-read="' + n.is_read + '" style="display:flex;gap:10px;padding:12px 16px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;background:' + bg + ';transition:background 0.2s;">';
            html += '<div style="font-size:1.4rem;flex-shrink:0;width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border-radius:50%;">' + n.icon + '</div>';
            html += '<div style="flex:1;min-width:0;overflow:hidden;">';
            html += '<div style="font-size:0.84rem;font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escHtml(n.title) + '</div>';
            html += '<div style="font-size:0.78rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px;">' + escHtml(n.message) + '</div>';
            html += '<div style="font-size:0.7rem;color:#94a3b8;margin-top:3px;">' + escHtml(n.time) + '</div>';
            html += '</div></a>';
        });
        list.innerHTML = html;

        // Click handler to mark as read
        list.querySelectorAll('[data-nid]').forEach(function(el) {
            el.addEventListener('mouseenter', function() {
                this.style.background = this.getAttribute('data-read') === '0' ? '#dde4ff' : '#f8fafc';
            });
            el.addEventListener('mouseleave', function() {
                this.style.background = this.getAttribute('data-read') === '0' ? '#EEF2FF' : '#fff';
            });
            el.addEventListener('click', function() {
                var nid = this.getAttribute('data-nid');
                if (nid && this.getAttribute('data-read') === '0') {
                    this.setAttribute('data-read', '1');
                    this.style.background = '#fff';
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
