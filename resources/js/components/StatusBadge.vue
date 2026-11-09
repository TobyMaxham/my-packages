<script setup>
import { computed } from 'vue';
import { useAuth } from '../composables/useAuth';

const props = defineProps({
  status: { type: String, required: true }, // 'compatible' | 'unclear' | 'critical' | 'abandoned'
});

const { auth } = useAuth();

const labels = {
  compatible: 'Kompatibel',
  unclear: 'Unklar',
  critical: 'Kritisch',
  abandoned: 'Eingestellt',
};

// Für Gäste bewusst neutraler formuliert - die volle Einordnung "Kritisch"
// gibt's erst nach Login, um öffentlich kein hartes Urteil über ein
// konkretes Paket/Vendor auszusprechen. Farbe/Icon bleiben gleich, damit
// das Signal "hier hinschauen" trotzdem ankommt.
const guestCriticalLabel = 'Prüfung nötig';

const icons = {
  compatible: 'bi-check-circle-fill',
  unclear: 'bi-question-circle-fill',
  critical: 'bi-exclamation-triangle-fill',
  abandoned: 'bi-archive-fill',
};

const tooltips = {
  compatible: 'Unterstützt die aktuellen Ziel-Versionen ohne bekannte Probleme.',
  unclear: 'Kompatibilität konnte nicht eindeutig geprüft werden.',
  critical: 'Kein Support für aktuelle PHP-/Laravel-Zielversionen — Handlungsbedarf.',
  abandoned: 'Als abandoned markiert und wird nicht mehr gepflegt — kein Handlungsbedarf.',
};

const label = computed(() => {
  if (props.status === 'critical' && !auth.isLoggedIn) {
    return guestCriticalLabel;
  }
  return labels[props.status] ?? props.status;
});
</script>

<template>
  <span
    class="pr-status-badge"
    :class="`pr-status-${status}`"
    :title="tooltips[status] ?? ''"
  >
    <i :class="`bi ${icons[status] ?? 'bi-dash-circle-fill'}`"></i>
    {{ label }}
  </span>
</template>
