import { reactive } from 'vue';
import { apiRequest } from '../api/http';

export function useStats() {
  const state = reactive({
    data: null,
    loading: false,
    error: null,
  });

  async function fetchStats() {
    state.loading = true;
    state.error = null;
    try {
      state.data = await apiRequest('/api/stats');
    } catch (error) {
      state.error = error.message;
      state.data = null;
    } finally {
      state.loading = false;
    }
  }

  return { state, fetchStats };
}
