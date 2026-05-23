<template>
  <section class="weekly-stats">
    <div class="section-header">
      <div>
        <span class="section-label">Неделя</span>
        <h2>Статистика питания</h2>
      </div>

      <div class="weekly-range">
        {{ formatShortDate(weekDays[0].date) }} — {{ formatShortDate(weekDays[6].date) }}
      </div>
    </div>

    <div class="weekly-summary-grid">
      <div>
        <strong>{{ formatCalories(weeklyTotals.kcal) }}</strong>
        <span>ккал за неделю</span>
      </div>

      <div>
        <strong>{{ formatCalories(weeklyAverageKcal) }}</strong>
        <span>ккал в среднем</span>
      </div>

      <div>
        <strong>{{ recordsCount }}</strong>
        <span>записей</span>
      </div>

      <div>
        <strong>{{ targetCompletion }}%</strong>
        <span>от недельной цели</span>
      </div>
    </div>

    <div
      class="weekly-insight-card"
      :class="`weekly-insight-card--${insightType}`"
    >

      <div class="weekly-insight-header">
        <div class="weekly-insight-title-block">
          <span class="weekly-insight-kicker">
            <IconResolver
              :name="isInsightLoading ? 'Loader2' : 'BrainCircuit'"
              :size="15"
              :class="{ 'spin-icon': isInsightLoading }"
            />

            ML-анализ недели
          </span>

          <strong>{{ insightTitle }}</strong>
        </div>
      </div>

      <p class="weekly-insight-description">
        {{ insightDescription }}
      </p>

      <div
        v-if="insightFeatureItems.length"
        class="weekly-insight-metrics"
      >
        <div
          v-for="feature in insightFeatureItems"
          :key="feature.label"
          class="weekly-insight-metric"
        >
          <span>{{ feature.label }}</span>

          <strong>{{ feature.value }}</strong>

          <div class="weekly-insight-meter">
            <i :style="{ width: `${feature.percent}%` }"></i>
          </div>
        </div>
      </div>

      <div
        v-if="insightRecommendations.length"
        class="weekly-insight-recommendations"
      >
        <span class="weekly-insight-subtitle">Рекомендации</span>

        <ul class="weekly-insight-list">
          <li
            v-for="recommendation in insightRecommendations"
            :key="recommendation"
          >
            <IconResolver
              name="CheckCircle2"
              :size="15"
            />

            <span>{{ recommendation }}</span>
          </li>
        </ul>
      </div>
    </div>

    <div class="weekly-chart">
      <button
        v-for="day in weekDays"
        :key="day.key"
        type="button"
        class="weekly-chart-day"
        :class="{ active: selectedDayKey === day.key }"
        @click="selectedDayKey = day.key"
      >
        <div
          class="weekly-bar"
          :style="{ height: `${getBarHeight(day.totals.kcal)}%` }"
        ></div>

        <span class="weekly-day-label">{{ day.label }}</span>
        <strong>{{ formatCalories(day.totals.kcal) }}</strong>
      </button>
    </div>

    <div class="weekly-selected-day">
      <div class="selected-day-header">
        <div>
          <strong>{{ selectedDayTitle }}</strong>
          <span>{{ selectedDayAnalyses.length }} записей</span>
        </div>

        <span
          class="selected-day-status"
          :class="selectedDayStatus.className"
        >
          {{ selectedDayStatus.text }}
        </span>
      </div>

      <div class="selected-day-macros">
        <div>
          <strong>{{ formatCalories(selectedDayTotals.kcal) }}</strong>
          <span>ккал</span>
        </div>

        <div>
          <strong>{{ formatMacroPrecise(selectedDayTotals.protein) }}</strong>
          <span>белки, г</span>
        </div>

        <div>
          <strong>{{ formatMacroPrecise(selectedDayTotals.fat) }}</strong>
          <span>жиры, г</span>
        </div>

        <div>
          <strong>{{ formatMacroPrecise(selectedDayTotals.carbs) }}</strong>
          <span>углеводы, г</span>
        </div>
      </div>

      <p class="weekly-recommendation">
        {{ recommendationText }}
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import IconResolver from '../ui/IconResolver.vue'

import {
  formatShortDate,
  getAnalysisDate,
  getMonday,
  getTodayDate,
  toDateInputValue,
} from '../../utils/date'

import {
  createEmptyNutritionTotals,
  getTotalsFromAnalyses,
} from '../../utils/nutrition'

import {
  formatCalories,
  formatMacroPrecise,
  formatPercent,
} from '../../utils/formatters'

const props = defineProps({
  analyses: {
    type: Array,
    default: () => [],
  },
  user: {
    type: Object,
    default: null,
  },
  insight: {
    type: Object,
    default: null,
  },
  isInsightLoading: {
    type: Boolean,
    default: false,
  },
})

const selectedDayKey = ref(getTodayDate())

const weekDays = computed(() => {
  const today = new Date()
  const monday = getMonday(today)

  return Array.from({ length: 7 }).map((_, index) => {
    const date = new Date(monday)
    date.setDate(monday.getDate() + index)

    const key = toDateInputValue(date)
    const dayAnalyses = props.analyses.filter((analysis) => {
      return getAnalysisDate(analysis) === key
    })

    return {
      date,
      key,
      label: date.toLocaleDateString('ru-RU', {
        weekday: 'short',
      }),
      analyses: dayAnalyses,
      totals: getTotalsFromAnalyses(dayAnalyses),
    }
  })
})

