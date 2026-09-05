<script setup>
import FeaturedPackageCard from './FeaturedPackageCard.vue';

defineProps({
  packages: { type: Array, default: () => [] },
});
</script>

<template>
  <section v-if="packages.length" class="pr-featured">
    <div class="container">
      <h2 class="pr-featured-title">Meine liebsten Pakete</h2>
      <div class="pr-featured-grid">
        <FeaturedPackageCard v-for="pkg in packages" :key="pkg.id" :pkg="pkg" />
      </div>
    </div>
  </section>
</template>
