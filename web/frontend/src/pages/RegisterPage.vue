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

        <h1>Создание аккаунта</h1>

        <p>
          Зарегистрируйтесь, чтобы сохранять историю питания,
          анализировать блюда по фото и отслеживать дневные цели.
        </p>
      </div>

      <form
        class="auth-form"
        @submit.prevent="submitRegister"
      >
        <div
          v-if="errorMessage"
          class="form-alert"
        >
          <strong>Не удалось создать аккаунт</strong>
          <span>{{ errorMessage }}</span>
        </div>

        <div class="form-group">
          <label>Имя</label>

          <input
            v-model.trim="form.name"
            type="text"
            autocomplete="name"
            placeholder="Ваше имя"
            required
          />
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
            autocomplete="new-password"
            placeholder="Минимум 6 символов"
            required
          />
        </div>

        <div class="form-group">
          <label>Повторите пароль</label>

          <input
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            placeholder="Повторите пароль"
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
            name="CheckCircle2"
            :size="18"
          />

          <span>{{ authStore.isLoading ? 'Создаём аккаунт...' : 'Зарегистрироваться' }}</span>
        </button>
      </form>

      <div class="auth-switch">
        <span>Уже есть аккаунт?</span>

        <RouterLink to="/login">
          Войти
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
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
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

async function submitRegister() {
  errorMessage.value = ''

  if (!form.name || !form.email || !form.password || !form.password_confirmation) {
    errorMessage.value = 'Заполните все поля.'
    return
  }

  if (form.password.length < 6) {
    errorMessage.value = 'Пароль должен быть не короче 6 символов.'
    return
  }

  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Пароли не совпадают.'
    return
  }

  try {
    await authStore.register({
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })

    router.push('/profile-setup')
  } catch (error) {
    console.error(error)

    if (error.response?.data?.errors) {
      const firstError = Object.values(error.response.data.errors).flat()[0]
      errorMessage.value = firstError || 'Проверьте данные регистрации.'
      return
    }

    if (error.response?.status === 422) {
      errorMessage.value = 'Проверьте данные регистрации.'
      return
    }

    errorMessage.value = 'Не удалось создать аккаунт. Попробуйте позже.'
  }
}
</script>