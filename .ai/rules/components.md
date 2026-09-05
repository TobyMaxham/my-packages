---
paths:
  - resources/js/components/StatusBadge.vue
---

# Components

## Status badge only renders for logged-in users
`status` is no longer a public API field (see resources.md) — anonymous responses omit the key entirely. `pkg.status` is therefore `undefined` for guests, so every place that renders `<StatusBadge>` (PackageTable, PackageCard, PackageDetailModal) wraps it in `v-if="auth.isLoggedIn"`. Don't remove that guard "to simplify" — the public package list is meant to be a neutral fact list (name/vendor/description/tags/github/version/release date/php+laravel range) with zero evaluative judgment; the compatible/unclear/critical verdict is admin-only by design, per explicit product decision.
