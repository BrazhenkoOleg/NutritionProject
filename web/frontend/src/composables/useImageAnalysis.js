import { ref } from 'vue'

import { analyzeImage } from '../services/analysisService'
import { compressImage, isAllowedImageType } from '../utils/image'
import { getFriendlyErrorMessage } from '../utils/errors'

const ML_COOLDOWN_MS = 8000
const ML_WARMUP_TIMEOUT_MS = 10000

let mlWarmupPromise = null

export function useImageAnalysis({
  selectedDate,
  upsertAnalysis,
  addPendingAnalysis,
  updatePendingAnalysis,
  removePendingAnalysis,
  openMeal,
  startEditProducts,
  toastStore,
}) {
  const uploadMealType = ref(null)
  const isAnalyzingImage = ref(false)

  const mlRequestInProgress = ref(false)
  const mlCooldownUntil = ref(null)

  const mealUploadFiles = ref({
    breakfast: null,
    lunch: null,
    dinner: null,
    snack: null,
  })

  const mealPreviewUrls = ref({
    breakfast: null,
    lunch: null,
    dinner: null,
    snack: null,
  })

  function handleFileChange(event, mealType) {
    const file = event.target.files?.[0]

    if (!file) {
      return
    }

    if (!isAllowedImageType(file.type)) {
      toastStore.error('Поддерживаются только изображения JPG, PNG и WEBP.')
      event.target.value = ''
      return
    }

    revokeMealPreviewUrl(mealType)

    mealUploadFiles.value[mealType] = file
    mealPreviewUrls.value[mealType] = URL.createObjectURL(file)

    openMeal(mealType)

    warmUpMlOnly({
      silent: true,
      timeoutMs: ML_WARMUP_TIMEOUT_MS,
    })
  }

  async function analyzeMealImage(mealType) {
    const file = mealUploadFiles.value[mealType]

    if (!file) {
      toastStore.info('Сначала выберите изображение блюда.')
      return
    }

    if (mlRequestInProgress.value) {
      toastStore.info('Дождитесь завершения текущего анализа.')
      return
    }

    if (isMlCoolingDown()) {
      toastStore.info(`Следующий анализ будет доступен через ${getMlCooldownSeconds()} сек.`)
      return
    }

    const previewUrl = mealPreviewUrls.value[mealType]
    let pendingId = null

    isAnalyzingImage.value = true
    uploadMealType.value = mealType
    mlRequestInProgress.value = true

    try {
      await warmUpMlOnly({
        silent: false,
        timeoutMs: ML_WARMUP_TIMEOUT_MS,
      })

      const pendingAnalysis = createPendingAnalysis(mealType, previewUrl)

      pendingId = pendingAnalysis.id
      addPendingAnalysis(pendingAnalysis)

      openMeal(mealType)

      updatePendingAnalysis(pendingId, {
        progress_step: 'compressing',
      })

      const compressedFile = await compressImage(file, 1024, 0.78)

      updatePendingAnalysis(pendingId, {
        progress_step: 'uploading',
      })

      const formData = new FormData()
      formData.append('image', compressedFile)
      formData.append('meal_type', mealType)
      formData.append('entry_date', selectedDate.value)

      updatePendingAnalysis(pendingId, {
        progress_step: 'recognizing',
      })

      const createdAnalysis = await analyzeImage(formData)

      updatePendingAnalysis(pendingId, {
        progress_step: 'finalizing',
      })

      removePendingAnalysis(pendingId)
      pendingId = null

      if (createdAnalysis) {
        upsertAnalysis(createdAnalysis)
      }

      clearMealUpload(mealType)

      if (createdAnalysis) {
        openMeal(mealType)
        startEditProducts(createdAnalysis)
        toastStore.success('Фото распознано. Уточните вес порции.')
      } else {
        toastStore.success('Фото добавлено в дневник.')
      }
    } catch (error) {
      console.error(error)

      if (pendingId) {
        removePendingAnalysis(pendingId)
      }

      toastStore.error(getFriendlyErrorMessage(error))
    } finally {
      isAnalyzingImage.value = false
      uploadMealType.value = null
      mlRequestInProgress.value = false
      startMlCooldown()
    }
  }

  function createPendingAnalysis(mealType, previewUrl) {
    return {
      id: `pending-${Date.now()}-${Math.random()}`,
      meal_type: mealType,
      entry_date: selectedDate.value,
      image_url: previewUrl,
      status: 'processing',
      products: [],
      created_at: new Date().toISOString(),
      is_pending: true,
      progress_step: 'preparing',
    }
  }

  function warmUpMlOnly(options = {}) {
    const {
      silent = false,
      timeoutMs = ML_WARMUP_TIMEOUT_MS,
    } = options

    const mlUrl = import.meta.env.VITE_ML_URL

    if (!mlUrl) {
      if (silent) {
        return Promise.resolve(null)
      }

      return Promise.reject(new Error('ML URL is not configured'))
    }

    if (!mlWarmupPromise) {
      mlWarmupPromise = requestMlWarmup(mlUrl, timeoutMs)
        .finally(() => {
          window.setTimeout(() => {
            mlWarmupPromise = null
          }, 10000)
        })
    }

    if (silent) {
      return mlWarmupPromise.catch((error) => {
        console.warn('ML warm-up failed', error)
        return null
      })
    }

    return mlWarmupPromise
  }

  async function requestMlWarmup(mlUrl, timeoutMs) {
    const controller = new AbortController()

    const timeoutId = window.setTimeout(() => {
      controller.abort()
    }, timeoutMs)

    try {
      const response = await fetch(`${mlUrl.replace(/\/$/, '')}/warmup`, {
        method: 'POST',
        signal: controller.signal,
      })

      if (!response.ok) {
        throw new Error(`ML warm-up failed with status ${response.status}`)
      }

      return response
    } finally {
      window.clearTimeout(timeoutId)
    }
  }

  function clearMealUpload(mealType) {
    mealUploadFiles.value[mealType] = null
    revokeMealPreviewUrl(mealType)
  }

  function revokeMealPreviewUrl(mealType) {
    if (!mealPreviewUrls.value[mealType]) {
      return
    }

    URL.revokeObjectURL(mealPreviewUrls.value[mealType])
    mealPreviewUrls.value[mealType] = null
  }

  function isMlCoolingDown() {
    if (!mlCooldownUntil.value) {
      return false
    }

    return Date.now() < mlCooldownUntil.value
  }

  function getMlCooldownSeconds() {
    if (!mlCooldownUntil.value) {
      return 0
    }

    return Math.max(Math.ceil((mlCooldownUntil.value - Date.now()) / 1000), 0)
  }

  function startMlCooldown() {
    mlCooldownUntil.value = Date.now() + ML_COOLDOWN_MS
  }

  return {
    uploadMealType,
    isAnalyzingImage,

    mealUploadFiles,
    mealPreviewUrls,

    handleFileChange,
    analyzeMealImage,
    warmUpMlOnly,
  }
}