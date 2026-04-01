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
