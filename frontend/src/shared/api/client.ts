import { getBackendApiUrl } from './config';

export type ApiRequestOptions = RequestInit & {
  path: `/${string}`;
};

export function createApiUrl(path: `/${string}`): string {
  return `${getBackendApiUrl()}${path}`;
}

export function apiRequest({ path, ...init }: ApiRequestOptions): Promise<Response> {
  return fetch(createApiUrl(path), {
    credentials: 'include',
    ...init,
  });
}
