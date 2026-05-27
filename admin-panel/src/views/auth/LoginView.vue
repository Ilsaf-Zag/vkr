<script setup lang="ts">
import { LockKeyhole } from 'lucide-vue-next';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();
const login = ref('');
const password = ref('');
const error = ref('');

async function submit() {
  error.value = '';

  try {
    await auth.login(login.value, password.value);
    await router.push('/');
  } catch {
    error.value = 'Неверный логин или пароль';
  }
}
</script>

<template>
  <main class="login-page">
    <form class="login-panel" @submit.prevent="submit">
      <div class="login-title">
        <LockKeyhole :size="24" />
        <div>
          <h1>ООО АЗЫК</h1>
          <p>Административная панель</p>
        </div>
      </div>

      <label class="field">
        <span>Логин</span>
        <input v-model="login" autocomplete="username" required />
      </label>

      <label class="field">
        <span>Пароль</span>
        <input v-model="password" autocomplete="current-password" type="password" required />
      </label>

      <p v-if="error" class="error">{{ error }}</p>

      <button class="button" type="submit" :disabled="auth.loading">
        Войти
      </button>
    </form>
  </main>
</template>

<style scoped>
.login-page {
  display: grid;
  place-items: center;
  min-height: 100vh;
  padding: 20px;
}

.login-panel {
  display: grid;
  gap: 14px;
  width: min(420px, 100%);
  border: 1px solid #d9e1e8;
  border-radius: 8px;
  background: #fff;
  padding: 22px;
}

.login-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.login-title h1 {
  margin: 0;
  font-size: 20px;
}

.login-title p {
  margin: 2px 0 0;
  color: #687486;
}

.error {
  margin: 0;
  color: #b42318;
}
</style>

