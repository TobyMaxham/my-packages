import { reactive, readonly } from 'vue';
import { apiRequest } from '../api/http';

// Modulweiter State -> jede Komponente, die useAuth() aufruft, teilt sich denselben Store.
const state = reactive({
  user: null,
  isLoggedIn: false,
  loading: true,
});

async function fetchUser() {
  state.loading = true;
  try {
    const data = await apiRequest('/api/user');
    state.user = data;
    state.isLoggedIn = true;
  } catch (error) {
    state.user = null;
    state.isLoggedIn = false;
  } finally {
    state.loading = false;
  }
}

async function login({ email, password }) {
  await apiRequest('/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
  await fetchUser();
}

async function logout() {
  await apiRequest('/logout', { method: 'POST' });
  state.user = null;
  state.isLoggedIn = false;
}

export function useAuth() {
  return {
    auth: readonly(state),
    fetchUser,
    login,
    logout,
  };
}
