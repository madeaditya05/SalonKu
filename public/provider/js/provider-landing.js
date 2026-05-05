document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;

    const modals = document.querySelectorAll('.modal-overlay');
    const openButtons = document.querySelectorAll('[data-open-modal]');
    const closeButtons = document.querySelectorAll('[data-close-modal]');
    const switchButtons = document.querySelectorAll('[data-switch-modal]');
    const passwordToggles = document.querySelectorAll('[data-toggle-password]');

    const countryPicker = document.getElementById('phoneCountryPicker');
    const countryBtn = document.getElementById('countrySelectedBtn');
    const countrySearch = document.getElementById('countrySearchInput');
    const countryOptions = document.querySelectorAll('.country-option');
    const countryCodeInput = document.getElementById('countryCodeInput');
    const selectedCountryFlag = document.getElementById('selectedCountryFlag');
    const selectedCountryCode = document.getElementById('selectedCountryCode');

    function openModal(modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            return;
        }

        closeAllModals(false);
        closeCountryDropdown();

        modal.classList.add('show');
        body.classList.add('modal-open');
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.remove('show');
        closeCountryDropdown();

        const openedModal = document.querySelector('.modal-overlay.show');

        if (!openedModal) {
            body.classList.remove('modal-open');
        }
    }

    function closeAllModals(removeBodyClass = true) {
        modals.forEach(function (modal) {
            modal.classList.remove('show');
        });

        closeCountryDropdown();

        if (removeBodyClass) {
            body.classList.remove('modal-open');
        }
    }

    function closeCountryDropdown() {
        if (countryPicker) {
            countryPicker.classList.remove('open');
        }
    }

    function openCountryDropdown() {
        if (!countryPicker) {
            return;
        }

        countryPicker.classList.add('open');

        if (countrySearch) {
            countrySearch.value = '';
            filterCountries('');

            setTimeout(function () {
                countrySearch.focus();
            }, 50);
        }
    }

    function filterCountries(keyword) {
        const searchText = keyword.toLowerCase().trim();

        countryOptions.forEach(function (option) {
            const code = option.getAttribute('data-code') || '';
            const name = option.getAttribute('data-name') || '';
            const searchTarget = `${code} ${name}`.toLowerCase();

            option.style.display = searchTarget.includes(searchText) ? 'grid' : 'none';
        });
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            let modalId = button.getAttribute('data-open-modal');

            if (!modalId) {
                modalId = 'registerModal';
            }

            openModal(modalId);
        });
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = button.closest('.modal-overlay');
            closeModal(modal);
        });
    });

    switchButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const modalId = button.getAttribute('data-switch-modal');

            if (modalId) {
                openModal(modalId);
            }
        });
    });

    modals.forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    passwordToggles.forEach(function (button) {
        button.addEventListener('click', function () {
            const inputId = button.getAttribute('data-toggle-password');
            const input = document.getElementById(inputId);

            if (!input) {
                return;
            }

            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = '◉';
            } else {
                input.type = 'password';
                button.textContent = '⌧';
            }
        });
    });

    if (countryBtn && countryPicker) {
        countryBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (countryPicker.classList.contains('open')) {
                closeCountryDropdown();
            } else {
                openCountryDropdown();
            }
        });
    }

    if (countrySearch) {
        countrySearch.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        countrySearch.addEventListener('input', function () {
            filterCountries(countrySearch.value);
        });
    }

    countryOptions.forEach(function (option) {
        option.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const code = option.getAttribute('data-code');
            const flag = option.getAttribute('data-flag');

            if (countryCodeInput && code) {
                countryCodeInput.value = code;
            }

            if (selectedCountryFlag && flag) {
                selectedCountryFlag.textContent = flag;
            }

            if (selectedCountryCode && code) {
                selectedCountryCode.textContent = code;
            }

            countryOptions.forEach(function (item) {
                item.classList.remove('active');
            });

            option.classList.add('active');
            closeCountryDropdown();
        });
    });

    document.addEventListener('click', function (event) {
        if (countryPicker && !countryPicker.contains(event.target)) {
            closeCountryDropdown();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllModals();
            closeCountryDropdown();
        }
    });

    if (body.dataset.openRegister === 'true') {
        openModal('registerModal');
    }

    if (body.dataset.openSignin === 'true') {
        openModal('signinModal');
    }
});