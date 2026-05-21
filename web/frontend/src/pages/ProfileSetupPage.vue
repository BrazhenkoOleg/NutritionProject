<template>
  <div class="page">
    <AppHeader
      :theme="theme"
      :is-authenticated="authStore.isAuthenticated"
      @logout="logout"
      @toggle-theme="toggleTheme"
    />

    <main class="profile-setup-page">
      <section class="profile-hero card">
        <div class="profile-hero-copy">
          <span class="eyebrow">
            <IconResolver
              name="User"
              :size="16"
            />
            Первичная настройка
          </span>

          <h1>Настроим персональные цели питания</h1>

          <p>
            Укажите базовые параметры, чтобы NutriVision рассчитал дневную норму калорий,
            белков, жиров и углеводов под ваш образ жизни и цель.
          </p>
        </div>

        <ProfilePreview :targets="previewTargets" />
      </section>

      <section class="profile-form-card card">
        <div class="section-header">
          <div>
            <span class="section-label">Профиль</span>
            <h2>Ваши данные</h2>
          </div>
        </div>

        <ProfileForm
          :form="form"
          @update:form="updateForm"
          @submit="submitProfile"
        >
          <div class="profile-goal-explainer">
            <IconResolver
              name="Sparkles"
              :size="20"
            />

            <div>
              <strong>{{ goalTitle }}</strong>
              <span>{{ goalDescription }}</span>
            </div>
          </div>

          <button
            type="submit"
            class="primary-button profile-submit-button"
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

            <span>{{ authStore.isLoading ? 'Сохраняем...' : 'Сохранить и перейти в дневник' }}</span>
          </button>
        </ProfileForm>
      </section>
    </main>

    <AppFooter />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'

import { useTheme } from '../composables/useTheme'

import {
  calculatePreviewTargets,
  createProfileFormFromUser,
  createProfilePayload,
} from '../utils/profileTargets'

import { getFriendlyErrorMessage } from '../utils/errors'

import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

import AppHeader from '../components/layout/AppHeader.vue'
import AppFooter from '../components/layout/AppFooter.vue'
import IconResolver from '../components/ui/IconResolver.vue'
import ProfilePreview from '../components/profile/ProfilePreview.vue'
import ProfileForm from '../components/profile/ProfileForm.vue'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const {
  theme,
  applyTheme,
  toggleTheme,
} = useTheme()

const form = reactive(createProfileFormFromUser(authStore.user))

const previewTargets = computed(() => {
  return calculatePreviewTargets(form)
})

const goalTitle = computed(() => {
  const titles = {
    lose: 'Умеренный дефицит калорий',
    maintain: 'Баланс и поддержание формы',
    gain: 'Контролируемый профицит калорий',
  }

  return titles[form.goal] || titles.maintain
})

const goalDescription = computed(() => {
  const descriptions = {
    lose: 'Система рассчитает норму с небольшим дефицитом, чтобы снижать вес постепенно.',
    maintain: 'Норма будет рассчитана для сохранения текущего веса и контроля рациона.',
    gain: 'Система добавит умеренный профицит для набора массы без резкого избытка калорий.',
  }

  return descriptions[form.goal] || descriptions.maintain
})

onMounted(() => {
  applyTheme(theme.value)
  resetForm()
})

function updateForm(value) {
  Object.assign(form, value)
}

function resetForm() {
  Object.assign(form, createProfileFormFromUser(authStore.user))
}

async function logout() {
  await authStore.logout()
  router.push('/login')
}

async function submitProfile() {
  try {
    await authStore.updateProfile(createProfilePayload(form))

    toastStore.success('Профиль настроен. Можно вести дневник питания.')
    router.push('/dashboard')
  } catch (error) {
    console.error(error)
    toastStore.error(getFriendlyErrorMessage(error))
  }
}
</script>