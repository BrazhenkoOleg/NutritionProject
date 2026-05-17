<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const message = ref('')

const form = ref({
  gender: 'male',
  age: 25,
  height_cm: 175,
  weight_kg: 70,
  activity_level: 'moderate',
  goal: 'maintain',
})

const goalLabels = {
  lose: 'Похудение',
  maintain: 'Поддержка формы',
  gain: 'Набор массы',
}

const activityLabels = {
  sedentary: 'Минимальная активность',
  light: 'Лёгкая активность',
  moderate: 'Средняя активность',
  active: 'Высокая активность',
  very_active: 'Очень высокая активность',
}

const calculatedPreview = computed(() => {
  const weight = Number(form.value.weight_kg || 0)
  const height = Number(form.value.height_cm || 0)
  const age = Number(form.value.age || 0)

  if (!weight || !height || !age) {
    return null
  }

  let bmr = 10 * weight + 6.25 * height - 5 * age

  if (form.value.gender === 'male') {
    bmr += 5
  } else {
    bmr -= 161
  }

  const activityFactors = {
    sedentary: 1.2,
    light: 1.375,
    moderate: 1.55,
    active: 1.725,
    very_active: 1.9,
  }

  let kcal = bmr * activityFactors[form.value.activity_level]

  if (form.value.goal === 'lose') {
    kcal *= 0.85
  }

  if (form.value.goal === 'gain') {
    kcal *= 1.1
  }

  const protein = weight * 1.6
  const fat = (kcal * 0.25) / 9
  const carbs = Math.max((kcal - protein * 4 - kcal * 0.25) / 4, 0)

  return {
    kcal: Math.round(kcal),
    protein: protein.toFixed(2),
    fat: fat.toFixed(2),
    carbs: carbs.toFixed(2),
  }
})

function formatNumber(value) {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  return Number(value).toFixed(2)
}

async function saveProfile() {
  message.value = 'Сохраняем профиль...'

  try {
    await authStore.updateProfile(form.value)

    message.value = ''
    router.push('/dashboard')
  } catch (error) {
    console.error(error)

    if (error.response?.data?.message) {
      message.value = error.response.data.message
    } else {
      message.value = 'Ошибка при сохранении профиля'
    }
  }
}
</script>

<template>
  <main class="page profile-setup-page">
    <section class="card profile-setup-card">
      <div class="profile-setup-header">
        <div>
          <h1>Настройка профиля</h1>

          <p class="subtitle">
            Заполните данные, чтобы система рассчитала ориентировочную дневную норму КБЖУ.
          </p>
        </div>

        <div
          v-if="authStore.user"
          class="profile-user-mini"
        >
          <strong>{{ authStore.user.name }}</strong>
          <span>{{ authStore.user.email }}</span>
        </div>
      </div>

      <form
        class="profile-form"
        @submit.prevent="saveProfile"
      >
        <div class="profile-grid">
          <div class="field">
            <label>Пол</label>

            <select v-model="form.gender">
              <option value="male">Мужской</option>
              <option value="female">Женский</option>
            </select>
          </div>

          <div class="field">
            <label>Возраст</label>

            <input
              v-model.number="form.age"
              type="number"
              min="14"
              max="100"
              required
            />
          </div>

          <div class="field">
            <label>Рост, см</label>

            <input
              v-model.number="form.height_cm"
              type="number"
              min="120"
              max="230"
              step="0.1"
              required
            />
          </div>

          <div class="field">
            <label>Вес, кг</label>

            <input
              v-model.number="form.weight_kg"
              type="number"
              min="35"
              max="250"
              step="0.1"
              required
            />
          </div>

          <div class="field">
            <label>Активность</label>

            <select v-model="form.activity_level">
              <option
                v-for="(label, value) in activityLabels"
                :key="value"
                :value="value"
              >
                {{ label }}
              </option>
            </select>
          </div>

          <div class="field">
            <label>Цель</label>

            <select v-model="form.goal">
              <option
                v-for="(label, value) in goalLabels"
                :key="value"
                :value="value"
              >
                {{ label }}
              </option>
            </select>
          </div>
        </div>

        <div
          v-if="calculatedPreview"
          class="profile-target-preview"
        >
          <div>
            <h2>Расчётная дневная норма</h2>

            <p>
              Цель: {{ goalLabels[form.goal] }},
              активность: {{ activityLabels[form.activity_level] }}.
            </p>
          </div>

          <div class="totals-grid">
            <div>
              <strong>{{ calculatedPreview.kcal }}</strong>
              <span>ккал</span>
            </div>

            <div>
              <strong>{{ formatNumber(calculatedPreview.protein) }}</strong>
              <span>белки, г</span>
            </div>

            <div>
              <strong>{{ formatNumber(calculatedPreview.fat) }}</strong>
              <span>жиры, г</span>
            </div>

            <div>
              <strong>{{ formatNumber(calculatedPreview.carbs) }}</strong>
              <span>углеводы, г</span>
            </div>
          </div>
        </div>

        <p class="profile-note">
          Расчёт является ориентировочным и используется только для справочной оценки рациона.
        </p>

        <button
          type="submit"
          :disabled="authStore.isLoading"
        >
          {{ authStore.isLoading ? 'Сохранение...' : 'Сохранить и перейти в дневник' }}
        </button>
      </form>

      <p
        v-if="message"
        class="message"
      >
        {{ message }}
      </p>
    </section>
  </main>
</template>