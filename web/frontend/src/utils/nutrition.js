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
      const totals = getAnalysisProductTotals(product)

      acc.kcal += totals.kcal
      acc.protein += totals.protein
      acc.fat += totals.fat
      acc.carbs += totals.carbs

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

export function getAnalysisProductTotals(product) {
  const weight = Number(product.weight_g || 0)

  if (hasPer100gSnapshot(product) && weight > 0) {
    return {
      kcal: calculateTotal(product.kcal_per_100g, weight),
      protein: calculateTotal(product.protein_per_100g, weight),
      fat: calculateTotal(product.fat_per_100g, weight),
      carbs: calculateTotal(product.carbs_per_100g, weight),
    }
  }

  return {
    kcal: Number(product.total_kcal || 0),
    protein: Number(product.total_protein || 0),
    fat: Number(product.total_fat || 0),
    carbs: Number(product.total_carbs || 0),
  }
}

export function getAnalysisProductPer100g(product) {
  return {
    kcal: Number(product.kcal_per_100g || 0),
    protein: Number(product.protein_per_100g || 0),
    fat: Number(product.fat_per_100g || 0),
    carbs: Number(product.carbs_per_100g || 0),
  }
}

function hasPer100gSnapshot(product) {
  return (
    product.kcal_per_100g !== null &&
    product.kcal_per_100g !== undefined &&
    product.protein_per_100g !== null &&
    product.protein_per_100g !== undefined &&
    product.fat_per_100g !== null &&
    product.fat_per_100g !== undefined &&
    product.carbs_per_100g !== null &&
    product.carbs_per_100g !== undefined
  )
}

function calculateTotal(valuePer100g, weight) {
  return Number(valuePer100g || 0) * weight / 100
}