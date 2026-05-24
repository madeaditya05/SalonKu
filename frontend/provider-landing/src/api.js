const defaultHeaders = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
};

export async function getCategories(url) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Kategori layanan belum bisa dimuat.');
    }

    const payload = await response.json();

    return Array.isArray(payload.data) ? payload.data : [];
}

export async function registerProvider(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: defaultHeaders,
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.message || 'Registrasi belum berhasil.');
        error.errors = data.errors || {};
        throw error;
    }

    return data;
}
