document.addEventListener('DOMContentLoaded', function () {
    const searchForm = document.querySelector('[data-customer-search]');
    const searchInput = document.querySelector('[data-customer-search-input]');
    const serviceCards = document.querySelectorAll('[data-customer-service-card]');
    const emptyState = document.querySelector('[data-search-empty]');

    const authModal = document.querySelector('[data-auth-modal]');
    const openButtons = document.querySelectorAll('[data-auth-open]');
    const closeButtons = document.querySelectorAll('[data-auth-close]');
    const tabs = document.querySelectorAll('[data-auth-tab]');
    const panels = document.querySelectorAll('[data-auth-panel]');

    function filterServices(keyword) {
        const query = String(keyword || '').toLowerCase().trim();
        let visibleCount = 0;

        serviceCards.forEach(function (card) {
            const name = card.getAttribute('data-name') || '';
            const visible = !query || name.includes(query);

            card.hidden = !visible;

            if (visible) {
                visibleCount++;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }
    }

    function setTab(type) {
        const selected = type === 'signup' ? 'signup' : 'signin';

        tabs.forEach(function (tab) {
            tab.classList.toggle('active', tab.dataset.authTab === selected);
        });

        panels.forEach(function (panel) {
            panel.classList.toggle('active', panel.dataset.authPanel === selected);
        });
    }

    function openModal(type) {
        if (!authModal) return;

        setTab(type);
        authModal.hidden = false;
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        if (!authModal) return;

        authModal.hidden = true;
        document.body.classList.remove('modal-open');
    }

    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function (event) {
            event.preventDefault();
            filterServices(searchInput.value);

            const section = document.querySelector('#services');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        searchInput.addEventListener('input', function () {
            filterServices(searchInput.value);
        });
    }

    document.querySelectorAll('[data-category-filter]').forEach(function (item) {
        item.addEventListener('click', function () {
            const keyword = item.dataset.categoryFilter || '';

            if (searchInput) {
                searchInput.value = keyword;
            }

            filterServices(keyword);
        });
    });

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button.dataset.authOpen);
        });
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            setTab(tab.dataset.authTab);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    if (authModal) {
        const initial = authModal.dataset.authInitial;

        if (initial === 'signin' || initial === 'signup') {
            openModal(initial);
        }
    }
});