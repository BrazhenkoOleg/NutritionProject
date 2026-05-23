<template>
  <AuthCard
    :theme="theme"
    title="Создание аккаунта"
    description="Зарегистрируйтесь, чтобы сохранять историю питания, анализировать блюда по фото и отслеживать дневные цели."
    @toggle-theme="toggleTheme"
  >
    <form
      class="auth-form"
      @submit.prevent="submitRegister"
    >
      <AuthFormAlert
        title="Не удалось создать аккаунт"
        :message="errorMessage"
      />

      <div class="form-group">
        <label>Имя</label>

        <input
          v-model.trim="form.name"
          type="text"
          autocomplete="name"
          placeholder="Ваше имя"
          required
          :disabled="authStore.isLoading"
        />
      </div>

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
          autocomplete="new-password"
          placeholder="Минимум 6 символов"
          required
          :disabled="authStore.isLoading"
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
          :disabled="authStore.isLoading"
        />
      </div>

      <AuthSubmitButton
        :is-loading="authStore.isLoading"
        icon="CheckCircle2"
        text="Зарегистрироваться"
        loading-text="Создаём аккаунт..."
      />
    </form>

    <template #footer>
      <span>Уже есть аккаунт?</span>

      <RouterLink to="/login">
        Войти
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
import { getRegisterValidationError } from '../utils/authValidation'
import { getRegisterErrorMessage } from '../utils/errors'

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
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

onMounted(() => {
  applyTheme(theme.value)
  redirectAuthorizedUser()
})

async function submitRegister() {
  errorMessage.value = ''

  const validationError = getRegisterValidationError(form)

  if (validationError) {
    errorMessage.value = validationError
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
    errorMessage.value = getRegisterErrorMessage(error)
  }
}
</script>