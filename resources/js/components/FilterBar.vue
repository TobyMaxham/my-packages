<script setup>
import { ref, watch } from 'vue';
import { useAuth } from '../composables/useAuth';

defineProps({
  vendors: { type: Array, default: () => [] },
});

const { auth } = useAuth();

const emit = defineEmits(['update:filters']);

const search = ref('');
const ecosystem = ref('');
const vendor = ref('');
const status = ref('');
const staleMonths = ref('');

let debounceHandle = null;

function emitFilters() {
  emit('update:filters', {
    search: search.value,
    ecosystem: ecosystem.value,
    vendor: vendor.value,
    status: status.value,
    stale_months: staleMonths.value,
  });
}

watch(search, () => {
  clearTimeout(debounceHandle);
  debounceHandle = setTimeout(emitFilters, 400);
});

watch([ecosystem, vendor, status, staleMonths], emitFilters);
</script>

<template>
  <div class="pr-filter-bar">
    <div class="pr-search-field">
      <i class="bi bi-search"></i>
      <input
        v-model="search"
        type="text"
        class="form-control"
        placeholder="Name, Vendor oder Tag suchen …"
      />
    </div>

    <select v-model="ecosystem" class="form-select pr-select">
      <option value="">Alle Ecosysteme</option>
      <option value="composer">Composer</option>
      <option value="npm">npm</option>
    </select>

    <select v-model="vendor" class="form-select pr-select">
      <option value="">Alle Vendors</option>
      <option v-for="v in vendors" :key="v" :value="v">{{ v }}</option>
    </select>

    <select v-if="auth.isLoggedIn" v-model="status" class="form-select pr-select">
      <option value="">Alle Status</option>
      <option value="compatible">Kompatibel</option>
      <option value="unclear">Unklar</option>
      <option value="critical">Kritisch</option>
      <option value="abandoned">Eingestellt</option>
    </select>

    <select v-if="auth.isLoggedIn" v-model="staleMonths" class="form-select pr-select">
      <option value="">Egal wie alt</option>
      <option value="6">Kein Release seit 6 Monaten</option>
      <option value="12">Kein Release seit 12 Monaten</option>
      <option value="24">Kein Release seit 24 Monaten</option>
    </select>
  </div>
</template>
