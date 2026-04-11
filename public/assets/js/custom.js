/* ============================================================
   Payroll System – Custom JS
   ============================================================ */

/* Sidebar toggle */
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar   = document.getElementById('sidebar');
    const body      = document.body;

    function isMobile() { return window.innerWidth <= 992; }

    // Restore desktop collapsed state from localStorage
    if (!isMobile() && localStorage.getItem('sidebarCollapsed') === '1') {
        body.classList.add('sidebar-collapsed');
    }

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            if (isMobile()) {
                // Mobile: slide in/out
                sidebar.classList.toggle('show');
            } else {
                // Desktop: collapse/expand
                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed',
                    body.classList.contains('sidebar-collapsed') ? '1' : '0');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (isMobile()) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // Mark active nav link based on current URL (longest prefix match wins)
    const path = window.location.pathname;
    let bestLink = null, bestLen = 0;
    document.querySelectorAll('#sidebar .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href === '#') return;
        try {
            const linkPath = new URL(href, window.location.origin).pathname;
            if (linkPath.length > 1 && (path === linkPath || path.startsWith(linkPath + '/')) && linkPath.length > bestLen) {
                bestLink = link;
                bestLen  = linkPath.length;
            }
        } catch (e) {}
    });
    if (bestLink) {
        bestLink.classList.add('active');
    }

    // Auto-dismiss alerts after 5 s
    document.querySelectorAll('.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Confirm delete links
    document.querySelectorAll('a[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (! confirm(el.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // Format currency fields on blur
    document.querySelectorAll('input[data-currency]').forEach(function (inp) {
        inp.addEventListener('blur', function () {
            const val = parseFloat(inp.value);
            if (! isNaN(val)) inp.value = val.toFixed(2);
        });
    });

    // Auto-compute daily rate when monthly salary changes
    const monthlySalaryInput = document.getElementById('monthly_salary');
    const dailyRateDisplay   = document.getElementById('daily_rate_preview');
    if (monthlySalaryInput && dailyRateDisplay) {
        monthlySalaryInput.addEventListener('input', function () {
            const monthly = parseFloat(this.value) || 0;
            dailyRateDisplay.textContent = 'Daily Rate: ₱ ' + (monthly / 22).toFixed(2);
        });
    }
});

/* ---- Utility helpers ---- */
function formatCurrency(amount) {
    return '₱ ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function printElement(id) {
    const el = document.getElementById(id);
    const orig = document.body.innerHTML;
    document.body.innerHTML = el.innerHTML;
    window.print();
    document.body.innerHTML = orig;
    window.location.reload();
}
