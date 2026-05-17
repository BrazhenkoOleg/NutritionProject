<script setup>
import { computed, ref } from 'vue'

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

const dayLabels = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

const selectedDayKey = ref(null)

const weekDays = computed(() => {
  const startOfWeek = getStartOfWeek(new Date())

  return dayLabels.map((label, index) => {
    const date = addDays(startOfWeek, index)
    const dateKey = toDateInputValue(date)

    const dayAnalyses = props.analyses.filter((analysis) => {
      return getAnalysisDate(analysis) === dateKey
    })

    return {
      label,
      date,
      dateKey,
      analyses: dayAnalyses,
      totals: getDayTotals(dayAnalyses),
    }
  })
})

const selectedDay = computed(() => {
  if (!weekDays.value.length) {
    return null
  }

  if (selectedDayKey.value) {
    return weekDays.value.find((day) => day.dateKey === selectedDayKey.value) || weekDays.value[0]
  }

  const todayKey = toDateInputValue(new Date())
  return weekDays.value.find((day) => day.dateKey === todayKey) || weekDays.value[0]
})

const weekTotals = computed(() => {
  return weekDays.value.reduce(
    (totals, day) => {
      totals.kcal += day.totals.kcal
      totals.protein += day.totals.protein
      totals.fat += day.totals.fat
      totals.carbs += day.totals.carbs

      return totals
    },
    {
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    },
  )
})

const weekAverage = computed(() => {
  return {
    kcal: weekTotals.value.kcal / 7,
    protein: weekTotals.value.protein / 7,
    fat: weekTotals.value.fat / 7,
    carbs: weekTotals.value.carbs / 7,
  }
})

const maxKcal = computed(() => {
  const values = weekDays.value.map((day) => day.totals.kcal)
  const max = Math.max(...values)

  return max > 0 ? max : 1
})

const weekRecordsCount = computed(() => {
  return weekDays.value.reduce((sum, day) => sum + day.analyses.length, 0)
})

function formatNumber(value) {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  return Number(value).toFixed(2)
}

function getProductWeight(product) {
  return Number(product.weight_g || 100)
}

function getProductKcal(product) {
  if (product.total_kcal !== undefined && product.total_kcal !== null) {
    return Number(product.total_kcal)
  }

  return (Number(product.kcal_per_100g || 0) * getProductWeight(product)) / 100
}

function getProductProtein(product) {
  if (product.total_protein !== undefined && product.total_protein !== null) {
    return Number(product.total_protein)
  }

  return (Number(product.protein_per_100g || 0) * getProductWeight(product)) / 100
}

function getProductFat(product) {
  if (product.total_fat !== undefined && product.total_fat !== null) {
    return Number(product.total_fat)
  }

  return (Number(product.fat_per_100g || 0) * getProductWeight(product)) / 100
}

function getProductCarbs(product) {
  if (product.total_carbs !== undefined && product.total_carbs !== null) {
    return Number(product.total_carbs)
  }

  return (Number(product.carbs_per_100g || 0) * getProductWeight(product)) / 100
}

