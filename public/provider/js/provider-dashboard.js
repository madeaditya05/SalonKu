document.addEventListener('DOMContentLoaded', function () {
    initProviderSidebar();
    initProviderSidebarSearch();
    initProviderSubmenus();
    initProviderProfileDropdown();
    initProviderDashboardTabs();
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
            document.body.classList.remove('sidebar-collapsed');
            sidebar.classList.remove('hover-expanded');
            return;
        }

        document.body.classList.toggle('sidebar-collapsed', isCollapsed);
        sidebar.classList.remove('hover-expanded');

        try {
            localStorage.setItem(collapsedStorageKey, isCollapsed ? '1' : '0');
        } catch (error) {
            console.warn('Cannot save sidebar state.', error);
        }
    }

    function loadCollapsedState() {
        if (!isDesktop()) {
            document.body.classList.remove('sidebar-collapsed');
            sidebar.classList.remove('hover-expanded');
            return;
        }

        let saved = '0';

        try {
            saved = localStorage.getItem(collapsedStorageKey) || '0';
        } catch (error) {
            saved = '0';
        }

        document.body.classList.toggle('sidebar-collapsed', saved === '1');
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

            const nextCollapsed = !document.body.classList.contains('sidebar-collapsed');
            setCollapsed(nextCollapsed);
        });
    }

    sidebar.addEventListener('mouseenter', function () {
        if (document.body.classList.contains('sidebar-collapsed') && isDesktop()) {
            sidebar.classList.add('hover-expanded');
        }
    });

    sidebar.addEventListener('mouseleave', function () {
        sidebar.classList.remove('hover-expanded');
    });

    if (menuScroll) {
        menuScroll.addEventListener('scroll', saveSidebarScroll);

        sidebar.querySelectorAll('a.sidebar-link, .sidebar-current-menu, .sidebar-submenu a').forEach(function (link) {
            link.addEventListener('click', saveSidebarScroll);
        });

        window.addEventListener('beforeunload', saveSidebarScroll);
    }

    window.addEventListener('resize', function () {
        loadCollapsedState();
        restoreSidebarScroll();
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

    const menuItems = Array.from(nav.querySelectorAll('.sidebar-menu-item'));
    const sections = Array.from(nav.querySelectorAll('[data-sidebar-section]'));

    function normalizeText(value) {
        return String(value || '').toLowerCase().trim();
    }

    function getItemText(item) {
        const text = item.innerText || '';
        const keywords = item.dataset.menuKeywords || '';
        return normalizeText(text + ' ' + keywords);
    }

    function showAll() {
        menuItems.forEach(function (item) {
            item.classList.remove('sidebar-search-hidden');
        });

        sections.forEach(function (section) {
            section.classList.remove('sidebar-search-hidden');
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

            item.classList.toggle('sidebar-search-hidden', !isMatch);

            if (isMatch) {
                visibleCount += 1;

                const group = item.closest('.sidebar-group');

                if (group) {
                    group.classList.add('open');
                }
            }
        });

        sections.forEach(function (section) {
            section.classList.add('sidebar-search-hidden');
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
            const group = button.closest('.sidebar-group');

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