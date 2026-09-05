<script setup>
defineProps({
  pkg: { type: Object, required: true },
});
</script>

<template>
  <article class="pr-featured-card">
    <div class="pr-package-vendor">{{ pkg.vendor }}</div>
    <h3 class="pr-package-name">{{ pkg.name }}</h3>

    <p v-if="pkg.featured_note" class="pr-featured-note">{{ pkg.featured_note }}</p>

    <div class="pr-featured-meta">
      <span class="pr-mono">PHP {{ pkg.php_range ?? '—' }}</span>
      <span class="pr-mono">Laravel {{ pkg.laravel_range ?? '—' }}</span>
    </div>

    <a
      v-if="pkg.github_url"
      :href="pkg.github_url"
      target="_blank"
      rel="noopener"
      class="pr-github-link"
    >
      <i class="bi bi-github"></i> Repository
    </a>
  </article>
</template>
