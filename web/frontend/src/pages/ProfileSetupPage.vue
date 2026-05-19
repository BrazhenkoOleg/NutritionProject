<template>
  <div class="page">
    <AppHeader
      :theme="theme"
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

        <div class="profile-preview-card">
          <div class="preview-ring">
            <strong>{{ previewTargets.kcal }}</strong>
            <span>ккал/день</span>
          </div>

          <div class="preview-macros">
            <div>
              <strong>{{ previewTargets.protein }}</strong>
              <span>белки, г</span>
            </div>

            <div>
              <strong>{{ previewTargets.fat }}</strong>
              <span>жиры, г</span>
            </div>

            <div>
              <strong>{{ previewTargets.carbs }}</strong>
              <span>углеводы, г</span>
            </div>
          </div>
        </div>
      </section>

      <section class="profile-form-card card">
        <div class="section-header">
          <div>
            <span class="section-label">Профиль</span>
            <h2>Ваши данные</h2>
          </div>
        </div>

        <form
          class="profile-form"
          @submit.prevent="submitProfile"
        >
          <div class="profile-grid">
            <div class="form-group">
              <label>Пол</label>

              <select v-model="form.gender">
                <option value="male">Мужской</option>
                <option value="female">Женский</option>
              </select>
            </div>

            <div class="form-group">
              <label>Возраст</label>

              <input
                v-model.number="form.age"
                type="number"
                min="14"
                max="100"
                placeholder="Например, 22"
              />
            </div>

            <div class="form-group">
              <label>Рост, см</label>

              <input
                v-model.number="form.height_cm"
                type="number"
                min="120"
                max="230"
                step="0.1"
                placeholder="Например, 178"
              />
            </div>

            <div class="form-group">
              <label>Вес, кг</label>

              <input
                v-model.number="form.weight_kg"
                type="number"
                min="35"
                max="250"
                step="0.1"
                placeholder="Например, 72"
              />
            </div>

            <div class="form-group profile-grid-wide">
              <label>Активность</label>

              <select v-model="form.activity_level">
                <option value="sedentary">Минимальная активность</option>
                <option value="light">Лёгкая активность</option>
                <option value="moderate">Средняя активность</option>
                <option value="active">Высокая активность</option>
                <option value="very_active">Очень высокая активность</option>
              </select>
            </div>

            <div class="form-group profile-grid-wide">
              <label>Цель</label>

              <select v-model="form.goal">
                <option value="lose">Снижение веса</option>
                <option value="maintain">Поддержание веса</option>
                <option value="gain">Набор массы</option>
              </select>
            </div>
          </div>

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
        </form>
      </section>
    </main>

    <AppFooter />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

import AppHeader from '../components/layout/AppHeader.vue'
import AppFooter from '../components/layout/AppFooter.vue'
import IconResolver from '../components/ui/IconResolver.vue'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const theme = ref(localStorage.getItem('theme') || 'light')

const form = reactive({
  gender: authStore.user?.gender || 'male',
  age: authStore.user?.age || 22,
  height_cm: Number(authStore.user?.height_cm || 175),
  weight_kg: Number(authStore.user?.weight_kg || 70),
  activity_level: authStore.user?.activity_level || 'moderate',
  goal: authStore.user?.goal || 'maintain',
})

const activityFactors = {
  sedentary: 1.2,
  light: 1.375,
  moderate: 1.55,
  active: 1.725,
  very_active: 1.9,
}

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

const previewTargets = computed(() => {
  const weight = Number(form.weight_kg || 0)
  const height = Number(form.height_cm || 0)
  const age = Number(form.age || 0)

  if (!weight || !height || !age) {
    return {
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    }
  }

  let bmr = 10 * weight + 6.25 * height - 5 * age

  if (form.gender === 'male') {
    bmr += 5
  } else {
    bmr -= 161
  }

  const maintenance = bmr * (activityFactors[form.activity_level] || 1.55)

  let kcal = maintenance

  if (form.goal === 'lose') {
    kcal *= 0.85
  } else if (form.goal === 'gain') {
    kcal *= 1.1
  }

  const protein = weight * 1.6
  const fat = (kcal * 0.25) / 9
  const carbs = Math.max((kcal - protein * 4 - fat * 9) / 4, 0)

  return {
    kcal: Math.round(kcal),
    protein: Math.round(protein),
    fat: Math.round(fat),
    carbs: Math.round(carbs),
  }
})

onMounted(() => {
  applyTheme(theme.value)
})

function applyTheme(value) {
  theme.value = value
  localStorage.setItem('theme', value)
  document.documentElement.setAttribute('data-theme', value)
}

function toggleTheme() {
  applyTheme(theme.value === 'light' ? 'dark' : 'light')
}

async function logout() {
  await authStore.logout()
  router.push('/login')
}

async function submitProfile() {
  try {
    await authStore.updateProfile({
      gender: form.gender,
      age: Number(form.age),
      height_cm: Number(form.height_cm),
      weight_kg: Number(form.weight_kg),
      activity_level: form.activity_level,
      goal: form.goal,
    })

    toastStore.success('Профиль настроен. Можно вести дневник питания.')
    router.push('/dashboard')
  } catch (error) {
    console.error(error)

    if (error.response?.data?.errors) {
      const firstError = Object.values(error.response.data.errors).flat()[0]
      toastStore.error(firstError || 'Проверьте заполненные данные.')
      return
    }

    toastStore.error('Не удалось сохранить профиль. Попробуйте ещё раз.')
  }
}
</script>