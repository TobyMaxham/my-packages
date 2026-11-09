---
paths:
  - app/Http/Resources/PackageResource.php
---

# Resources

## PackageResource field names follow the Vue frontend contract, not the DB columns
The delivered resources/js Vue SPA (package-compatibility-dashboard.zip) expects specific JSON keys: `vendor` (string, not an object), `github_url` (maps DB `repository_url`), `php_range` (maps `php_constraint`), `laravel_range` (maps `framework_constraint`). Keep aliasing these in PackageResource::toArray() rather than renaming the underlying model/DB columns or changing the frontend — the frontend is a fixed deliverable.

Public fields are a neutral fact list only: id, name, vendor, ecosystem, tags, description, github_url, latest_version, last_release_at, php_range, laravel_range. `status`, `abandoned`, `downloads_total`, `favers`, `last_checked_at` are ALL gated behind `$request->user('sanctum') !== null` via `$this->when(...)` — this is deliberate: the public site is a plain package directory with no evaluative judgment, the compatible/unclear/critical verdict (and anything else "handlungsorientiert") is admin-only. Gated fields must be genuinely absent from the JSON for anonymous requests, not just hidden client-side — don't move any of them back to the always-visible block. Auth check works without `auth:sanctum` middleware since the guard resolves on demand once `$middleware->statefulApi()` is set in bootstrap/app.php.

## outdated_dependencies is admin-only and a hint, not a status
`outdated_dependencies` (tracked composer deps whose constraint excludes their stored latest_version, computed in FetchPackageDetailsJob) is gated via `$this->when($isAuthenticated, ...)` like `status` — it is evaluative, so it must be absent for anonymous requests. It is deliberately a separate hint and must NOT change PackageStatus: the status answers "runs on the target PHP/Laravel versions?", this answers "pins an old release of one of our own packages?". The frontend shows it only inside `auth.isLoggedIn` blocks (PackageTable, PackageCard, PackageDetailModal).
