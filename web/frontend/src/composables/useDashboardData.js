import { computed, ref } from 'vue'

import { fetchAnalyses as fetchAnalysesRequest } from '../services/analysisService'
import { fetchProducts as fetchProductsRequest } from '../services/productService'
import { getAnalysisDate } from '../utils/date'
import { createEmptyNutritionTotals, getAnalysisTotals } from '../utils/nutrition'

export function useDashboardData({
  selectedDate,
}) {
  const analyses = ref([])
  const pendingAnalyses = ref([])
  const allProducts = ref([])

  const visibleAnalyses = computed(() => {
    return [
      ...pendingAnalyses.value,
      ...analyses.value,
    ]
  })

  const analysesByMeal = computed(() => {
    const grouped = {
      breakfast: [],
      lunch: [],
      dinner: [],
      snack: [],
    }

    visibleAnalyses.value
      .filter((analysis) => getAnalysisDate(analysis) === selectedDate.value)
      .forEach((analysis) => {
        if (grouped[analysis.meal_type]) {
          grouped[analysis.meal_type].push(analysis)
        }
      })

    return grouped
  })

  const dailyTotals = computed(() => {
    return visibleAnalyses.value
      .filter((analysis) => getAnalysisDate(analysis) === selectedDate.value)
      .reduce(
        (acc, analysis) => {
          if (analysis.is_pending) {
            return acc
          }

          const totals = getAnalysisTotals(analysis)

          acc.kcal += totals.kcal
          acc.protein += totals.protein
          acc.fat += totals.fat
          acc.carbs += totals.carbs

          return acc
        },
        createEmptyNutritionTotals(),
      )
  })

  async function fetchProducts() {
    allProducts.value = await fetchProductsRequest()
  }

  async function fetchAnalyses() {
    analyses.value = await fetchAnalysesRequest()
  }

  async function fetchDashboardData() {
    await Promise.all([
      fetchProducts(),
      fetchAnalyses(),
    ])
  }

  function upsertAnalysis(updatedAnalysis) {
    if (!updatedAnalysis?.id) {
      return
    }

    const exists = analyses.value.some((analysis) => {
      return analysis.id === updatedAnalysis.id
    })

    if (exists) {
      analyses.value = analyses.value.map((analysis) => {
        if (analysis.id !== updatedAnalysis.id) {
          return analysis
        }

        return updatedAnalysis
      })

      return
    }

    analyses.value = [
      updatedAnalysis,
      ...analyses.value,
    ]
  }

  function removeAnalysisFromList(analysisId) {
    analyses.value = analyses.value.filter((analysis) => {
      return analysis.id !== analysisId
    })
  }

  function addPendingAnalysis(pendingAnalysis) {
    pendingAnalyses.value.unshift(pendingAnalysis)
  }

  function updatePendingAnalysis(id, patch) {
    pendingAnalyses.value = pendingAnalyses.value.map((item) => {
      if (item.id !== id) {
        return item
      }

      return {
        ...item,
        ...patch,
      }
    })
  }

  function removePendingAnalysis(id) {
    pendingAnalyses.value = pendingAnalyses.value.filter((item) => {
      return item.id !== id
    })
  }

  function getMealTotals(mealType) {
    return analysesByMeal.value[mealType].reduce(
      (acc, analysis) => {
        if (analysis.is_pending) {
          return acc
        }

        const totals = getAnalysisTotals(analysis)

        acc.kcal += totals.kcal
        acc.protein += totals.protein
        acc.fat += totals.fat
        acc.carbs += totals.carbs

        return acc
      },
      createEmptyNutritionTotals(),
    )
  }

  return {
    analyses,
    pendingAnalyses,
    allProducts,
    visibleAnalyses,
    analysesByMeal,
    dailyTotals,

    fetchProducts,
    fetchAnalyses,
    fetchDashboardData,

    upsertAnalysis,
    removeAnalysisFromList,

    addPendingAnalysis,
    updatePendingAnalysis,
    removePendingAnalysis,

    getMealTotals,
  }
}