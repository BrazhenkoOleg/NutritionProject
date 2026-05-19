<template>
  <section class="daily-summary">
    <div class="daily-summary-header">
      <div>
        <span class="section-label">Итоги дня</span>
        <h2>Дневной баланс</h2>
      </div>

      <button
        type="button"
        class="light-button"
        @click="$emit('open-profile')"
      >
        <IconResolver
          name="Settings"
          :size="17"
        />
        <span>Изменить норму</span>
      </button>
    </div>

    <div class="daily-score-card">
      <div class="daily-score-main">
        <div class="score-ring">
          <strong>{{ caloriesPercent }}</strong>
          <span>%</span>
        </div>

        <div>
          <strong>{{ scoreTitle }}</strong>
          <p>{{ scoreDescription }}</p>
        </div>
      </div>

      <div class="daily-score-values">
        <strong>{{ Math.round(dailyTotals.kcal || 0) }}</strong>
        <span>из {{ dailyGoals.kcal }} ккал</span>
      </div>
    </div>

    <div class="goal-grid">
      <div class="goal-card">
        <div class="goal-card-top">
          <span>Калории</span>
          <strong>{{ Math.round(dailyTotals.kcal || 0) }}</strong>
        </div>

        <div class="progress-bar">
          <div
            class="progress-fill"
            :style="{ width: `${getProgressWidth(dailyTotals.kcal, dailyGoals.kcal)}%` }"
          ></div>
        </div>

        <small>{{ dailyGoals.kcal }} ккал цель</small>
      </div>

      <div class="goal-card">
        <div class="goal-card-top">
          <span>Белки</span>
          <strong>{{ formatNumber(dailyTotals.protein) }} г</strong>
        </div>

        <div class="progress-bar">
          <div
            class="progress-fill"
            :style="{ width: `${getProgressWidth(dailyTotals.protein, dailyGoals.protein)}%` }"
          ></div>
        </div>

        <small>{{ formatNumber(dailyGoals.protein) }} г цель</small>
      </div>

      <div class="goal-card">
        <div class="goal-card-top">
          <span>Жиры</span>
          <strong>{{ formatNumber(dailyTotals.fat) }} г</strong>
        </div>

        <div class="progress-bar">
          <div
            class="progress-fill"
            :style="{ width: `${getProgressWidth(dailyTotals.fat, dailyGoals.fat)}%` }"
          ></div>
        </div>

        <small>{{ formatNumber(dailyGoals.fat) }} г цель</small>
      </div>

      <div class="goal-card">
        <div class="goal-card-top">
          <span>Углеводы</span>
          <strong>{{ formatNumber(dailyTotals.carbs) }} г</strong>
        </div>

        <div class="progress-bar">
          <div
            class="progress-fill"
            :style="{ width: `${getProgressWidth(dailyTotals.carbs, dailyGoals.carbs)}%` }"
          ></div>
        </div>

        <small>{{ formatNumber(dailyGoals.carbs) }} г цель</small>
      </div>
    </div>

    <div class="daily-recommendation">
      <IconResolver
        :name="recommendationIcon"
        :size="20"
      />

      <div>
        <strong>{{ recommendationTitle }}</strong>
        <span>{{ recommendationText }}</span>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import IconResolver from '../ui/IconResolver.vue'

const props = defineProps({
  dailyTotals: {
    type: Object,
    default: () => ({
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    }),
  },
  user: {
    type: Object,
    default: null,
  },
})

defineEmits(['open-profile'])

const dailyGoals = computed(() => {
  return {
    kcal: Math.round(Number(props.user?.daily_kcal_goal || 0)),
    protein: Number(props.user?.daily_protein_goal || 0),
    fat: Number(props.user?.daily_fat_goal || 0),
    carbs: Number(props.user?.daily_carbs_goal || 0),
  }
})

const caloriesPercent = computed(() => {
  if (!dailyGoals.value.kcal) {
    return 0
  }

  return Math.min(Math.round((Number(props.dailyTotals.kcal || 0) / dailyGoals.value.kcal) * 100), 999)
})

const scoreTitle = computed(() => {
  if (!dailyGoals.value.kcal) {
    return 'Цель не настроена'
  }

  if (props.dailyTotals.kcal === 0) {
    return 'День ещё не заполнен'
  }

  if (caloriesPercent.value < 75) {
    return 'Ниже дневной нормы'
  }

  if (caloriesPercent.value <= 115) {
    return 'Баланс близок к цели'
  }

  return 'Выше дневной нормы'
})

const scoreDescription = computed(() => {
  if (!dailyGoals.value.kcal) {
    return 'Заполните профиль, чтобы видеть персональные нормы.'
  }

  if (props.dailyTotals.kcal === 0) {
    return 'Добавьте фото блюда или внесите продукты вручную.'
  }

  if (caloriesPercent.value < 75) {
    return 'Можно добавить полноценный приём пищи или увеличить порцию.'
  }

  if (caloriesPercent.value <= 115) {
    return 'Рацион выглядит сбалансированно относительно вашей цели.'
  }

  return 'Проверьте размер порций и калорийность перекусов.'
})

const recommendationIcon = computed(() => {
  if (!dailyGoals.value.kcal || props.dailyTotals.kcal === 0) {
    return 'Sparkles'
  }

  if (caloriesPercent.value < 75) {
    return 'Activity'
  }

  if (caloriesPercent.value <= 115) {
    return 'CheckCircle2'
  }

  return 'XCircle'
})

const recommendationTitle = computed(() => {
  if (!dailyGoals.value.kcal) {
    return 'Настройте профиль'
  }

  if (props.dailyTotals.kcal === 0) {
    return 'Добавьте первую запись'
  }

  if (caloriesPercent.value < 75) {
    return 'Рацион пока неполный'
  }

  if (caloriesPercent.value <= 115) {
    return 'Хороший дневной баланс'
  }

  return 'Есть превышение цели'
})

const recommendationText = computed(() => {
  if (!dailyGoals.value.kcal) {
    return 'Персональная дневная норма появится после заполнения профиля.'
  }

  if (props.dailyTotals.kcal === 0) {
    return 'Сделайте фото блюда — система распознает продукты и предложит уточнить вес порции.'
  }

  if (caloriesPercent.value < 75) {
    return 'Если день ещё не завершён, добавьте следующий приём пищи. Если завершён — рацион может быть слишком низкокалорийным.'
  }

  if (caloriesPercent.value <= 115) {
    return 'Продолжайте отслеживать порции и поддерживать похожий баланс.'
  }

  return 'Попробуйте уменьшить порции высококалорийных продуктов или пересмотреть перекусы.'
})

function getProgressWidth(value, goal) {
  if (!goal) {
    return 0
  }

  return Math.min(Math.round((Number(value || 0) / Number(goal)) * 100), 100)
}

function formatNumber(value) {
  return Number(value || 0).toFixed(1)
}
</script>