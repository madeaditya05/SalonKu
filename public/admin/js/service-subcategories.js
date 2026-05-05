document.addEventListener('DOMContentLoaded', function () {
    /*
    |--------------------------------------------------------------------------
    | Modal
    |--------------------------------------------------------------------------
    */

    function getModals() {
        return document.querySelectorAll('.subcategory-modal');
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            console.error('Modal tidak ditemukan:', modalId);
            return;
        }

        closeAllModals();

        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.remove('show');

        const openedModal = document.querySelector('.subcategory-modal.show');

        if (!openedModal) {
            document.body.classList.remove('modal-open');
        }
    }

    function closeAllModals() {
        getModals().forEach(function (modal) {
            modal.classList.remove('show');
        });

        document.body.classList.remove('modal-open');
    }

    document.addEventListener('click', function (event) {
        const openButton = event.target.closest('[data-modal-open]');
        const closeButton = event.target.closest('[data-modal-close]');

        if (openButton) {
            event.preventDefault();

            const modalId = openButton.getAttribute('data-modal-open');

            if (modalId) {
                openModal(modalId);
            }

            return;
        }

        if (closeButton) {
            event.preventDefault();

            const modal = closeButton.closest('.subcategory-modal');
            closeModal(modal);
        }
    });

    getModals().forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Slug Generator
    |--------------------------------------------------------------------------
    */

    document.addEventListener('input', function (event) {
        const source = event.target.closest('[data-slug-source]');

        if (!source) {
            return;
        }

        const form = source.closest('form');
        const target = form ? form.querySelector('[data-slug-target]') : null;

        if (!target) {
            return;
        }

        target.value = generateSlug(source.value);
    });

    function generateSlug(value) {
        return String(value || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Preview
    |--------------------------------------------------------------------------
    */

    document.addEventListener('change', function (event) {
        const input = event.target.closest('[data-file-input]');

        if (!input) {
            return;
        }

        handleFilePreview(input);
    });

    document.querySelectorAll('.upload-field').forEach(function (uploadBox) {
        const input = uploadBox.querySelector('[data-file-input]');

        if (!input) {
            return;
        }

        uploadBox.addEventListener('dragover', function (event) {
            event.preventDefault();
            uploadBox.classList.add('drag-over');
        });

        uploadBox.addEventListener('dragleave', function () {
            uploadBox.classList.remove('drag-over');
        });

        uploadBox.addEventListener('drop', function (event) {
            event.preventDefault();
            uploadBox.classList.remove('drag-over');

            const files = event.dataTransfer.files;

            if (!files || files.length === 0) {
                return;
            }

            input.files = files;
            handleFilePreview(input);
        });
    });

    function handleFilePreview(input) {
        const file = input.files && input.files[0];
        const uploadBox = input.closest('.upload-field');

        if (!file || !uploadBox) {
            return;
        }

        const preview = uploadBox.querySelector('.upload-preview');

        if (!preview) {
            return;
        }

        const isImage = file.type.startsWith('image/') || file.name.toLowerCase().endsWith('.svg');

        if (!isImage) {
            alert('File harus berupa gambar.');
            input.value = '';
            return;
        }

        const maxSize = 2 * 1024 * 1024;

        if (file.size > maxSize) {
            alert('Ukuran file maksimal 2MB.');
            input.value = '';
            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {
            preview.src = event.target.result;
            uploadBox.classList.add('has-preview');
        };

        reader.readAsDataURL(file);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Button Safety
    |--------------------------------------------------------------------------
    */

    document.addEventListener('click', function (event) {
        const deleteButton = event.target.closest('.delete-confirm-btn');

        if (!deleteButton) {
            return;
        }

        const form = deleteButton.closest('form');

        if (!form) {
            return;
        }

        event.preventDefault();

        deleteButton.disabled = true;
        deleteButton.innerText = 'Deleting...';

        form.submit();
    });
});