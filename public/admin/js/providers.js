document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-provider-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const providerId = button.dataset.providerToggle;
            const branchRow = document.getElementById('providerBranches-' + providerId);
            const parentRow = button.closest('.provider-parent-row');

            if (!branchRow || button.disabled) {
                return;
            }

            const shouldOpen = branchRow.hidden;

            branchRow.hidden = !shouldOpen;
            button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

            if (parentRow) {
                parentRow.classList.toggle('is-expanded', shouldOpen);
            }
        });
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-delete-form]');

        if (!form) {
            return;
        }

        const confirmed = confirm('Yakin ingin menghapus provider ini?');

        if (!confirmed) {
            event.preventDefault();
        }
    });
});
