---
paths:
  - resources/js/views/PackageExplorer.vue
---

# Views

## Public site has no branding and a fixed 4-part structure
This is explicitly not a branded product — no logo, no invented product name ("Paketradar" was removed), anywhere on the page. The public page ('/') is exactly, in order: (1) plain header row with H1 "Composer- & npm-Pakete" + a discreet Login/Logout link, nothing else, (2) one short factual intro paragraph, (3) a neutral stats counter row (packages/vendors/downloads/favers, from GET /api/stats, no evaluative framing), (4) the package list/filters. No hero section, no CTA button, no terminal mockup, no separate "landing" vs "explorer" page — AppNavbar.vue was deleted, App.vue is just `<router-view>`. Don't reintroduce a persistent site-wide navbar or brand mark without the user asking again.
