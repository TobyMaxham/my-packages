import { createApp } from 'vue';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'bootstrap-icons/font/bootstrap-icons.css';
import App from './App.vue';
import { router } from './router';
import { useAuth } from './composables/useAuth';
import '../css/dashboard.css';

const app = createApp(App);
app.use(router);
app.mount('#package-dashboard');

// Session-Status einmalig beim App-Start prüfen (401 -> einfach ausgeloggt)
useAuth().fetchUser();
