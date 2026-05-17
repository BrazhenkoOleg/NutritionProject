<script setup>
const props = defineProps({
  dailyTotals: {
    type: Object,
    required: true,
  },
  user: {
    type: Object,
    default: null,
  },
})

defineEmits(['open-profile'])

function formatNumber(value) {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  return Number(value).toFixed(2)
}

function getGoalProgress(current, goal) {
  const goalValue = Number(goal || 0)

  if (!goalValue) {
    return 0
  }

  return Math.min((Number(current || 0) / goalValue) * 100, 150)
}

function getProgressClass(current, goal) {
  const goalValue = Number(goal || 0)

  if (!goalValue) {
    return ''
  }

  const percent = (Number(current || 0) / goalValue) * 100

  if (percent < 80) {
    return 'low'
  }

  if (percent > 110) {
    return 'high'
  }

  return 'normal'
}

function getDailyRecommendations() {
  const user = props.user

  if (!user?.profile_completed) {
    return [
      {
        type: 'warning',
        text: 'Заполните профиль, чтобы система могла рассчитать вашу дневную норму КБЖУ.',
      },
    ]
  }

  const recommendations = []

  const kcalGoal = Number(user.daily_kcal_goal || 0)
  const proteinGoal = Number(user.daily_protein_goal || 0)
  const fatGoal = Number(user.daily_fat_goal || 0)
  const carbsGoal = Number(user.daily_carbs_goal || 0)

  const kcal = Number(props.dailyTotals.kcal || 0)
  const protein = Number(props.dailyTotals.protein || 0)
  const fat = Number(props.dailyTotals.fat || 0)
  const carbs = Number(props.dailyTotals.carbs || 0)

  if (kcalGoal && kcal > kcalGoal * 1.1) {
    recommendations.push({
      type: 'danger',
      text: 'Калорийность за день заметно превышает вашу норму. Стоит уменьшить порции или выбрать менее калорийные продукты.',
    })
  }

  if (kcalGoal && kcal < kcalGoal * 0.8) {
    recommendations.push({
      type: 'warning',
      text: 'Калорийность за день сильно ниже вашей нормы. Возможно, рацион недостаточно питательный.',
    })
  }

  if (proteinGoal && protein < proteinGoal * 0.8) {
    recommendations.push({
      type: 'warning',
      text: 'Белка сегодня недостаточно. Можно добавить курицу, рыбу, яйца, творог или другие белковые продукты.',
    })
  }

  if (fatGoal && fat > fatGoal * 1.2) {
    recommendations.push({
      type: 'danger',
      text: 'Жиров сегодня больше рекомендуемой нормы. Обратите внимание на жирные продукты, соусы, сыр, жареные блюда.',
    })
  }

  if (carbsGoal && carbs > carbsGoal * 1.2) {
    recommendations.push({
      type: 'warning',
      text: 'Углеводов сегодня больше рекомендуемой нормы. Проверьте количество хлеба, круп, сладостей или мучных продуктов.',
    })
  }

  if (proteinGoal && protein >= proteinGoal * 0.9 && protein <= proteinGoal * 1.2) {
    recommendations.push({
      type: 'success',
      text: 'Количество белка близко к вашей дневной норме.',
    })
  }

  if (recommendations.length === 0) {
    recommendations.push({
      type: 'success',
      text: 'Рацион за выбранный день близок к рассчитанной норме КБЖУ.',
    })
  }

  return recommendations
}
</script>

<template>
  <div class="daily-summary personal-summary">
    <div class="daily-summary-header">
      <div>
        <h3>🎯 Итого за день</h3>

        <p>
          Сравнение фактического рациона за выбранную дату с вашей персональной нормой.
        </p>
      </div>

      <button
        type="button"
        class="light-button"
        @click="$emit('open-profile')"
      >
        ⚙️ Изменить норму
      </button>
    </div>

    <div class="goal-grid">
      <div class="goal-card">
        <div class="goal-card-header">
          <span>Калории</span>

          <strong>
            {{ Math.round(dailyTotals.kcal || 0) }} / {{ user?.daily_kcal_goal || '—' }}
          </strong>
        </div>

        <div class="goal-bar">
          <div
            class="goal-bar-fill"
            :class="getProgressClass(dailyTotals.kcal, user?.daily_kcal_goal)"
            :style="{ width: `${getGoalProgress(dailyTotals.kcal, user?.daily_kcal_goal)}%` }"
          ></div>
        </div>

        <small>ккал</small>
      </div>

      <div class="goal-card">
        <div class="goal-card-header">
          <span>Белки</span>

          <strong>
            {{ formatNumber(dailyTotals.protein) }} /
            {{ formatNumber(user?.daily_protein_goal) }}
          </strong>
        </div>

        <div class="goal-bar">
          <div
            class="goal-bar-fill"
            :class="getProgressClass(dailyTotals.protein, user?.daily_protein_goal)"
            :style="{ width: `${getGoalProgress(dailyTotals.protein, user?.daily_protein_goal)}%` }"
          ></div>
        </div>

        <small>г</small>
      </div>

      <div class="goal-card">
        <div class="goal-card-header">
          <span>Жиры</span>

          <strong>
            {{ formatNumber(dailyTotals.fat) }} /
            {{ formatNumber(user?.daily_fat_goal) }}
          </strong>
        </div>

        <div class="goal-bar">
          <div
            class="goal-bar-fill"
            :class="getProgressClass(dailyTotals.fat, user?.daily_fat_goal)"
            :style="{ width: `${getGoalProgress(dailyTotals.fat, user?.daily_fat_goal)}%` }"
          ></div>
        </div>

        <small>г</small>
      </div>

      <div class="goal-card">
        <div class="goal-card-header">
          <span>Углеводы</span>

          <strong>
            {{ formatNumber(dailyTotals.carbs) }} /
            {{ formatNumber(user?.daily_carbs_goal) }}
          </strong>
        </div>

        <div class="goal-bar">
          <div
            class="goal-bar-fill"
            :class="getProgressClass(dailyTotals.carbs, user?.daily_carbs_goal)"
            :style="{ width: `${getGoalProgress(dailyTotals.carbs, user?.daily_carbs_goal)}%` }"
          ></div>
        </div>

        <small>г</small>
      </div>
    </div>

    <div class="recommendations-block">
      <h4>Рекомендации</h4>

      <div class="recommendations-list">
        <div
          v-for="(recommendation, index) in getDailyRecommendations()"
          :key="index"
          class="recommendation-item"
          :class="recommendation.type"
        >
          {{ recommendation.text }}
        </div>
      </div>
    </div>
  </div>
</template>