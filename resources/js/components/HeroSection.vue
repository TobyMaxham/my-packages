<script setup>
import StatsBar from './StatsBar.vue';

defineProps({
  stats: { type: Object, default: null },
  previewPackages: { type: Array, default: () => [] },
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleDateString('de-DE', { year: 'numeric', month: 'short', day: '2-digit' });
}
</script>

<template>
  <header class="pr-hero">
    <div class="container">
      <div class="pr-hero-grid">
        <div class="pr-hero-copy">
          <h1 class="pr-hero-headline">Meine Composer- &amp; npm-Pakete</h1>
          <p class="pr-hero-text">
            Ich bin Backend-Entwickler und arbeite hauptsächlich mit PHP/Laravel. Hier sind die
            Composer- und npm-Pakete, die ich einsetze oder im Blick behalte.
          </p>
          <StatsBar :stats="stats" class="pr-hero-stats" />
        </div>

        <div class="pr-hero-preview-card">
          <div
            v-for="pkg in previewPackages"
            :key="pkg.id"
            class="pr-hero-preview-row"
          >
            <div>
              <div class="pr-package-vendor">{{ pkg.vendor }}</div>
              <div class="pr-package-name">{{ pkg.name }}</div>
            </div>
            <div class="pr-hero-preview-meta">
              <span class="pr-mono">{{ pkg.php_range ?? '—' }}</span>
              <span class="pr-mono">{{ pkg.laravel_range ?? '—' }}</span>
              <span>{{ formatDate(pkg.last_release_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
