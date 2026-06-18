<template>
  <div class="hello-world">
    <h1>Hello World from Dummy ION App</h1>

    <div v-if="loading" class="status">
      Memeriksa status autentikasi...
    </div>

    <div v-else-if="authenticated" class="status authenticated">
      <p><strong>Status:</strong> Login ✅</p>
      <p><strong>Nama:</strong> {{ userName }}</p>
      <p v-if="userEmail"><strong>Email:</strong> {{ userEmail }}</p>
      <button @click="handleLogout">Logout</button>
    </div>

    <div v-else class="status guest">
      <p><strong>Status:</strong> Guest</p>
      <p>Silakan login melalui ION SSO.</p>
    </div>

    <div v-if="message" class="message">{{ message }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const loading = ref(true);
const authenticated = ref(false);
const user = ref(null);
const message = ref('');

const userName = computed(() => {
  if (!user.value) return '-';
  return user.value.name || user.value.username || user.value.nik_sap || 'User';
});

const userEmail = computed(() => {
  if (!user.value) return null;
  return user.value.email || null;
});

async function fetchMe() {
  try {
    const response = await fetch('/api/me', {
      credentials: 'include',
    });
    const data = await response.json();

    authenticated.value = data.authenticated === true;
    user.value = data.user || null;
  } catch (error) {
    authenticated.value = false;
    user.value = null;
    message.value = 'Gagal memeriksa status autentikasi.';
  } finally {
    loading.value = false;
  }
}

async function handleLogout() {
  try {
    const response = await fetch('/api/logout', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (response.ok) {
      authenticated.value = false;
      user.value = null;
      message.value = 'Logout berhasil.';
    } else {
      message.value = 'Logout gagal.';
    }
  } catch (error) {
    message.value = 'Terjadi kesalahan saat logout.';
  }
}

onMounted(() => {
  fetchMe();
});
</script>

<style scoped>
.hello-world {
  font-family: Arial, sans-serif;
  max-width: 600px;
  margin: 3rem auto;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
}

h1 {
  color: #2c3e50;
}

.status {
  margin-top: 1.5rem;
  font-size: 1.1rem;
}

.authenticated {
  color: #27ae60;
}

.guest {
  color: #7f8c8d;
}

button {
  margin-top: 1rem;
  padding: 0.6rem 1.2rem;
  font-size: 1rem;
  color: white;
  background-color: #e74c3c;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:hover {
  background-color: #c0392b;
}

.message {
  margin-top: 1rem;
  font-size: 0.95rem;
  color: #2980b9;
}
</style>
