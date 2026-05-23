<template>
  <AuthCard
    :theme="theme"
    title="Вход в дневник питания"
    description="Войдите в аккаунт, чтобы продолжить анализировать блюда, уточнять порции и отслеживать КБЖУ."
    @toggle-theme="toggleTheme"
  >
    <form
      class="auth-form"
      @submit.prevent="submitLogin"
    >
      <AuthFormAlert
        title="Не удалось войти"
        :message="errorMessage"
      />

      <div class="form-group">
        <label>Электронная почта</label>

        <input
          v-model.trim="form.email"
          type="email"
          autocomplete="email"
          placeholder="Введите email"
          required
          :disabled="authStore.isLoading"
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
          :disabled="authStore.isLoading"
        />
      </div>

      <AuthSubmitButton
        :is-loading="authStore.isLoading"
        icon="User"
        text="Войти"
        loading-text="Входим..."
      />
    </form>

    <template #footer>
      <span>Нет аккаунта?</span>

      <RouterLink to="/register">
        Зарегистрироваться
      </RouterLink>
    </template>
  </AuthCard>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useTheme } from '../composables/useTheme'

import { useAuthStore } from '../stores/auth'

import { useAuthRedirect } from '../composables/useAuthRedirect'
import { getLoginValidationError } from '../utils/authValidation'
import { getLoginErrorMessage } from '../utils/errors'

import AuthCard from '../components/auth/AuthCard.vue'
import AuthFormAlert from '../components/auth/AuthFormAlert.vue'
import AuthSubmitButton from '../components/auth/AuthSubmitButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const {
  redirectAuthorizedUser,
} = useAuthRedirect(router, authStore)

const {
  theme,
  applyTheme,
  toggleTheme,
} = useTheme()

const errorMessage = ref('')

const form = reactive({
  email: '',
  password: '',
})

onMounted(() => {
  applyTheme(theme.value)
  redirectAuthorizedUser()
})

async function submitLogin() {
  errorMessage.value = ''

  const validationError = getLoginValidationError(form)

  if (validationError) {
    errorMessage.value = validationError
    return
  }

  try {
    await authStore.login({
      email: form.email,
      password: form.password,
    })

    redirectAuthorizedUser()
  } catch (error) {
    console.error(error)
    errorMessage.value = getLoginErrorMessage(error)
  }
}
</script>