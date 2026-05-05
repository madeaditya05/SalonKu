document.addEventListener('DOMContentLoaded', function () {
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