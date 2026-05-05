document.addEventListener('DOMContentLoaded', function () {
    initCountryStateCityAndPhoneCode();
    initBranchImagePreview();
    initBranchHoliday();
    initBranchStaffMultiselect();
    initBranchTable();
    initBranchDeleteModal();
});

function initCountryStateCityAndPhoneCode() {
    const API_BASE_URL = 'https://countriesnow.space/api/v0.1';

    const countrySelect = document.getElementById('countrySelect');
    const stateSelect = document.getElementById('stateSelect');
    const citySelect = document.getElementById('citySelect');
    const phoneCodeSelect = document.getElementById('branchPhoneCodeSelect');

    const selectedCountry = countrySelect ? countrySelect.dataset.selected : '';
    const selectedState = stateSelect ? stateSelect.dataset.selected : '';
    const selectedCity = citySelect ? citySelect.dataset.selected : '';
    const selectedPhoneCode = phoneCodeSelect ? phoneCodeSelect.dataset.selected : '+62';

    let countryCodeMap = {};

    if (!countrySelect && !stateSelect && !citySelect && !phoneCodeSelect) {
        return;
    }

    async function getJson(url, options = {}) {
        const response = await fetch(url, options);

        if (!response.ok) {
            throw new Error('Request failed: ' + response.status);
        }

        return response.json();
    }

    function resetSelect(select, placeholder) {
        if (!select) return;

        select.innerHTML = '';

        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;

        select.appendChild(option);
    }

    function addOption(select, value, label, selectedValue = '') {
        if (!select) return;

        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;

        if (selectedValue && selectedValue === value) {
            option.selected = true;
        }

        select.appendChild(option);
    }

    function normalizePhoneCode(code) {
        if (!code) return '';

        const cleanCode = String(code).trim();

        if (!cleanCode) return '';

        return cleanCode.startsWith('+') ? cleanCode : '+' + cleanCode;
    }

    function addPhoneCodeOption(phoneCode, label = '') {
        if (!phoneCodeSelect || !phoneCode) return;

        const normalizedCode = normalizePhoneCode(phoneCode);

        const exists = Array.from(phoneCodeSelect.options).some(function (option) {
            return option.value === normalizedCode;
        });

        if (exists) return;

        const option = document.createElement('option');
        option.value = normalizedCode;
        option.textContent = label ? label + ' ' + normalizedCode : normalizedCode;

        phoneCodeSelect.appendChild(option);
    }

    function setPhoneCode(phoneCode) {
        if (!phoneCodeSelect || !phoneCode) return;

        const normalizedCode = normalizePhoneCode(phoneCode);

        addPhoneCodeOption(normalizedCode);
        phoneCodeSelect.value = normalizedCode;
    }

    async function loadCountryCodes() {
        if (!phoneCodeSelect) return;

        resetSelect(phoneCodeSelect, 'Loading codes...');

        try {
            const result = await getJson(API_BASE_URL + '/countries/codes');

            resetSelect(phoneCodeSelect, 'Select Code');

            if (!result.error && Array.isArray(result.data)) {
                result.data.forEach(function (country) {
                    const countryName = country.name || '';
                    const dialCode = country.dial_code || country.dialCode || country.code || '';

                    if (countryName && dialCode) {
                        countryCodeMap[countryName] = normalizePhoneCode(dialCode);
                        addPhoneCodeOption(dialCode, countryName);
                    }
                });
            }

            setPhoneCode(selectedPhoneCode || '+62');
        } catch (error) {
            console.error('Failed to load phone codes:', error);

            resetSelect(phoneCodeSelect, 'Failed to load codes');
            setPhoneCode(selectedPhoneCode || '+62');
        }
    }

    async function loadCountries() {
        if (!countrySelect) return;

        resetSelect(countrySelect, 'Loading countries...');

        try {
            const result = await getJson(API_BASE_URL + '/countries/states');

            resetSelect(countrySelect, 'Select Country');

            if (!result.error && Array.isArray(result.data)) {
                result.data.forEach(function (country) {
                    if (!country.name) return;

                    addOption(countrySelect, country.name, country.name, selectedCountry);
                });
            }

            if (selectedCountry) {
                await loadStates(selectedCountry, selectedState, selectedCity);
            } else {
                resetSelect(stateSelect, 'Select Country First');
                resetSelect(citySelect, 'Select State First');
            }
        } catch (error) {
            console.error('Failed to load countries:', error);
            resetSelect(countrySelect, 'Failed to load countries');
            resetSelect(stateSelect, 'Select Country First');
            resetSelect(citySelect, 'Select State First');
        }
    }

    async function loadStates(countryName, selectedStateValue = '', selectedCityValue = '') {
        if (!stateSelect || !citySelect) return;

        resetSelect(stateSelect, 'Loading states...');
        resetSelect(citySelect, 'Select State First');

        if (!countryName) {
            resetSelect(stateSelect, 'Select Country First');
            return;
        }

        try {
            const result = await getJson(API_BASE_URL + '/countries/states', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    country: countryName,
                }),
            });

            resetSelect(stateSelect, 'Select State');

            const states = result.data && Array.isArray(result.data.states)
                ? result.data.states
                : [];

            states.forEach(function (state) {
                const stateName = state.name || state;

                if (!stateName) return;

                addOption(stateSelect, stateName, stateName, selectedStateValue);
            });

            if (selectedStateValue) {
                await loadCities(countryName, selectedStateValue, selectedCityValue);
            }
        } catch (error) {
            console.error('Failed to load states:', error);
            resetSelect(stateSelect, 'Failed to load states');
            resetSelect(citySelect, 'Select State First');
        }
    }

    async function loadCities(countryName, stateName, selectedCityValue = '') {
        if (!citySelect) return;

        resetSelect(citySelect, 'Loading cities...');

        if (!countryName || !stateName) {
            resetSelect(citySelect, 'Select State First');
            return;
        }

        try {
            const result = await getJson(API_BASE_URL + '/countries/state/cities', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    country: countryName,
                    state: stateName,
                }),
            });

            resetSelect(citySelect, 'Select City');

            const cities = Array.isArray(result.data) ? result.data : [];

            cities.forEach(function (cityName) {
                if (!cityName) return;

                addOption(citySelect, cityName, cityName, selectedCityValue);
            });
        } catch (error) {
            console.error('Failed to load cities:', error);
            resetSelect(citySelect, 'Failed to load cities');
        }
    }

    if (countrySelect) {
        countrySelect.addEventListener('change', async function () {
            const countryName = this.value;

            if (stateSelect) {
                stateSelect.dataset.selected = '';
            }

            if (citySelect) {
                citySelect.dataset.selected = '';
            }

            resetSelect(citySelect, 'Select State First');

            if (countryCodeMap[countryName]) {
                setPhoneCode(countryCodeMap[countryName]);
            }

            await loadStates(countryName);
        });
    }

    if (stateSelect) {
        stateSelect.addEventListener('change', async function () {
            const countryName = countrySelect ? countrySelect.value : '';
            const stateName = this.value;

            if (citySelect) {
                citySelect.dataset.selected = '';
            }

            await loadCities(countryName, stateName);
        });
    }

    loadCountryCodes();
    loadCountries();
}

