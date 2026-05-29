document.addEventListener('DOMContentLoaded', function () {
    initProviderSidebar();
    initProviderSidebarSearch();
    initProviderSubmenus();
    initProviderProfileDropdown();
    initProviderDashboardTabs();
    initProviderDashboardTableSearch();
    initAnalyticsDashboardTooltips();
    initProviderAdminFilterPanels();
});

function initProviderSidebar() {
    const sidebar = document.getElementById('providerSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const overlay = document.getElementById('providerSidebarOverlay');
    const menuScroll = document.getElementById('providerSidebarMenuScroll');

    if (!sidebar) {
        return;
    }

    const collapsedStorageKey = 'provider_sidebar_collapsed';
    const scrollStorageKey = 'provider_sidebar_menu_scroll';

    function isDesktop() {
        return window.innerWidth > 992;
    }

    function setCollapsedClasses(value) {
        document.body.classList.toggle('sidebar-collapsed', value);
        document.body.classList.toggle('admin-sidebar-collapsed', value);
    }

    function removeCollapsedClasses() {
        document.body.classList.remove('sidebar-collapsed');
        document.body.classList.remove('admin-sidebar-collapsed');
    }

    function hasCollapsedClass() {
        return document.body.classList.contains('sidebar-collapsed')
            || document.body.classList.contains('admin-sidebar-collapsed');
    }

    function openMobileSidebar() {
        if (!sidebar || !overlay) return;

        sidebar.classList.add('show');
        overlay.classList.add('show');
    }

    function closeMobileSidebar() {
        if (!sidebar || !overlay) return;

        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }

    function saveSidebarScroll() {
        if (!menuScroll) return;

        try {
            localStorage.setItem(scrollStorageKey, String(menuScroll.scrollTop));
        } catch (error) {
            console.warn('Cannot save sidebar scroll.', error);
        }
    }

    function restoreSidebarScroll() {
        if (!menuScroll) return;

        let savedScroll = '0';

        try {
            savedScroll = localStorage.getItem(scrollStorageKey) || '0';
        } catch (error) {
            savedScroll = '0';
        }

        requestAnimationFrame(function () {
            menuScroll.scrollTop = parseInt(savedScroll, 10) || 0;
        });
    }

    function setCollapsed(isCollapsed) {
        if (!isDesktop()) {
            removeCollapsedClasses();
            sidebar.classList.remove('hover-expanded');
            return;
        }

        setCollapsedClasses(isCollapsed);
        sidebar.classList.remove('hover-expanded');

        try {
            localStorage.setItem(collapsedStorageKey, isCollapsed ? '1' : '0');
        } catch (error) {
            console.warn('Cannot save sidebar state.', error);
        }
    }

    function loadCollapsedState() {
        if (!isDesktop()) {
            removeCollapsedClasses();
            sidebar.classList.remove('hover-expanded');
            return;
        }

        let saved = '0';

        try {
            saved = localStorage.getItem(collapsedStorageKey) || '0';
        } catch (error) {
            saved = '0';
        }

        setCollapsedClasses(saved === '1');
    }

    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', openMobileSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeMobileSidebar);
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            if (!isDesktop()) {
                closeMobileSidebar();
                return;
            }

            const nextCollapsed = !hasCollapsedClass();
            setCollapsed(nextCollapsed);
        });
    }

    sidebar.addEventListener('mouseenter', function () {
        if (hasCollapsedClass() && isDesktop()) {
            sidebar.classList.add('hover-expanded');
        }
    });

    sidebar.addEventListener('mouseleave', function () {
        sidebar.classList.remove('hover-expanded');
    });

    if (menuScroll) {
        menuScroll.addEventListener('scroll', saveSidebarScroll);

        sidebar.querySelectorAll('a.admin-menu-item, .admin-current-link, .admin-submenu a').forEach(function (link) {
            link.addEventListener('click', function () {
                saveSidebarScroll();

                if (!isDesktop()) {
                    closeMobileSidebar();
                }
            });
        });

        window.addEventListener('beforeunload', saveSidebarScroll);
    }

    window.addEventListener('resize', function () {
        loadCollapsedState();
        restoreSidebarScroll();

        if (isDesktop()) {
            closeMobileSidebar();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });

    loadCollapsedState();
    restoreSidebarScroll();
}

