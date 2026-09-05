<script setup>
import { ref } from 'vue';
import { Modal } from 'bootstrap';
import StatusBadge from './StatusBadge.vue';
import { useAuth } from '../composables/useAuth';

const { auth } = useAuth();

const modalEl = ref(null);
const pkg = ref(null);
let modalInstance = null;

// Lazy init beim Öffnen statt in onMounted -> vermeidet Race-Condition mit
// Vues Template-Compilation (siehe bekannte Bootstrap-Modal-Timing-Falle)
function open(selectedPkg) {
  pkg.value = selectedPkg;
  modalInstance = Modal.getOrCreateInstance(modalEl.value);
  modalInstance.show();
}

function close() {
  modalInstance?.hide();
}

defineExpose({ open, close });

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleDateString('de-DE', { year: 'numeric', month: 'short', day: '2-digit' });
}
</script>

<template>
  <div ref="modalEl" class="modal fade pr-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content" v-if="pkg">
        <div class="modal-header">
          <div>
            <div class="pr-package-vendor">{{ pkg.vendor }}</div>
            <h2 class="modal-title pr-package-name">{{ pkg.name }}</h2>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <div class="modal-body">
          <div v-if="auth.isLoggedIn || pkg.tags?.length" class="d-flex align-items-center flex-wrap gap-2 mb-3">
            <StatusBadge v-if="auth.isLoggedIn" :status="pkg.status" />
            <span v-for="tag in pkg.tags ?? []" :key="tag" class="pr-tag">{{ tag }}</span>
          </div>

          <dl class="pr-detail-grid">
            <div>
              <dt>Ecosystem</dt>
              <dd>{{ pkg.ecosystem }}</dd>
            </div>
            <div>
              <dt>Aktuelle Version</dt>
              <dd class="pr-mono">{{ pkg.latest_version ?? '—' }}</dd>
            </div>
            <div>
              <dt>Unterstützte PHP-Range</dt>
              <dd class="pr-mono">{{ pkg.php_range ?? '—' }}</dd>
            </div>
            <div>
              <dt>Unterstützte Laravel-Range</dt>
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
          >
            <i class="bi bi-github"></i> Auf GitHub ansehen
          </a>

          <div v-if="auth.isLoggedIn" class="pr-advanced-section">
            <h3 class="pr-advanced-title">Erweiterte Infos</h3>
            <dl class="pr-detail-grid">
              <div>
                <dt>Downloads gesamt</dt>
                <dd class="pr-mono">{{ pkg.downloads_total?.toLocaleString('de-DE') ?? '—' }}</dd>
              </div>
              <div>
                <dt>Favoriten</dt>
                <dd class="pr-mono">{{ pkg.favers?.toLocaleString('de-DE') ?? '—' }}</dd>
              </div>
              <div>
                <dt>Abandoned</dt>
                <dd>{{ pkg.abandoned ? 'Ja' : 'Nein' }}</dd>
              </div>
              <div>
                <dt>Zuletzt geprüft</dt>
                <dd>{{ formatDate(pkg.last_checked_at) }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
