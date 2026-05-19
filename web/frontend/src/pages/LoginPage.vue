<template>
  <div class="auth-page">
    <section class="auth-card">
      <button
        type="button"
        class="auth-theme-toggle"
        @click="toggleTheme"
      >
        <IconResolver
          :name="theme === 'dark' ? 'Sun' : 'Moon'"
          :size="17"
        />

        <span>{{ theme === 'dark' ? 'Светлая тема' : 'Тёмная тема' }}</span>
      </button>

      <div class="auth-header">
        <div class="auth-logo">
          <IconResolver
            name="ScanSearch"
            :size="28"
          />
        </div>

        <span class="eyebrow">
          <IconResolver
            name="Sparkles"
            :size="16"
          />
          NutriVision
        </span>

        <h1>Вход в дневник питания</h1>

        <p>
          Войдите в аккаунт, чтобы продолжить анализировать блюда,
          уточнять порции и отслеживать КБЖУ.
        </p>
      </div>

      <form
        class="auth-form"
        @submit.prevent="submitLogin"
      >
        <div
          v-if="errorMessage"
          class="form-alert"
        >
          <strong>Не удалось войти</strong>
          <span>{{ errorMessage }}</span>
        </div>

        <div class="form-group">
          <label>Email</label>

          <input
            v-model.trim="form.email"
            type="email"
            autocomplete="email"
            placeholder="you@example.com"
            required
          />
        </div>

        <div class="form-group">
          <label>Пароль</label>

          <input
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            placeholder="Введите пароль"
            required
          />
        </div>

        <button
          type="submit"
          class="auth-submit-button"
          :disabled="authStore.isLoading"
        >
          <IconResolver
            v-if="authStore.isLoading"
            name="Loader2"
            :size="18"
            class="spin-icon"
          />

          <IconResolver
            v-else
            name="User"
            :size="18"
          />

          <span>{{ authStore.isLoading ? 'Входим...' : 'Войти' }}</span>
        </button>
      </form>

      <div class="auth-switch">
        <span>Нет аккаунта?</span>

        <RouterLink to="/register">
          Зарегистрироваться
        </RouterLink>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'
import IconResolver from '../components/ui/IconResolver.vue'

const router = useRouter()
const authStore = useAuthStore()

const theme = ref(localStorage.getItem('theme') || 'light')
const errorMessage = ref('')

const form = reactive({
  email: '',
  password: '',
})

onMounted(() => {
  applyTheme(theme.value)

  if (authStore.token && authStore.user?.profile_completed) {
    router.push('/dashboard')
  } else if (authStore.token && authStore.user && !authStore.user.profile_completed) {
    router.push('/profile-setup')
  }
})

function applyTheme(value) {
  theme.value = value
  localStorage.setItem('theme', value)
  document.documentElement.setAttribute('data-theme', value)
}

function toggleTheme() {
  applyTheme(theme.value === 'light' ? 'dark' : 'light')
}

async function submitLogin() {
  errorMessage.value = ''

  if (!form.email || !form.password) {
    errorMessage.value = 'Введите email и пароль.'
    return
  }

  try {
    await authStore.login({
      email: form.email,
      password: form.password,
    })

    if (authStore.user?.profile_completed) {
      router.push('/dashboard')
    } else {
      router.push('/profile-setup')
    }
  } catch (error) {
    console.error(error)

    if (error.response?.status === 422 || error.response?.status === 401) {
      errorMessage.value = 'Неверный email или пароль.'
      return
    }

    errorMessage.value = 'Не удалось выполнить вход. Попробуйте позже.'
  }
}
</script>