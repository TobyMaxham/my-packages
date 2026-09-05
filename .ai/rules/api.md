---
paths:
  - app/Http/Controllers/Api/VendorController.php
  - app/Http/Controllers/Api/PackageController.php
  - app/Http/Controllers/Api/StatsController.php
---

# Api

## GET /api/vendors returns plain distinct vendor name strings
Response shape is `{"data": string[]}`, not vendor objects with id/ecosystem — the FilterBar.vue dropdown binds `:value="v"` directly against each array entry, which breaks if entries are objects. This intentionally loses ecosystem-scoping on the vendor filter; acceptable for V1 since vendor names rarely collide across ecosystems.

## status/stale_months query filters are admin-only
Same public/admin boundary as PackageResource: `status` and `stale_months` filters only apply when `$request->user('sanctum') !== null`. For anonymous requests these params are silently ignored (no error, just no-op) rather than returning 403/422 — matches the FilterBar.vue UI, which hides those two controls entirely for logged-out users (see components.md).

## GET /api/stats is public and aggregate-only
Distinct from the gated per-package fields in PackageResource — SUM(downloads_total)/SUM(favers) across all packages, plus packages_count and vendors_count (COUNT DISTINCT packages.vendor_id), are not individually identifying/evaluative so they're public with no auth check. Don't merge this into PackageController or gate it — the public landing page depends on it being unauthenticated.
