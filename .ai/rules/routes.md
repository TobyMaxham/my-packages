---
paths:
  - routes/web.php
---

# Routes

## Sanctum stateful auth requires a matching Origin/Referer, not just the session cookie
EnsureFrontendRequestsAreStateful (enabled via `$middleware->statefulApi()`) only treats a request as "from the frontend" if its Origin or Referer header matches `config('sanctum.stateful')`. A real browser on the same origin sends this automatically, but curl/Postman testing needs an explicit `-H "Origin: http://127.0.0.1:8000"` (matching APP_URL's host:port) or `/api/user` and the auth-gated PackageResource fields will silently look like "not logged in" (401 / public-only fields) even with a valid session cookie.

Also: `$request->session()->regenerate()` (called on login) rotates the XSRF-TOKEN cookie. The frontend's http.js already re-reads `document.cookie` per-request so this is transparent in the browser, but a scripted/manual test must re-read the cookie jar after login before the next mutating request, or it gets a 419.
