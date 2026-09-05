import { reactive } from 'vue';
import { apiRequest } from '../api/http';

function buildQuery(params) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      query.set(key, value);
    }
  });
  const qs = query.toString();
  return qs ? `?${qs}` : '';
}

export function usePackages() {
  const state = reactive({
    items: [],
    meta: null,
    vendors: [],
    loading: false,
    error: null,
  });

  async function fetchPackages(filters = {}) {
    state.loading = true;
    state.error = null;
    try {
      const query = buildQuery(filters);
      const data = await apiRequest(`/api/packages${query}`);
      // Unterstützt sowohl Laravel-Paginate-Shape ({data, meta}) als auch ein rohes Array
      state.items = data.data ?? data;
      state.meta = data.meta ?? null;
    } catch (error) {
      state.error = error.message;
      state.items = [];
      state.meta = null;
    } finally {
      state.loading = false;
    }
  }

  async function fetchPackage(id) {
    return apiRequest(`/api/packages/${id}`);
  }

  async function fetchVendors() {
    try {
      const data = await apiRequest('/api/vendors');
      state.vendors = data.data ?? data;
    } catch (error) {
      state.vendors = [];
    }
  }

  return { state, fetchPackages, fetchPackage, fetchVendors };
}
