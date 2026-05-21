import { ref } from 'vue'

import { createManualAnalysis } from '../services/analysisService'
import { getFriendlyErrorMessage } from '../utils/errors'

export function useManualEntry({
  selectedDate,
  upsertAnalysis,
  openMeal,
  cancelEditProducts,
  toastStore,
}) {
  const manualMealType = ref(null)
  const manualProducts = ref([])
  const isSavingManualEntry = ref(false)

  function startManualMealEntry(mealType) {
    manualMealType.value = mealType
    cancelEditProducts()

    manualProducts.value = [
      {
        class_name: '',
        query: '',
        weight_g: 100,
      },
    ]

    openMeal(mealType)
  }

  function cancelManualMealEntry() {
    manualMealType.value = null
    manualProducts.value = []
  }

  async function saveManualMealEntry() {
    if (!manualMealType.value) {
      return
    }

    const validProducts = getValidManualProducts(manualProducts.value)

    if (validProducts.length === 0) {
      toastStore.info('Добавьте хотя бы один продукт и укажите массу.')
      return
    }

    isSavingManualEntry.value = true

    try {
      const createdAnalysis = await createManualAnalysis({
        meal_type: manualMealType.value,
        entry_date: selectedDate.value,
        products: validProducts,
      })

      if (createdAnalysis) {
        upsertAnalysis(createdAnalysis)
        openMeal(createdAnalysis.meal_type)
      }

      cancelManualMealEntry()

      toastStore.success('Ручная запись добавлена.')
    } catch (error) {
      console.error(error)
      toastStore.error(getFriendlyErrorMessage(error))
    } finally {
      isSavingManualEntry.value = false
    }
  }

  function getValidManualProducts(products) {
    return products
      .filter((product) => {
        return product.class_name && Number(product.weight_g) > 0
      })
      .map((product) => ({
        class_name: product.class_name,
        weight_g: Math.round(Number(product.weight_g || 100)),
      }))
  }

  return {
    manualMealType,
    manualProducts,
    isSavingManualEntry,

    startManualMealEntry,
    cancelManualMealEntry,
    saveManualMealEntry,
  }
}