function initProviderSidebarSearch() {
    const searchInput = document.getElementById('providerSidebarSearch');
    const clearButton = document.getElementById('providerSidebarSearchClear');
    const nav = document.getElementById('providerSidebarNav');
    const emptyState = document.getElementById('providerSidebarSearchEmpty');
    const currentMenu = document.getElementById('providerSidebarCurrent');

    if (!searchInput || !nav) {
        return;
    }

    const menuItems = Array.from(nav.querySelectorAll('.admin-menu-search-item'));
    const sections = Array.from(nav.querySelectorAll('[data-section-title]'));

    function normalizeText(value) {
        return String(value || '').toLowerCase().trim();
    }

    function getItemText(item) {
        const text = item.innerText || '';
        const keywords = item.dataset.keywords || '';
        return normalizeText(text + ' ' + keywords);
    }

    function showAll() {
        menuItems.forEach(function (item) {
            item.classList.remove('search-hidden');
        });

        sections.forEach(function (section) {
            section.classList.remove('search-hidden');
        });

        if (emptyState) {
            emptyState.classList.remove('show');
        }

        if (clearButton) {
            clearButton.classList.remove('show');
        }

        if (currentMenu) {
            currentMenu.classList.remove('searching');
        }
    }

    function filterMenus() {
        const keyword = normalizeText(searchInput.value);

        if (!keyword) {
            showAll();
            return;
        }

        let visibleCount = 0;

        menuItems.forEach(function (item) {
            const isMatch = getItemText(item).includes(keyword);

            item.classList.toggle('search-hidden', !isMatch);

            if (isMatch) {
                visibleCount += 1;

                const group = item.closest('.admin-menu-group');

                if (group) {
                    group.classList.add('open');
                }
            }
        });

        sections.forEach(function (section) {
            section.classList.add('search-hidden');
        });

        if (emptyState) {
            emptyState.classList.toggle('show', visibleCount === 0);
        }

        if (clearButton) {
            clearButton.classList.add('show');
        }

        if (currentMenu) {
            currentMenu.classList.add('searching');
        }
    }

    searchInput.addEventListener('input', filterMenus);

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            searchInput.focus();
            showAll();
        });
    }

    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            searchInput.value = '';
            showAll();
            searchInput.blur();
        }
    });
}

function initProviderSubmenus() {
    document.querySelectorAll('[data-submenu-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const group = button.closest('.admin-menu-group');

            if (!group) return;

            group.classList.toggle('open');
        });
    });
}

function initProviderProfileDropdown() {
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');

    if (!profileToggle || !profileMenu) {
        return;
    }

    profileToggle.addEventListener('click', function (event) {
        event.stopPropagation();
        profileMenu.classList.toggle('show');
    });

    document.addEventListener('click', function (event) {
        if (!profileMenu.contains(event.target) && !profileToggle.contains(event.target)) {
            profileMenu.classList.remove('show');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            profileMenu.classList.remove('show');
        }
    });
}

function initProviderDashboardTabs() {
    document.querySelectorAll('.dashboard-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.dashboard-tab').forEach(function (item) {
                item.classList.remove('active');
            });

            tab.classList.add('active');
        });
    });
}

function initProviderDashboardTableSearch() {
    document.querySelectorAll('[data-dashboard-table-search]').forEach(function (input) {
        const card = input.closest('.dashboard-table-card');
        const table = card ? card.querySelector('[data-dashboard-table]') : null;

        if (!table) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('tbody tr'));

        input.addEventListener('input', function () {
            const keyword = input.value.trim().toLowerCase();

            rows.forEach(function (row) {
                row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
            });
        });
    });
}

function initAnalyticsDashboardTooltips() {
    const tooltip = document.querySelector('[data-dashboard-tooltip]');

    if (!tooltip) {
        return;
    }

    function showTooltip(target, event) {
        const text = target.getAttribute('data-tooltip');

        if (!text) {
            return;
        }

        tooltip.textContent = text;
        tooltip.classList.add('is-visible');
        moveTooltip(event);
    }

    function moveTooltip(event) {
        if (!event) {
            return;
        }

        const spacing = 16;
        const tooltipRect = tooltip.getBoundingClientRect();
        const targetRect = event.target && event.target.getBoundingClientRect ? event.target.getBoundingClientRect() : null;
        const anchorX = typeof event.clientX === 'number' ? event.clientX : (targetRect ? targetRect.left + targetRect.width / 2 : 0);
        const anchorY = typeof event.clientY === 'number' ? event.clientY : (targetRect ? targetRect.top : 0);
        let left = anchorX + spacing;
        let top = anchorY + spacing;

        if (left + tooltipRect.width > window.innerWidth - 12) {
            left = anchorX - tooltipRect.width - spacing;
        }

        if (top + tooltipRect.height > window.innerHeight - 12) {
            top = anchorY - tooltipRect.height - spacing;
        }

        tooltip.style.left = Math.max(12, left) + 'px';
        tooltip.style.top = Math.max(12, top) + 'px';
    }

    function hideTooltip() {
        tooltip.classList.remove('is-visible');
    }

    document.querySelectorAll('[data-tooltip]').forEach(function (target) {
        target.addEventListener('mouseenter', function (event) {
            showTooltip(target, event);
        });

        target.addEventListener('mousemove', moveTooltip);
        target.addEventListener('mouseleave', hideTooltip);

        target.addEventListener('focus', function (event) {
            showTooltip(target, event);
        });

        target.addEventListener('blur', hideTooltip);
    });
}

function initProviderAdminFilterPanels() {
    document.querySelectorAll('.admin-booking-mobile-filter-toggle').forEach(function (button) {
        const panel = button.closest('.admin-booking-filter-panel');

        if (!panel) {
            return;
        }

        button.addEventListener('click', function () {
            const expanded = !panel.classList.contains('is-expanded');

            panel.classList.toggle('is-expanded', expanded);
            button.classList.toggle('active', expanded);
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });
}
