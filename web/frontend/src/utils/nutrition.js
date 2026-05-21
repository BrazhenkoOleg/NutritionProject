export function createEmptyNutritionTotals() {
  return {
    kcal: 0,
    protein: 0,
    fat: 0,
    carbs: 0,
  }
}

export function getAnalysisTotals(analysis) {
  if (analysis.totals) {
    return {
      kcal: Number(analysis.totals.kcal || 0),
      protein: Number(analysis.totals.protein || 0),
      fat: Number(analysis.totals.fat || 0),
      carbs: Number(analysis.totals.carbs || 0),
    }
  }

  if (!analysis.products?.length) {
    return createEmptyNutritionTotals()
  }

  return analysis.products.reduce(
    (acc, product) => {
      acc.kcal += Number(product.total_kcal || 0)
      acc.protein += Number(product.total_protein || 0)
      acc.fat += Number(product.total_fat || 0)
      acc.carbs += Number(product.total_carbs || 0)

      return acc
    },
    createEmptyNutritionTotals(),
  )
}

export function getTotalsFromAnalyses(analyses) {
  return analyses.reduce(
    (acc, analysis) => {
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