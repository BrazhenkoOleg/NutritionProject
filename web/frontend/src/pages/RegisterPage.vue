<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const message = ref('')

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

async function register() {
  message.value = 'Регистрация...'

  try {
    await authStore.register(form.value)

    message.value = ''
    router.push('/profile-setup')
  } catch (error) {
    console.error(error)

    if (error.response?.data?.message) {
      message.value = error.response.data.message
    } else {
      message.value = 'Ошибка регистрации'
    }
  }
}
</script>

<template>
  <main class="page auth-page">
    <section class="card auth-card">
      <h1>NutriVision</h1>

      <p class="subtitle">
        Создайте аккаунт для работы с историей анализов и сохранением результатов.
      </p>

      <form
        class="auth-form"
        @submit.prevent="register"
      >
        <div class="field">
          <label>Имя</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Введите имя"
            required
          />
        </div>

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
            placeholder="Минимум 6 символов"
            required
          />
        </div>

        <div class="field">
          <label>Подтверждение пароля</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            placeholder="Повторите пароль"
            required
          />
        </div>

        <button
          type="submit"
          :disabled="authStore.isLoading"
        >
          {{ authStore.isLoading ? 'Регистрация...' : 'Зарегистрироваться' }}
        </button>
      </form>

      <p
        v-if="message"
        class="message"
      >
        {{ message }}
      </p>

      <p class="auth-link">
        Уже есть аккаунт?
        <RouterLink to="/login">
          Войти
        </RouterLink>
      </p>
    </section>
  </main>
</template>