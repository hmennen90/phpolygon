import type { ApiResponse } from '@/types';

const BASE_URL = '/api/editor';

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly body?: unknown,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

async function request<T>(
    method: 'GET' | 'POST' | 'PUT' | 'DELETE',
    path: string,
    body?: unknown,
): Promise<T> {
    const url = `${BASE_URL}${path}`;

    const headers: Record<string, string> = {
        Accept: 'application/json',
    };

    const init: RequestInit = { method, headers };

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
        init.body = JSON.stringify(body);
    }

    const res = await fetch(url, init);

    if (!res.ok) {
        let errBody: unknown;
        try {
            errBody = await res.json();
        } catch {
            /* ignore parse failure */
        }
        const msg =
            (errBody as ApiResponse)?.error ?? `HTTP ${res.status} ${res.statusText}`;
        throw new ApiError(msg, res.status, errBody);
    }

    const json: ApiResponse<T> = await res.json();

    if (!json.success) {
        throw new ApiError(json.error ?? 'Unknown API error', res.status, json);
    }

    return json.data;
}

export function get<T>(path: string): Promise<T> {
    return request<T>('GET', path);
}

export function post<T>(path: string, body?: unknown): Promise<T> {
    return request<T>('POST', path, body);
}

export function put<T>(path: string, body?: unknown): Promise<T> {
    return request<T>('PUT', path, body);
}

export function del<T>(path: string): Promise<T> {
    return request<T>('DELETE', path);
}
