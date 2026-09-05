<script setup>
defineProps({
  stats: { type: Object, default: null },
});

function formatNumber(value) {
  return (value ?? 0).toLocaleString('de-DE');
}
</script>

<template>
  <dl v-if="stats" class="pr-stats">
    <div class="pr-stat">
      <dt class="pr-stat-label">Pakete</dt>
      <dd class="pr-stat-value">{{ formatNumber(stats.packages_count) }}</dd>
    </div>
    <div class="pr-stat">
      <dt class="pr-stat-label">Vendors</dt>
      <dd class="pr-stat-value">{{ formatNumber(stats.vendors_count) }}</dd>
    </div>
    <div class="pr-stat">
      <dt class="pr-stat-label">Downloads gesamt</dt>
      <dd class="pr-stat-value">{{ formatNumber(stats.downloads_total) }}</dd>
    </div>
    <div class="pr-stat">
      <dt class="pr-stat-label">GitHub Stars gesamt</dt>
      <dd class="pr-stat-value">{{ formatNumber(stats.favers_total) }}</dd>
    </div>
  </dl>
</template>
