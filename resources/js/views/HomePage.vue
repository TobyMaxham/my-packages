<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { usePackages } from '../composables/usePackages';
import { useStats } from '../composables/useStats';
import { useAuth } from '../composables/useAuth';
import HeroSection from '../components/HeroSection.vue';
import FeaturedSection from '../components/FeaturedSection.vue';
import FilterBar from '../components/FilterBar.vue';
import PackageTable from '../components/PackageTable.vue';
import PackageCard from '../components/PackageCard.vue';
import PaginationBar from '../components/PaginationBar.vue';
import PackageDetailModal from '../components/PackageDetailModal.vue';

const { state, fetchPackages, fetchVendors } = usePackages();
const { state: stats, fetchStats } = useStats();
const { state: heroPreview, fetchPackages: fetchHeroPreview } = usePackages();
const { state: featured, fetchPackages: fetchFeatured } = usePackages();
const { auth, logout } = useAuth();
const router = useRouter();

const previewPackages = computed(() => heroPreview.items.slice(0, 3));

const filters = reactive({
  search: '',
  ecosystem: '',
  vendor: '',
  status: '',
  stale_months: '',
});
const page = ref(1);
const modalRef = ref(null);

function loadPackages() {
  fetchPackages({ ...filters, page: page.value });
}

function onFiltersChange(newFilters) {
  Object.assign(filters, newFilters);
  page.value = 1;
  loadPackages();
}

function onPageChange(newPage) {
  page.value = newPage;
  loadPackages();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function openDetail(pkg) {
  modalRef.value?.open(pkg);
}

async function handleLogout() {
  await logout();
  router.push('/login');
}

onMounted(() => {
  fetchStats();
  fetchHeroPreview();
  fetchFeatured({ featured: 1 });
  fetchVendors();
  loadPackages();
});
</script>

<template>
  <div class="pr-utility-bar">
    <div class="container pr-utility-bar-row">
      <template v-if="auth.isLoggedIn">
        <a class="pr-login-link" href="/admin/vendors">Admin</a>
        <button type="button" class="pr-login-link" @click="handleLogout">Logout</button>
      </template>
      <RouterLink v-else class="pr-login-link" to="/login">Login</RouterLink>
    </div>
  </div>

  <HeroSection :stats="stats.data" :preview-packages="previewPackages" />

  <FeaturedSection :packages="featured.items" />

  <section class="pr-explorer">
    <div class="container">
      <FilterBar :vendors="state.vendors" @update:filters="onFiltersChange" />

      <p v-if="state.error" class="pr-error">{{ state.error }}</p>

      <div v-else-if="state.loading" class="pr-loading">Lade Pakete …</div>

      <template v-else>
        <p v-if="state.items.length === 0" class="pr-empty">
          Keine Pakete gefunden. Passe deine Filter an.
        </p>

        <template v-else>
          <div class="d-none d-md-block">
            <PackageTable :packages="state.items" @open="openDetail" />
          </div>

          <div class="d-md-none pr-card-list">
            <PackageCard v-for="pkg in state.items" :key="pkg.id" :pkg="pkg" @open="openDetail" />
          </div>
        </template>

        <PaginationBar :meta="state.meta" @change="onPageChange" />
      </template>
    </div>
  </section>

  <PackageDetailModal ref="modalRef" />
</template>