const selectedDay = computed(() => {
  return weekDays.value.find((day) => day.key === selectedDayKey.value) || weekDays.value[0]
})

const selectedDayAnalyses = computed(() => {
  return selectedDay.value?.analyses || []
})

const selectedDayTotals = computed(() => {
  return selectedDay.value?.totals || createEmptyNutritionTotals()
})

const weeklyTotals = computed(() => {
  return weekDays.value.reduce(
    (acc, day) => {
      acc.kcal += day.totals.kcal
      acc.protein += day.totals.protein
      acc.fat += day.totals.fat
      acc.carbs += day.totals.carbs

      return acc
    },
    createEmptyNutritionTotals(),
  )
})

const recordsCount = computed(() => {
  return weekDays.value.reduce((acc, day) => acc + day.analyses.length, 0)
})

const weeklyAverageKcal = computed(() => {
  return weeklyTotals.value.kcal / 7
})

const maxDayKcal = computed(() => {
  return Math.max(...weekDays.value.map((day) => day.totals.kcal), 1)
})

const dailyGoal = computed(() => {
  return Number(props.user?.daily_kcal_goal || 0)
})

const weeklyGoal = computed(() => {
  return dailyGoal.value * 7
})

const targetCompletion = computed(() => {
  if (!weeklyGoal.value) {
    return 0
  }

  const percent = (weeklyTotals.value.kcal / weeklyGoal.value) * 100

  return Math.min(formatPercent(percent), 999)
})

const selectedDayTitle = computed(() => {
  if (!selectedDay.value) {
    return 'Выбранный день'
  }

  return selectedDay.value.date.toLocaleDateString('ru-RU', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  })
})

const selectedDayStatus = computed(() => {
  if (!dailyGoal.value) {
    return {
      text: 'цель не задана',
      className: 'neutral',
    }
  }

  const percent = (selectedDayTotals.value.kcal / dailyGoal.value) * 100

  if (percent < 75) {
    return {
      text: 'ниже цели',
      className: 'warning',
    }
  }

  if (percent <= 115) {
    return {
      text: 'в норме',
      className: 'success',
    }
  }

  return {
    text: 'выше цели',
    className: 'danger',
  }
})

const recommendationText = computed(() => {
  if (!dailyGoal.value) {
    return 'Заполните профиль, чтобы получить персональные рекомендации по дневной норме.'
  }

  const kcal = selectedDayTotals.value.kcal

  if (kcal === 0) {
    return 'В этот день пока нет записей. Добавьте фото блюда или внесите продукты вручную.'
  }

  const percent = (kcal / dailyGoal.value) * 100

  if (percent < 75) {
    return 'Рацион ниже дневной цели. Можно добавить полноценный приём пищи или увеличить порцию.'
  }

  if (percent <= 115) {
    return 'День выглядит сбалансированно относительно вашей дневной нормы.'
  }

  return 'Калорийность выше дневной цели. Проверьте размер порций и перекусы.'
})

const insightTitle = computed(() => {
  if (props.isInsightLoading) {
    return 'Анализируем рацион...'
  }

  return props.insight?.title || 'Недостаточно данных для анализа'
})

const insightDescription = computed(() => {
  if (props.isInsightLoading) {
    return 'Система анализирует недельные показатели КБЖУ и подготавливает рекомендацию.'
  }

  return props.insight?.description || 'Добавьте больше записей в дневник питания, чтобы получить рекомендацию по недельному рациону.'
})

const insightRecommendations = computed(() => {
  return props.insight?.recommendations || []
})

const insightType = computed(() => {
  return props.insight?.type || 'empty'
})

const insightFeatureItems = computed(() => {
  const features = props.insight?.features

  if (!features || props.isInsightLoading) {
    return []
  }

  return [
    {
      label: 'Калории',
      value: formatInsightPercent(features.avg_kcal_ratio),
      percent: toInsightProgressPercent(features.avg_kcal_ratio),
    },
    {
      label: 'Белки',
      value: formatInsightPercent(features.avg_protein_ratio),
      percent: toInsightProgressPercent(features.avg_protein_ratio),
    },
    {
      label: 'Жиры',
      value: formatInsightPercent(features.avg_fat_ratio),
      percent: toInsightProgressPercent(features.avg_fat_ratio),
    },
    {
      label: 'Углеводы',
      value: formatInsightPercent(features.avg_carbs_ratio),
      percent: toInsightProgressPercent(features.avg_carbs_ratio),
    },
  ]
})

watch(
  () => weekDays.value.map((day) => day.key).join(','),
  () => {
    const today = getTodayDate()
    const hasToday = weekDays.value.some((day) => day.key === today)

    selectedDayKey.value = hasToday ? today : weekDays.value[0]?.key
  },
  { immediate: true },
)

function getBarHeight(kcal) {
  if (!maxDayKcal.value) {
    return 8
  }

  return Math.max((kcal / maxDayKcal.value) * 100, kcal > 0 ? 10 : 4)
}

function formatInsightPercent(value) {
  return `${Math.round(Number(value || 0) * 100)}%`
}

function toInsightProgressPercent(value) {
  return Math.min(Math.round(Number(value || 0) * 100), 130)
}
</script>