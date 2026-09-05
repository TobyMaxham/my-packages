const CSRF_COOKIE_URL = '/sanctum/csrf-cookie';

function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]*)'));
  return match ? decodeURIComponent(match[2]) : null;
}

let csrfReady = null;

export function ensureCsrfCookie() {
  if (!csrfReady) {
    csrfReady = fetch(CSRF_COOKIE_URL, { credentials: 'include' });
  }
  return csrfReady;
}

/**
 * Zentraler fetch-Wrapper.
 * - Setzt Accept: application/json global (fetch() macht das nicht von allein)
 * - Holt bei mutierenden Requests vorher den Sanctum-CSRF-Cookie und hängt
 *   den XSRF-Token als Header an
 */
export async function apiRequest(url, options = {}) {
  const method = (options.method || 'GET').toUpperCase();
  const isMutating = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

  if (isMutating) {
    await ensureCsrfCookie();
  }

  const headers = {
    Accept: 'application/json',
    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
    ...options.headers,
  };

  if (isMutating) {
    const xsrfToken = getCookie('XSRF-TOKEN');
    if (xsrfToken) headers['X-XSRF-TOKEN'] = xsrfToken;
  }

  const response = await fetch(url, {
    ...options,
    method,
    headers,
    credentials: 'include',
  });

  if (response.status === 401) {
    const error = new Error('Nicht eingeloggt');
    error.status = 401;
    throw error;
  }

  if (!response.ok) {
    let message = `Request fehlgeschlagen (${response.status})`;
    try {
      const data = await response.json();
      message = data.message || message;
    } catch (_) {
      // Response war kein JSON, Standardmeldung bleibt
    }
    const error = new Error(message);
    error.status = response.status;
    throw error;
  }

  if (response.status === 204) return null;
  return response.json();
}