function initBranchImagePreview() {
    const branchImageInput = document.getElementById('branchImageInput');
    const branchImagePreview = document.getElementById('branchImagePreview');
    const branchImagePlaceholder = document.getElementById('branchImagePlaceholder');

    if (branchImageInput && branchImagePreview) {
        branchImageInput.addEventListener('change', function () {
            const file = this.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = function (event) {
                branchImagePreview.src = event.target.result;
                branchImagePreview.classList.remove('hidden');

                if (branchImagePlaceholder) {
                    branchImagePlaceholder.classList.add('hidden');
                }
            };

            reader.readAsDataURL(file);
        });
    }
}

function initBranchHoliday() {
    const holidayWrapper = document.getElementById('holidayWrapper');
    const addHolidayBtn = document.getElementById('addHolidayBtn');

    function bindRemoveHolidayButtons() {
        document.querySelectorAll('.remove-holiday-btn').forEach(function (button) {
            button.onclick = function () {
                const rows = document.querySelectorAll('.branch-holiday-row');

                if (rows.length > 1) {
                    this.closest('.branch-holiday-row').remove();
                    return;
                }

                const input = this.closest('.branch-holiday-row').querySelector('input');

                if (input) {
                    input.value = '';
                }
            };
        });
    }

    if (holidayWrapper && addHolidayBtn) {
        addHolidayBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'branch-holiday-row';

            row.innerHTML = `
                <input type="date" name="holidays[]">
                <button type="button" class="remove-holiday-btn">×</button>
            `;

            holidayWrapper.appendChild(row);
            bindRemoveHolidayButtons();
        });

        bindRemoveHolidayButtons();
    }
}

