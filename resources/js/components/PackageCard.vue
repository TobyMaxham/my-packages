<script setup>
import StatusBadge from './StatusBadge.vue';
import { useAuth } from '../composables/useAuth';

defineProps({
  pkg: { type: Object, required: true },
});

defineEmits(['open']);

const { auth } = useAuth();

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleDateString('de-DE', { year: 'numeric', month: 'short', day: '2-digit' });
}
</script>

<template>
  <article
    class="pr-card"
    role="button"
    tabindex="0"
    @click="$emit('open', pkg)"
    @keyup.enter="$emit('open', pkg)"
  >
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <div class="pr-package-vendor">{{ pkg.vendor }}</div>
        <h3 class="pr-package-name">{{ pkg.name }}</h3>
      </div>
      <StatusBadge v-if="auth.isLoggedIn" :status="pkg.status" />
    </div>

    <div class="pr-tag-row" v-if="pkg.tags?.length">
      <span v-for="tag in pkg.tags" :key="tag" class="pr-tag">{{ tag }}</span>
    </div>

    <dl class="pr-meta-grid">
      <div>
        <dt>Version</dt>
        <dd class="pr-mono">{{ pkg.latest_version ?? '—' }}</dd>
      </div>
      <div>
        <dt>PHP</dt>
        <dd class="pr-mono">{{ pkg.php_range ?? '—' }}</dd>
      </div>
      <div>
        <dt>Laravel</dt>
        <dd class="pr-mono">{{ pkg.laravel_range ?? '—' }}</dd>
      </div>
      <div>
        <dt>Letztes Release</dt>
        <dd>{{ formatDate(pkg.last_release_at) }}</dd>
      </div>
    </dl>

    <a
      v-if="pkg.github_url"
      :href="pkg.github_url"
      target="_blank"
      rel="noopener"
      class="pr-github-link"
      @click.stop
    >
      <i class="bi bi-github"></i> Repository
    </a>
  </article>
</template>
