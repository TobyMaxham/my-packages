<script setup>
const props = defineProps({
  meta: { type: Object, default: null },
});
const emit = defineEmits(['change']);

function goTo(page) {
  if (page < 1 || (props.meta?.last_page && page > props.meta.last_page)) return;
  emit('change', page);
}
</script>

<template>
  <nav v-if="meta && meta.last_page > 1" aria-label="Seiten" class="pr-pagination-wrap">
    <ul class="pagination pr-pagination mb-0">
      <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
        <button type="button" class="page-link" @click="goTo(meta.current_page - 1)">Zurück</button>
      </li>
      <li class="page-item pr-page-status">
        Seite {{ meta.current_page }} von {{ meta.last_page }}
      </li>
      <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
        <button type="button" class="page-link" @click="goTo(meta.current_page + 1)">Weiter</button>
      </li>
    </ul>
  </nav>
</template>
