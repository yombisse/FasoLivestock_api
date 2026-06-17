/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Sidebar Manager (Alpine.js component)
═══════════════════════════════════════════════════════════ */

function sidebarManager() {
    return {
        isOpen:   true,
        isMobile: false,

        init() {
            this.checkMobile();
            this.restoreState();

            window.addEventListener('resize', () => {
                this.checkMobile();
            });
        },

        checkMobile() {
            const wasMobile = this.isMobile;
            this.isMobile   = window.innerWidth < 768;

            // Basculement mobile ↔ desktop
            if (this.isMobile && !wasMobile) {
                this.isOpen = false;
                this.applyLayout();
            }

            if (!this.isMobile && wasMobile) {
                this.restoreState();
            }
        },

        restoreState() {
            if (this.isMobile) { this.isOpen = false; return; }
            const saved  = localStorage.getItem('fasoSidebarOpen');
            this.isOpen  = saved !== null ? saved === 'true' : true;
            this.applyLayout();
        },

        toggle() {
            this.isOpen = !this.isOpen;
            this.applyLayout();

            if (!this.isMobile) {
                localStorage.setItem('fasoSidebarOpen', this.isOpen);
            }
        },

        close() {
            if (this.isMobile) {
                this.isOpen = false;
                this.applyLayout();
            }
        },

        applyLayout() {
            const sidebar = document.getElementById('sidebar');
            const main    = document.getElementById('main-content');
            const overlay = document.getElementById('sidebar-overlay');

            if (!sidebar || !main) return;

            if (this.isMobile) {
                sidebar.classList.toggle('mobile-open', this.isOpen);
                overlay?.classList.toggle('active', this.isOpen);
                main.style.marginLeft = '';
            } else {
                sidebar.style.transform = this.isOpen ? '' : `translateX(-${getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width')})`;
                main.classList.toggle('expanded', !this.isOpen);
                overlay?.classList.remove('active');
            }
        },
    };
}

/* ─── Sous-menus Alpine component ───────────────────────── */
function navSubmenu(isActiveByDefault = false) {
    return {
        open: isActiveByDefault,
        toggle() { this.open = !this.open; },
    };
}