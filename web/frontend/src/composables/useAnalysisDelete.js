import { ref } from 'vue'

import { deleteAnalysis } from '../services/analysisService'
import { getFriendlyErrorMessage } from '../utils/errors'

export function useAnalysisDelete({
  removeAnalysisFromList,
  editingAnalysisId,
  cancelEditProducts,
  toastStore,
}) {
  const analysisToDelete = ref(null)
  const isDeletingAnalysis = ref(false)

  function askDeleteAnalysis(analysis) {
    if (!analysis || analysis.is_pending) {
      return
    }

    analysisToDelete.value = analysis
  }

  function closeDeleteModal() {
    if (isDeletingAnalysis.value) {
      return
    }

    analysisToDelete.value = null
  }

  async function confirmDeleteAnalysis() {
    if (!analysisToDelete.value) {
      return
    }

    const analysis = analysisToDelete.value

    isDeletingAnalysis.value = true

    try {
      await deleteAnalysis(analysis.id)

      if (editingAnalysisId.value === analysis.id) {
        cancelEditProducts()
      }

      removeAnalysisFromList(analysis.id)

      analysisToDelete.value = null

      toastStore.success('Запись удалена.')
    } catch (error) {
      console.error(error)
      toastStore.error(getFriendlyErrorMessage(error))
    } finally {
      isDeletingAnalysis.value = false
    }
  }

  return {
    analysisToDelete,
    isDeletingAnalysis,

    askDeleteAnalysis,
    closeDeleteModal,
    confirmDeleteAnalysis,
  }
}