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

    // Light / Dark Mode Toggle (2-state system-aware override via <meta name="color-scheme">)
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const colorSchemeMeta = document.querySelector('meta[name="color-scheme"]');
    const systemDarkMedia = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    let inMemoryScheme = null;

    const readStoredScheme = () => {
        try {
            const val = localStorage.getItem('color-scheme');
            return (val === 'light' || val === 'dark') ? val : null;
        } catch (e) {
            return inMemoryScheme;
        }
    };

    const getSystemScheme = () => (systemDarkMedia && systemDarkMedia.matches) ? 'dark' : 'light';

    const updateChartsForTheme = (resolved) => {
        if (typeof window.Chart === 'undefined') {
            return;
        }
        const textColor = resolved === 'dark' ? '#cbd5e1' : '#334155';
        const gridColor = resolved === 'dark' ? 'rgba(148, 163, 184, 0.18)' : 'rgba(15, 23, 42, 0.08)';
        window.Chart.defaults.color = textColor;
        window.Chart.defaults.borderColor = gridColor;

        if (window.Chart.instances) {
            Object.values(window.Chart.instances).forEach((chart) => {
                if (chart && chart.options && chart.options.scales) {
                    Object.values(chart.options.scales).forEach((scale) => {
                        if (scale.ticks) scale.ticks.color = textColor;
                        if (scale.grid) scale.grid.color = gridColor;
                    });
                }
                if (chart && typeof chart.update === 'function') {
                    chart.update('none');
                }
            });
        }
    };

    const applyColorScheme = (storedScheme) => {
        const metaValue = storedScheme ?? 'light dark';
        if (colorSchemeMeta) {
            colorSchemeMeta.content = metaValue;
        }
        const resolved = storedScheme ?? getSystemScheme();
        document.documentElement.setAttribute('data-bs-theme', resolved);

        if (themeToggleBtn) {
            const label = resolved === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
            themeToggleBtn.setAttribute('title', label);
            themeToggleBtn.setAttribute('aria-pressed', resolved === 'dark' ? 'true' : 'false');
        }

        updateChartsForTheme(resolved);
        window.dispatchEvent(new CustomEvent('vms:themechange', {
            detail: { theme: resolved, stored: storedScheme }
        }));
    };

    applyColorScheme(readStoredScheme());

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const systemScheme = getSystemScheme();
            const currentRendered = readStoredScheme() ?? systemScheme;
            const targetScheme = currentRendered === 'dark' ? 'light' : 'dark';

            if (targetScheme !== systemScheme) {
                inMemoryScheme = targetScheme;
                try { localStorage.setItem('color-scheme', targetScheme); } catch (e) {}
                applyColorScheme(targetScheme);
            } else {
                // Returning to system preference: remove stored override rather than pinning
                inMemoryScheme = null;
                try { localStorage.removeItem('color-scheme'); } catch (e) {}
                applyColorScheme(null);
            }
        });
    }

    // Keep in sync across browser tabs
    window.addEventListener('storage', (e) => {
        if (e.key === 'color-scheme') {
            const val = (e.newValue === 'light' || e.newValue === 'dark') ? e.newValue : null;
            inMemoryScheme = val;
            applyColorScheme(val);
        }
    });

    // React to OS theme changes without clearing an explicit stored override
    if (systemDarkMedia) {
        const onSystemChange = () => {
            const stored = readStoredScheme();
            if (!stored) {
                applyColorScheme(null);
            }
        };
        if (typeof systemDarkMedia.addEventListener === 'function') {
            systemDarkMedia.addEventListener('change', onSystemChange);
        }
    }

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
