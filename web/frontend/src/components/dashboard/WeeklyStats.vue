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
        <strong>{{ Math.round(weeklyTotals.kcal) }}</strong>
        <span>ккал за неделю</span>
      </div>

      <div>
        <strong>{{ Math.round(weeklyAverageKcal) }}</strong>
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
        <strong>{{ Math.round(day.totals.kcal) }}</strong>
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
          <strong>{{ Math.round(selectedDayTotals.kcal) }}</strong>
          <span>ккал</span>
        </div>

        <div>
          <strong>{{ formatNumber(selectedDayTotals.protein) }}</strong>
          <span>белки, г</span>
        </div>

        <div>
          <strong>{{ formatNumber(selectedDayTotals.fat) }}</strong>
          <span>жиры, г</span>
        </div>

        <div>
          <strong>{{ formatNumber(selectedDayTotals.carbs) }}</strong>
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

const props = defineProps({
  analyses: {
    type: Array,
    default: () => [],
  },
  user: {
    type: Object,
    default: null,
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
    const dayAnalyses = props.analyses.filter((analysis) => getAnalysisDate(analysis) === key)
    const totals = getTotalsFromAnalyses(dayAnalyses)

    return {
      date,
      key,
      label: date.toLocaleDateString('ru-RU', {
        weekday: 'short',
      }),
      analyses: dayAnalyses,
      totals,
    }
  })
})

const selectedDay = computed(() => {
  return weekDays.value.find((day) => day.key === selectedDayKey.value) || weekDays.value[0]
})

const selectedDayAnalyses = computed(() => selectedDay.value?.analyses || [])

const selectedDayTotals = computed(() => {
  return selectedDay.value?.totals || {
    kcal: 0,
    protein: 0,
    fat: 0,
    carbs: 0,
  }
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
    {
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    },
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

  return Math.min(Math.round((weeklyTotals.value.kcal / weeklyGoal.value) * 100), 999)
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

watch(
  () => weekDays.value.map((day) => day.key).join(','),
  () => {
    const today = getTodayDate()
    const hasToday = weekDays.value.some((day) => day.key === today)

    selectedDayKey.value = hasToday ? today : weekDays.value[0]?.key
  },
  { immediate: true },
)

function getMonday(date) {
  const result = new Date(date)
  const day = result.getDay()
  const diff = day === 0 ? -6 : 1 - day

  result.setDate(result.getDate() + diff)
  result.setHours(0, 0, 0, 0)

  return result
}

function getTodayDate() {
  return toDateInputValue(new Date())
}

function toDateInputValue(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function getAnalysisDate(analysis) {
  if (analysis.entry_date) {
    return String(analysis.entry_date).slice(0, 10)
  }

  if (!analysis.created_at) {
    return ''
  }

  return toDateInputValue(new Date(analysis.created_at))
}

function getTotalsFromAnalyses(items) {
  return items.reduce(
    (acc, analysis) => {
      const totals = getAnalysisTotals(analysis)

      acc.kcal += totals.kcal
      acc.protein += totals.protein
      acc.fat += totals.fat
      acc.carbs += totals.carbs

      return acc
    },
    {
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    },
  )
}

function getAnalysisTotals(analysis) {
  if (analysis.totals) {
    return {
      kcal: Number(analysis.totals.kcal || 0),
      protein: Number(analysis.totals.protein || 0),
      fat: Number(analysis.totals.fat || 0),
      carbs: Number(analysis.totals.carbs || 0),
    }
  }

  if (!analysis.products?.length) {
    return {
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    }
  }

  return analysis.products.reduce(
    (acc, product) => {
      acc.kcal += Number(product.total_kcal || 0)
      acc.protein += Number(product.total_protein || 0)
      acc.fat += Number(product.total_fat || 0)
      acc.carbs += Number(product.total_carbs || 0)

      return acc
    },
    {
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    },
  )
}

function getBarHeight(kcal) {
  if (!maxDayKcal.value) {
    return 8
  }

  return Math.max((kcal / maxDayKcal.value) * 100, kcal > 0 ? 10 : 4)
}

function formatNumber(value) {
  return Number(value || 0).toFixed(1)
}

function formatShortDate(date) {
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
  })
}
</script>