function initBranchStaffMultiselect() {
    const staffMultiselect = document.getElementById('branchStaffMultiselect');
    const staffControl = document.getElementById('branchStaffControl');
    const staffTags = document.getElementById('branchStaffTags');

    function renderStaffTags() {
        if (!staffTags) return;

        const checkedStaffInputs = document.querySelectorAll('.branch-staff-option input[type="checkbox"]:checked');

        staffTags.innerHTML = '';

        if (checkedStaffInputs.length === 0) {
            const placeholder = document.createElement('span');
            placeholder.className = 'branch-staff-placeholder';
            placeholder.id = 'branchStaffPlaceholder';
            placeholder.textContent = 'Select Staff';
            staffTags.appendChild(placeholder);
            return;
        }

        checkedStaffInputs.forEach(function (input) {
            const staffId = input.value;
            const staffName = input.dataset.name || 'Staff';

            const tag = document.createElement('span');
            tag.className = 'branch-staff-tag';
            tag.dataset.staffId = staffId;

            const text = document.createElement('span');
            text.textContent = staffName;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.textContent = '×';
            removeBtn.dataset.removeStaffId = staffId;

            tag.appendChild(text);
            tag.appendChild(removeBtn);
            staffTags.appendChild(tag);
        });
    }

    function syncStaffOptions() {
        document.querySelectorAll('.branch-staff-option').forEach(function (option) {
            const input = option.querySelector('input[type="checkbox"]');

            if (!input) return;

            if (input.checked) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
    }

    if (staffMultiselect && staffControl) {
        staffControl.addEventListener('click', function () {
            staffMultiselect.classList.toggle('open');
        });

        staffControl.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                staffMultiselect.classList.toggle('open');
            }

            if (event.key === 'Escape') {
                staffMultiselect.classList.remove('open');
            }
        });

        document.querySelectorAll('.branch-staff-option input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                syncStaffOptions();
                renderStaffTags();
            });
        });

        document.addEventListener('click', function (event) {
            if (!staffMultiselect.contains(event.target)) {
                staffMultiselect.classList.remove('open');
            }
        });

        if (staffTags) {
            staffTags.addEventListener('click', function (event) {
                const removeBtn = event.target.closest('[data-remove-staff-id]');

                if (!removeBtn) return;

                event.stopPropagation();

                const staffId = removeBtn.dataset.removeStaffId;
                const input = document.querySelector('.branch-staff-option input[type="checkbox"][value="' + staffId + '"]');

                if (input) {
                    input.checked = false;
                }

                syncStaffOptions();
                renderStaffTags();
            });
        }

        syncStaffOptions();
        renderStaffTags();
    }
}

