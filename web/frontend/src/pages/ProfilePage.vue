<template>
  <div class="page">
    <AppHeader
      :theme="theme"
      :is-authenticated="authStore.isAuthenticated"
      @logout="logout"
      @toggle-theme="toggleTheme"
    />

    <main class="profile-page-modern">
      <section class="profile-hero card">
        <div class="profile-hero-copy">
          <span class="eyebrow">
            <IconResolver
              name="Settings"
              :size="16"
            />
            Персональные настройки
          </span>

          <h1>Профиль и дневные цели</h1>

          <p>
            Обновите параметры профиля, если изменился вес, активность или цель.
            NutriVision пересчитает норму КБЖУ автоматически.
          </p>
        </div>

        <ProfilePreview :targets="previewTargets" />
      </section>

      <section class="profile-form-card card">
        <div class="section-header">
          <div>
            <span class="section-label">Данные пользователя</span>
            <h2>{{ authStore.user?.name || 'Профиль' }}</h2>
          </div>

          <button
            type="button"
            class="light-button"
            @click="router.push('/dashboard')"
          >
            Вернуться в дневник
          </button>
        </div>

        <ProfileForm
          :form="form"
          @update:form="updateForm"
          @submit="submitProfile"
        >
          <div class="profile-current-goals">
            <div>
              <strong>{{ currentGoals.kcal }}</strong>
              <span>текущая цель, ккал</span>
            </div>

            <div>
              <strong>{{ currentGoals.protein }}</strong>
              <span>белки, г</span>
            </div>

            <div>
              <strong>{{ currentGoals.fat }}</strong>
              <span>жиры, г</span>
            </div>

            <div>
              <strong>{{ currentGoals.carbs }}</strong>
              <span>углеводы, г</span>
            </div>
          </div>

          <div class="profile-goal-explainer">
            <IconResolver
              name="Activity"
              :size="20"
            />

            <div>
              <strong>Новые цели будут применены к будущим расчётам</strong>
              <span>
                История питания сохранится, а дневная норма обновится после сохранения профиля.
              </span>
            </div>
          </div>

          <div class="profile-actions">
            <button
              type="submit"
              class="primary-button"
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

              <span>{{ authStore.isLoading ? 'Сохраняем...' : 'Сохранить изменения' }}</span>
            </button>

            <button
              type="button"
              class="light-button"
              @click="resetForm"
            >
              Сбросить
            </button>
          </div>
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
  getCurrentGoalsFromUser,
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

const currentGoals = computed(() => {
  return getCurrentGoalsFromUser(authStore.user)
})

const previewTargets = computed(() => {
  return calculatePreviewTargets(form)
})

onMounted(async () => {
  applyTheme(theme.value)

  if (!authStore.user && authStore.token) {
    try {
      await authStore.fetchUser()
    } catch {
      router.push('/login')
      return
    }
  }

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

    toastStore.success('Профиль обновлён.')
    router.push('/dashboard')
  } catch (error) {
    console.error(error)
    toastStore.error(getFriendlyErrorMessage(error))
  }
}
</script>