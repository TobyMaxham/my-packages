<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
  <section class="pr-notfound">
    <div class="container">
      <p class="pr-mono">404</p>
      <h1>Diese Seite gibt's hier nicht.</h1>
      <p class="pr-intro">
        Der Link ist entweder falsch oder die Seite wurde verschoben. Die Paketliste findest du
        hier:
      </p>
      <RouterLink class="btn pr-btn-primary" to="/">Zur Paketliste</RouterLink>
    </div>
  </section>
</template>