function getAnalysisDate(analysis) {
  if (analysis.entry_date) {
    return String(analysis.entry_date).slice(0, 10)
  }

  if (!analysis.created_at) {
    return ''
  }

  const date = new Date(analysis.created_at)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function getStartOfWeek(date = new Date()) {
  const result = new Date(date)
  const day = result.getDay()
  const diff = day === 0 ? -6 : 1 - day

  result.setDate(result.getDate() + diff)
  result.setHours(0, 0, 0, 0)

  return result
}

function toDateInputValue(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function addDays(date, count) {
  const result = new Date(date)
  result.setDate(result.getDate() + count)

  return result
}

function getDayTotals(analyses) {
  return analyses.reduce(
    (totals, analysis) => {
      const products = analysis.products || []

      products.forEach((product) => {
        totals.kcal += getProductKcal(product)
        totals.protein += getProductProtein(product)
        totals.fat += getProductFat(product)
        totals.carbs += getProductCarbs(product)
      })

      return totals
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
  return Math.max((Number(kcal || 0) / maxKcal.value) * 100, 4)
}

function selectDay(day) {
  selectedDayKey.value = day.dateKey
}

function isSelectedDay(day) {
  return selectedDay.value?.dateKey === day.dateKey
}

function formatDayDate(date) {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

function getWeeklyRecommendation() {
  const user = props.user

  if (!user?.profile_completed) {
    return 'Заполните профиль, чтобы система могла сравнивать недельный рацион с вашей нормой.'
  }

  const kcalGoal = Number(user.daily_kcal_goal || 0)
  const proteinGoal = Number(user.daily_protein_goal || 0)

  if (kcalGoal && weekAverage.value.kcal > kcalGoal * 1.1) {
    return 'Средняя калорийность за неделю выше вашей нормы. Стоит обратить внимание на размер порций и калорийные продукты.'
  }

  if (kcalGoal && weekAverage.value.kcal < kcalGoal * 0.8) {
    return 'Средняя калорийность за неделю заметно ниже нормы. Возможно, рацион недостаточно питательный.'
  }

  if (proteinGoal && weekAverage.value.protein < proteinGoal * 0.8) {
    return 'В среднем за неделю белка недостаточно. Добавьте больше белковых продуктов в рацион.'
  }

  return 'Средние показатели за неделю близки к рассчитанной норме.'
}
</script>

<template>
  <section class="weekly-stats">
    <div class="weekly-stats-header">
      <div>
        <h3>📊 Статистика за неделю</h3>

        <p>
          Нажмите на столбец, чтобы посмотреть подробности за выбранный день.
        </p>
      </div>
    </div>

    <div class="weekly-summary-grid">
      <div>
        <span>Итого ккал</span>
        <strong>{{ Math.round(weekTotals.kcal || 0) }}</strong>
      </div>

      <div>
        <span>Среднее ккал/день</span>
        <strong>{{ Math.round(weekAverage.kcal || 0) }}</strong>
      </div>

      <div>
        <span>Средний белок</span>
        <strong>{{ formatNumber(weekAverage.protein) }} г</strong>
      </div>

      <div>
        <span>Записей</span>
        <strong>{{ weekRecordsCount }}</strong>
      </div>
    </div>

    <div class="weekly-chart">
      <button
        v-for="day in weekDays"
        :key="day.dateKey"
        type="button"
        class="weekly-chart-day"
        :class="{ active: isSelectedDay(day) }"
        @click="selectDay(day)"
      >
        <div class="weekly-chart-bar-wrap">
          <div
            class="weekly-chart-bar"
            :style="{ height: `${getBarHeight(day.totals.kcal)}%` }"
          ></div>
        </div>

        <strong>{{ day.label }}</strong>
        <span>{{ formatNumber(day.totals.kcal) }}</span>
      </button>
    </div>

    <div
      v-if="selectedDay"
      class="weekly-selected-day"
    >
      <div class="weekly-selected-day-header">
        <div>
          <h4>{{ selectedDay.label }}, {{ formatDayDate(selectedDay.date) }}</h4>
          <span>{{ selectedDay.analyses.length }} записей за день</span>
        </div>

        <div class="weekly-selected-kcal">
          {{ formatNumber(selectedDay.totals.kcal) }} ккал
        </div>
      </div>

      <div class="weekly-selected-kbju">
        <div>
          <strong>{{ formatNumber(selectedDay.totals.protein) }}</strong>
          <span>белки, г</span>
        </div>

        <div>
          <strong>{{ formatNumber(selectedDay.totals.fat) }}</strong>
          <span>жиры, г</span>
        </div>

        <div>
          <strong>{{ formatNumber(selectedDay.totals.carbs) }}</strong>
          <span>углеводы, г</span>
        </div>
      </div>

      <p
        v-if="selectedDay.analyses.length === 0"
        class="weekly-selected-empty"
      >
        За этот день пока нет записей.
      </p>
    </div>

    <div class="weekly-recommendation">
      {{ getWeeklyRecommendation() }}
    </div>
  </section>
</template>