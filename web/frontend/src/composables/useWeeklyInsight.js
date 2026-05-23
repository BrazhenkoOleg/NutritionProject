import { ref } from 'vue'

import { fetchWeeklyNutritionInsight } from '../services/nutritionInsightService'
import { getFriendlyErrorMessage } from '../utils/errors'

export function useWeeklyInsight({
  toastStore = null,
} = {}) {
  const weeklyInsight = ref(null)
  const isWeeklyInsightLoading = ref(false)

  async function fetchWeeklyInsight(date) {
    isWeeklyInsightLoading.value = true

    try {
      weeklyInsight.value = await fetchWeeklyNutritionInsight(date)
    } catch (error) {
      console.error(error)

      weeklyInsight.value = null

      if (toastStore) {
        toastStore.error(getFriendlyErrorMessage(error))
      }
    } finally {
      isWeeklyInsightLoading.value = false
    }
  }

  function clearWeeklyInsight() {
    weeklyInsight.value = null
  }

  return {
    weeklyInsight,
    isWeeklyInsightLoading,
    fetchWeeklyInsight,
    clearWeeklyInsight,
  }
}