function initBranchTable() {
    const table = document.getElementById('branchTable');
    const searchInput = document.getElementById('branchSearchInput');
    const entriesSelect = document.getElementById('branchEntriesSelect');
    const infoText = document.getElementById('branchInfoText');
    const pagination = document.getElementById('branchPagination');

    if (!table || !searchInput || !entriesSelect || !infoText || !pagination) {
        return;
    }

    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
        return !row.classList.contains('branch-empty-row');
    });

    let currentPage = 1;
    let perPage = parseInt(entriesSelect.value, 10) || 10;
    let filteredRows = allRows;

    function render() {
        perPage = parseInt(entriesSelect.value, 10) || 10;

        const keyword = searchInput.value.trim().toLowerCase();

        filteredRows = allRows.filter(function (row) {
            return row.innerText.toLowerCase().includes(keyword);
        });

        const totalRows = filteredRows.length;
        const totalPages = Math.max(Math.ceil(totalRows / perPage), 1);

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const startIndex = (currentPage - 1) * perPage;
        const endIndex = startIndex + perPage;

        tbody.innerHTML = '';

        if (totalRows === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'branch-empty-row';

            const emptyCell = document.createElement('td');
            emptyCell.colSpan = table.querySelectorAll('thead th').length;
            emptyCell.textContent = 'No branch available';

            emptyRow.appendChild(emptyCell);
            tbody.appendChild(emptyRow);
        } else {
            filteredRows.slice(startIndex, endIndex).forEach(function (row) {
                tbody.appendChild(row);
            });
        }

        const first = totalRows === 0 ? 0 : startIndex + 1;
        const last = Math.min(endIndex, totalRows);

        infoText.textContent = `Showing ${first} to ${last} of ${totalRows} entries`;

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        pagination.innerHTML = '';

        const buttons = [
            { label: 'First', page: 'first' },
            { label: 'Previous', page: 'previous' },
        ];

        for (let page = 1; page <= totalPages; page++) {
            buttons.push({
                label: String(page),
                page: page,
            });
        }

        buttons.push(
            { label: 'Next', page: 'next' },
            { label: 'Last', page: 'last' }
        );

        buttons.forEach(function (item) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = item.label;

            if (item.page === currentPage) {
                button.classList.add('active');
            }

            if ((item.page === 'first' || item.page === 'previous') && currentPage === 1) {
                button.disabled = true;
            }

            if ((item.page === 'next' || item.page === 'last') && currentPage === totalPages) {
                button.disabled = true;
            }

            button.addEventListener('click', function () {
                if (item.page === 'first') {
                    currentPage = 1;
                } else if (item.page === 'previous') {
                    currentPage = Math.max(currentPage - 1, 1);
                } else if (item.page === 'next') {
                    currentPage = Math.min(currentPage + 1, totalPages);
                } else if (item.page === 'last') {
                    currentPage = totalPages;
                } else {
                    currentPage = item.page;
                }

                render();
            });

            pagination.appendChild(button);
        });
    }

    searchInput.addEventListener('input', function () {
        currentPage = 1;
        render();
    });

    entriesSelect.addEventListener('change', function () {
        currentPage = 1;
        render();
    });

    table.querySelectorAll('thead th[data-sort]').forEach(function (th, index) {
        let asc = true;

        th.addEventListener('click', function () {
            const type = th.dataset.sort;

            allRows.sort(function (a, b) {
                const aText = a.children[index] ? a.children[index].innerText.trim().toLowerCase() : '';
                const bText = b.children[index] ? b.children[index].innerText.trim().toLowerCase() : '';

                if (type === 'number') {
                    return asc
                        ? parseFloat(aText || 0) - parseFloat(bText || 0)
                        : parseFloat(bText || 0) - parseFloat(aText || 0);
                }

                return asc
                    ? aText.localeCompare(bText)
                    : bText.localeCompare(aText);
            });

            asc = !asc;
            currentPage = 1;
            render();
        });
    });

    render();
}

function initBranchDeleteModal() {
    const modal = document.getElementById('branchDeleteModal') || document.getElementById('deleteBranchModal');
    const confirmForm = document.getElementById('branchDeleteConfirmForm') || document.getElementById('deleteBranchForm');
    const cancelButton = document.getElementById('branchDeleteCancel');

    if (!modal || !confirmForm) {
        return;
    }

    document.querySelectorAll('.branch-delete-trigger, .branch-delete-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const deleteUrl = this.dataset.deleteUrl;
            const form = this.closest('.branch-delete-form');

            if (deleteUrl) {
                confirmForm.action = deleteUrl;
            } else if (form) {
                confirmForm.action = form.action;
            } else {
                return;
            }

            modal.classList.add('active');
        });
    });

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            modal.classList.remove('active');
        });
    }

    document.querySelectorAll('[data-close-branch-delete]').forEach(function (button) {
        button.addEventListener('click', function () {
            modal.classList.remove('active');
        });
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.classList.remove('active');
        }
    });
}