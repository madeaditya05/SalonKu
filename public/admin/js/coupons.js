document.addEventListener('DOMContentLoaded', function () {
    const productTypeSelect = document.getElementById('productTypeSelect');
    const productField = document.getElementById('couponProductField');
    const productLabel = document.getElementById('couponProductLabel');
    const productSelector = document.getElementById('couponProductSelector');
    const productControl = document.getElementById('couponProductControl');
    const productSearch = document.getElementById('couponProductSearch');
    const productDropdown = document.getElementById('couponProductDropdown');
    const selectedTags = document.getElementById('couponSelectedTags');
    const hiddenInputs = document.getElementById('couponProductHiddenInputs');
    const masterDataScript = document.getElementById('couponMasterData');

    const couponTypeSelect = document.getElementById('couponTypeSelect');
    const couponValueSuffix = document.getElementById('couponValueSuffix');

    if (!productTypeSelect || !productField || !masterDataScript) {
        return;
    }

    const masterData = JSON.parse(masterDataScript.textContent || '{}');

    let selectedIds = [];

    try {
        selectedIds = JSON.parse(productField.getAttribute('data-selected') || '[]').map(Number);
    } catch (error) {
        selectedIds = [];
    }

    function getCurrentType() {
        return productTypeSelect.value || 'all';
    }

    function getTypeLabel(type) {
        const labels = {
            service: 'Service',
            category: 'Category',
            subcategory: 'Sub Category',
        };

        return labels[type] || '';
    }

    function getOptions(type) {
        return masterData[type] || [];
    }

    function isSelected(id) {
        return selectedIds.includes(Number(id));
    }

    function render() {
        const type = getCurrentType();

        if (type === 'all') {
            productField.style.display = 'none';
            selectedIds = [];
            renderSelectedTags();
            renderHiddenInputs();
            renderDropdown();
            return;
        }

        productField.style.display = 'block';
        productLabel.textContent = getTypeLabel(type);

        const validIds = getOptions(type).map(function (item) {
            return Number(item.id);
        });

        selectedIds = selectedIds.filter(function (id) {
            return validIds.includes(Number(id));
        });

        productSearch.placeholder = 'Select ' + getTypeLabel(type);

        renderSelectedTags();
        renderHiddenInputs();
        renderDropdown();
    }

    function renderSelectedTags() {
        selectedTags.innerHTML = '';

        const type = getCurrentType();
        const options = getOptions(type);

        selectedIds.forEach(function (id) {
            const option = options.find(function (item) {
                return Number(item.id) === Number(id);
            });

            if (!option) {
                return;
            }

            const tag = document.createElement('span');
            tag.className = 'coupon-tag';
            tag.innerHTML = `
                ${option.label}
                <button type="button" data-remove-id="${option.id}">×</button>
            `;

            selectedTags.appendChild(tag);
        });

        selectedTags.querySelectorAll('[data-remove-id]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();

                const id = Number(button.getAttribute('data-remove-id'));

                selectedIds = selectedIds.filter(function (selectedId) {
                    return Number(selectedId) !== id;
                });

                renderSelectedTags();
                renderHiddenInputs();
                renderDropdown();
            });
        });
    }

    function renderHiddenInputs() {
        hiddenInputs.innerHTML = '';

        selectedIds.forEach(function (id) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_ids[]';
            input.value = id;

            hiddenInputs.appendChild(input);
        });
    }

    function renderDropdown() {
        const type = getCurrentType();
        const options = getOptions(type);
        const keyword = productSearch.value.toLowerCase().trim();

        productDropdown.innerHTML = '';

        if (type === 'all') {
            return;
        }

        const filteredOptions = options.filter(function (item) {
            return item.label.toLowerCase().includes(keyword);
        });

        if (filteredOptions.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'coupon-option empty';
            empty.textContent = 'No data found';

            productDropdown.appendChild(empty);
            return;
        }

        filteredOptions.forEach(function (item) {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'coupon-option';

            if (isSelected(item.id)) {
                option.classList.add('selected');
            }

            option.textContent = item.label;

            option.addEventListener('click', function () {
                const id = Number(item.id);

                if (isSelected(id)) {
                    selectedIds = selectedIds.filter(function (selectedId) {
                        return Number(selectedId) !== id;
                    });
                } else {
                    selectedIds.push(id);
                }

                productSearch.value = '';
                renderSelectedTags();
                renderHiddenInputs();
                renderDropdown();
                productSearch.focus();
            });

            productDropdown.appendChild(option);
        });
    }

    function openDropdown() {
        if (getCurrentType() === 'all') {
            return;
        }

        productSelector.classList.add('open');
        renderDropdown();
    }

    function closeDropdown() {
        productSelector.classList.remove('open');
    }

    productTypeSelect.addEventListener('change', function () {
        selectedIds = [];
        productSearch.value = '';
        render();
    });

    productControl.addEventListener('click', function () {
        productSearch.focus();
        openDropdown();
    });

    productSearch.addEventListener('focus', function () {
        openDropdown();
    });

    productSearch.addEventListener('input', function () {
        openDropdown();
        renderDropdown();
    });

    document.addEventListener('click', function (event) {
        if (!productSelector.contains(event.target)) {
            closeDropdown();
        }
    });

    function updateCouponSuffix() {
        if (!couponTypeSelect || !couponValueSuffix) {
            return;
        }

        if (couponTypeSelect.value === 'fixed') {
            couponValueSuffix.textContent = '$';
        } else {
            couponValueSuffix.textContent = '%';
        }
    }

    if (couponTypeSelect) {
        couponTypeSelect.addEventListener('change', updateCouponSuffix);
        updateCouponSuffix();
    }

    render();
});