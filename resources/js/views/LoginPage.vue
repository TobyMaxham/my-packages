<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const { login } = useAuth();
const router = useRouter();

const email = ref('');
const password = ref('');
const error = ref('');
const submitting = ref(false);

async function handleSubmit() {
  error.value = '';
  submitting.value = true;
  try {
    await login({ email: email.value, password: password.value });
    router.push('/');
  } catch (err) {
    error.value = err.message || 'Login fehlgeschlagen.';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <section class="pr-login-section">
    <div class="container">
      <form class="pr-login-card" @submit.prevent="handleSubmit">
        <h1>Login</h1>
        <p class="text-muted">Melde dich an, um erweiterte Paket-Infos zu sehen.</p>

        <div class="mb-3">
          <label class="form-label" for="email">E-Mail</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="form-control"
            required
            autocomplete="email"
          />
        </div>

        <div class="mb-3">
          <label class="form-label" for="password">Passwort</label>
          <input
            id="password"
            v-model="password"
            type="password"
            class="form-control"
            required
            autocomplete="current-password"
          />
        </div>

        <p v-if="error" class="pr-error">{{ error }}</p>

        <button type="submit" class="btn pr-btn-primary w-100" :disabled="submitting">
          {{ submitting ? 'Wird geprüft …' : 'Einloggen' }}
        </button>
      </form>
    </div>
  </section>
</template>
