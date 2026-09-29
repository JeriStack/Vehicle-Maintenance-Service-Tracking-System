// assets/js/app.js
document.addEventListener('DOMContentLoaded', event => {
    // Toggle the side navigation
    const toggleBtns = document.querySelectorAll('.sidebar-toggle-btn, #sidebarToggle');
    
    // Restore state from localStorage for desktop
    const isCollapsed = localStorage.getItem('vms_sidebar_collapsed') === 'true';
    if (isCollapsed && window.innerWidth >= 768) {
        document.body.classList.add('sb-sidenav-toggled');
    }

    // Initialize tooltips safely
    let tooltipList = [];
    try {
        const initTooltips = () => {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('#sidebar-wrapper [data-bs-toggle="tooltip"]'));
            tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover',
                    placement: 'right'
                });
            });
        };
        initTooltips();
    } catch (err) {
        console.error("Tooltip init error: ", err);
    }

    const updateUIState = () => {
        const collapsed = document.body.classList.contains('sb-sidenav-toggled');
        
        // Update toggle buttons aria & icons
        toggleBtns.forEach(btn => {
            btn.setAttribute('aria-expanded', !collapsed);
            const icon = btn.querySelector('.toggle-icon');
            if (icon) {
                if (collapsed) {
                    icon.classList.remove('bi-chevron-left');
                    icon.classList.add('bi-chevron-right');
                } else {
                    icon.classList.remove('bi-chevron-right');
                    icon.classList.add('bi-chevron-left');
                }
            }
        });

        // Update tooltips
        if (collapsed && window.innerWidth >= 768) {
            tooltipList.forEach(t => t.enable());
        } else {
            tooltipList.forEach(t => {
                t.hide();
                t.disable();
            });
        }
    };

    updateUIState();

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            const collapsed = document.body.classList.contains('sb-sidenav-toggled');
            localStorage.setItem('vms_sidebar_collapsed', collapsed);
            updateUIState();
        });
    });

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
