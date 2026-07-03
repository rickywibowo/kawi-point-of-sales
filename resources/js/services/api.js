const configuredApiBaseUrl = import.meta.env.VITE_API_BASE_URL;
const API_BASE_URL = configuredApiBaseUrl && configuredApiBaseUrl !== 'http://localhost/api'
    ? configuredApiBaseUrl
    : '/api';

export class ApiError extends Error {
    constructor(message, status, payload = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.payload = payload;
    }
}

export function getApiToken() {
    return localStorage.getItem('kawi_api_token');
}

export function setApiToken(token) {
    if (token) {
        localStorage.setItem('kawi_api_token', token);

        return;
    }

    localStorage.removeItem('kawi_api_token');
}

export function getTenantContext() {
    return {
        businessId: localStorage.getItem('kawi_business_id'),
        branchId: localStorage.getItem('kawi_branch_id'),
    };
}

export function setTenantContext({ businessId, branchId }) {
    if (businessId) {
        localStorage.setItem('kawi_business_id', businessId);
    }

    if (branchId) {
        localStorage.setItem('kawi_branch_id', branchId);
    }
}

export async function apiGet(path) {
    return apiRequest(path);
}

export async function apiPost(path, body) {
    return apiRequest(path, {
        method: 'POST',
        body: JSON.stringify(body),
    });
}

async function apiRequest(path, options = {}) {
    const token = getApiToken();
    const { businessId, branchId } = getTenantContext();
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(options.headers ?? {}),
    };

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    if (businessId) {
        headers['X-Business-Id'] = businessId;
    }

    if (branchId) {
        headers['X-Branch-Id'] = branchId;
    }

    const response = await fetch(`${API_BASE_URL}${path}`, {
        ...options,
        headers,
    });
    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(payload?.message ?? 'API request failed.', response.status, payload);
    }

    return payload;
}
