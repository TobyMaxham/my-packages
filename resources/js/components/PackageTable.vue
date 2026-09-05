<script setup>
import StatusBadge from './StatusBadge.vue';
import { useAuth } from '../composables/useAuth';

defineProps({
  packages: { type: Array, required: true },
});
defineEmits(['open']);

const { auth } = useAuth();

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleDateString('de-DE', { year: 'numeric', month: 'short', day: '2-digit' });
}
</script>

<template>
  <table class="table pr-table align-middle mb-0">
    <thead>
      <tr>
        <th>Paket</th>
        <th>Tags</th>
        <th>Version</th>
        <th>PHP</th>
        <th>Laravel</th>
        <th>Letztes Release</th>
        <th v-if="auth.isLoggedIn">Status</th>
        <th class="text-end">Repo</th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="pkg in packages"
        :key="pkg.id"
        class="pr-table-row"
        role="button"
        @click="$emit('open', pkg)"
      >
        <td>
          <div class="pr-package-vendor">{{ pkg.vendor }}</div>
          <div class="pr-package-name">{{ pkg.name }}</div>
        </td>
        <td>
          <span v-for="tag in pkg.tags ?? []" :key="tag" class="pr-tag">{{ tag }}</span>
        </td>
        <td class="pr-mono">{{ pkg.latest_version ?? '—' }}</td>
        <td class="pr-mono">{{ pkg.php_range ?? '—' }}</td>
        <td class="pr-mono">{{ pkg.laravel_range ?? '—' }}</td>
        <td>{{ formatDate(pkg.last_release_at) }}</td>
        <td v-if="auth.isLoggedIn"><StatusBadge :status="pkg.status" /></td>
        <td class="text-end">
          <a
            v-if="pkg.github_url"
            :href="pkg.github_url"
            target="_blank"
            rel="noopener"
            class="pr-github-link"
            @click.stop
          >
            <i class="bi bi-github"></i>
          </a>
        </td>
      </tr>
    </tbody>
  </table>
</template>
