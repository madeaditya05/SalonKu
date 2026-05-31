const normalizeUrl = (url) => String(url || '').replace(/\/$/, '');

const currentHostname = () => window.location.hostname || '127.0.0.1';

const loopbackHosts = new Set(['127.0.0.1', 'localhost', '::1']);

const isLoopbackHost = (hostname) => loopbackHosts.has(String(hostname || '').toLowerCase());

const localizeLoopbackUrl = (url) => {
    const normalized = normalizeUrl(url);

    try {
        const parsed = new URL(normalized);
        const hostname = currentHostname();

        if (!isLoopbackHost(hostname) && isLoopbackHost(parsed.hostname)) {
            parsed.hostname = hostname;
        }

        return normalizeUrl(parsed.toString());
    } catch {
        return normalized;
    }
};

const localBackendUrl = () => {
    const hostname = currentHostname();

    return `http://${hostname}:8000`;
};

const localFrontendUrl = (port) => {
    const hostname = currentHostname();

    return `http://${hostname}:${port}`;
};

const backendUrl = () => localizeLoopbackUrl(import.meta.env.VITE_BACKEND_URL || localBackendUrl());
const apiBaseUrl = () => localizeLoopbackUrl(import.meta.env.VITE_API_BASE_URL || `${backendUrl()}/api`);
const apiFallbackUrls = () => [
    apiBaseUrl(),
    `${localBackendUrl()}/api`,
    'http://127.0.0.1:8000/api',
    'http://localhost:8000/api',
    'http://127.0.0.1:8001/api',
].map(normalizeUrl).filter((url, index, urls) => url && urls.indexOf(url) === index);
const jsonHeaders = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
};

function apiRequestUrls(path, params = {}) {
    const cleanPath = path.startsWith('/') ? path : `/${path}`;

    return apiFallbackUrls().map((baseUrl) => {
        const url = new URL(`${baseUrl}${cleanPath}`);

        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });

        return url.toString();
    });
}

async function fetchApi(path, options = {}, params = {}) {
    let lastError = null;

    for (const url of apiRequestUrls(path, params)) {
        try {
            return await fetch(url, options);
        } catch (error) {
            lastError = error;
        }
    }

    const error = new Error(`Could not connect to the Laravel API. Make sure the backend is running at ${backendUrl()}.`);
    error.cause = lastError;
    throw error;
}

async function fetchJson(path, params = {}) {
    const response = await fetchApi(path, {
        headers: {
            Accept: 'application/json',
        },
    }, params);

    if (!response.ok) {
        throw new Error('Data could not be loaded.');
    }

    return response.json();
}

function collection(payload) {
    return Array.isArray(payload?.data) ? payload.data : [];
}

export function resolveAssetUrl(path) {
    if (!path) {
        return '';
    }

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    const cleanPath = String(path).replace(/^\/+/, '');

    return cleanPath.startsWith('storage/')
        ? `${backendUrl()}/${cleanPath}`
        : `${backendUrl()}/storage/${cleanPath}`;
}

export async function getCategories(params = {}) {
    return collection(await fetchJson('/categories', params));
}

export async function getLocations(params = {}) {
    return collection(await fetchJson('/locations', params));
}

export async function getBranches(params = {}) {
    return collection(await fetchJson('/branches', params));
}

export async function getBranchServices(branchId, params = {}) {
    return collection(await fetchJson(`/branches/${branchId}/services`, params));
}

export async function getBranchDetail(branchId, params = {}) {
    const payload = await fetchJson(`/branches/${branchId}`, params);

    return payload.data || null;
}

export async function getBranchStaff(branchId, params = {}) {
    return collection(await fetchJson(`/branches/${branchId}/staff`, params));
}

export async function getServices(params = {}) {
    return collection(await fetchJson('/services', params));
}

export async function getProviders(params = {}) {
    return collection(await fetchJson('/providers', params));
}

export async function getCoupons(params = {}) {
    return collection(await fetchJson('/coupons', params));
}

async function sendJson(path, payload = {}, token = '', method = 'POST') {
    const options = {
        method,
        headers: {
            ...jsonHeaders,
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
    };

    if (method !== 'GET') {
        options.body = JSON.stringify(payload);
    }

    const response = await fetchApi(path, options);

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.message || 'Request failed.');
        error.errors = data.errors || {};
        throw error;
    }

    return data;
}

async function getJsonAuth(path, params = {}, token = '') {
    const response = await fetchApi(path, {
        headers: {
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
    }, params);

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.message || 'Data could not be loaded.');
        error.errors = data.errors || {};
        throw error;
    }

    return data;
}

export async function loginCustomer(payload) {
    return sendJson('/auth/login', {
        ...payload,
        role: 'customer',
    });
}

export async function registerCustomer(payload) {
    return sendJson('/auth/register/customer', payload);
}

export async function logoutCustomer(token) {
    return sendJson('/auth/logout', {}, token);
}

export async function getCustomerBookings(token, params = {}) {
    return collection(await getJsonAuth('/customer/bookings', params, token));
}

export async function getCustomerBooking(token, bookingId) {
    const payload = await getJsonAuth(`/customer/bookings/${bookingId}`, {}, token);

    return payload.data || null;
}

export async function checkBookingAvailability(payload) {
    const data = await sendJson('/customer/booking/check-availability', payload);

    return data.data || data;
}

export async function validateCoupon(payload) {
    const data = await sendJson('/coupons/validate', payload);

    return data.data || data;
}

export async function createCustomerBooking(token, payload) {
    return sendJson('/customer/bookings', payload, token);
}

export async function createBookingPayment(token, bookingId, payload) {
    return sendJson(`/customer/bookings/${bookingId}/payment/charge`, payload, token);
}

export async function refreshBookingPaymentStatus(token, bookingId) {
    const payload = await getJsonAuth(`/customer/bookings/${bookingId}/payment/status`, {}, token);

    return payload.data || null;
}

export async function cancelCustomerBooking(token, bookingId) {
    return sendJson(`/customer/bookings/${bookingId}/cancel`, {}, token, 'PATCH');
}

export async function rescheduleCustomerBooking(token, bookingId, payload) {
    return sendJson(`/customer/bookings/${bookingId}/reschedule`, payload, token, 'PATCH');
}

export const links = {
    providerFrontend: localizeLoopbackUrl(import.meta.env.VITE_PROVIDER_FRONTEND_URL || localFrontendUrl(5173)),
    customerApp: localizeLoopbackUrl(import.meta.env.VITE_CUSTOMER_APP_URL || localFrontendUrl(5174)),
};
