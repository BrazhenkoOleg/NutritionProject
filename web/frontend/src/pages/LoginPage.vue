<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const message = ref('')

const form = ref({
  email: '',
  password: '',
})

async function login() {
  message.value = 'Вход...'

  try {
    await authStore.login(form.value)

    message.value = ''

    if (authStore.user?.profile_completed) {
        router.push('/dashboard')
    } else {
        router.push('/profile-setup')
    }
  } catch (error) {
    console.error(error)

    if (error.response?.data?.message) {
      message.value = error.response.data.message
    } else {
      message.value = 'Ошибка входа'
    }
  }
}
</script>

<template>
  <main class="page auth-page">
    <section class="card auth-card">
      <h1>NutriVision</h1>

      <p class="subtitle">
        Войдите в аккаунт, чтобы загружать изображения и сохранять историю анализов.
      </p>

      <form
        class="auth-form"
        @submit.prevent="login"
      >
        <div class="field">
          <label>Email</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="Введите email"
            required
          />
        </div>

        <div class="field">
          <label>Пароль</label>
          <input
            v-model="form.password"
            type="password"
            placeholder="Введите пароль"
            required
          />
        </div>

        <button
          type="submit"
          :disabled="authStore.isLoading"
        >
          {{ authStore.isLoading ? 'Вход...' : 'Войти' }}
        </button>
      </form>

      <p
        v-if="message"
        class="message"
      >
        {{ message }}
      </p>

      <p class="auth-link">
        Нет аккаунта?
        <RouterLink to="/register">
          Зарегистрироваться
        </RouterLink>
      </p>
    </section>
  </main>
</template>