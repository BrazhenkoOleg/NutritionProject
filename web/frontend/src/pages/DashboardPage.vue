<template>
  <div class="page">
    <AppHeader
      :theme="theme"
      @logout="logout"
      @toggle-theme="toggleTheme"
    />

    <main class="dashboard-page">
      <div
        v-if="isInitialLoading"
        class="dashboard-skeleton"
      >
        <div class="skeleton-hero"></div>

        <div class="skeleton-grid">
          <div></div>
          <div></div>
          <div></div>
        </div>
      </div>

      <template v-else>
        <section class="dashboard-hero">
          <div class="dashboard-hero-copy">
            <span class="eyebrow">
              <IconResolver
                name="Sparkles"
                :size="16"
              />
              AI-дневник питания
            </span>

            <h1>Анализируйте рацион по фото, а не вручную</h1>

            <p>
              NutriVision распознаёт продукты на изображении, помогает уточнить вес порции
              и автоматически считает калории, белки, жиры и углеводы за день.
            </p>

            <div class="hero-pills">
              <span>ONNX Runtime</span>
              <span>YOLOv11</span>
              <span>КБЖУ-дневник</span>
            </div>
          </div>

          <div class="hero-process-card">
            <div class="process-step">
              <IconResolver name="Camera" />
              <span>Фото блюда</span>
            </div>

            <div class="process-line"></div>

            <div class="process-step">
              <IconResolver name="ScanSearch" />
              <span>AI-распознавание</span>
            </div>

            <div class="process-line"></div>

            <div class="process-step">
              <IconResolver name="BarChart3" />
              <span>КБЖУ и дневник</span>
            </div>
          </div>
        </section>

        <div
          v-if="isWarmingUp"
          class="warmup-banner"
        >
          <IconResolver
            name="Loader2"
            :size="18"
            class="spin-icon"
          />

          <div>
            <strong>Подготавливаем сервисы</strong>
            <span>На бесплатном тарифе первый запуск может занять больше времени.</span>
          </div>
        </div>

        <section class="dashboard-toolbar card">
          <div class="date-control">
            <span class="section-label">Дата дневника</span>
            <input
              v-model="selectedDate"
              type="date"
              class="date-input"
            />
          </div>

          <button
            type="button"
            class="light-button"
            @click="goToday"
          >
            Сегодня
          </button>
        </section>

        <DailySummary
          :daily-totals="dailyTotals"
          :user="authStore.user"
          @open-profile="router.push('/profile')"
        />

        <WeeklyStats
          :analyses="analyses"
          :user="authStore.user"
        />

        <div class="meals-grid">
          <MealSection
            v-for="meal in mealTypes"
            :key="meal.value"
            :meal="meal"
            :analyses="analysesByMeal[meal.value]"
            :totals="getMealTotals(meal.value)"
            :collapsed="collapsedMeals[meal.value]"
            :upload-file="mealUploadFiles[meal.value]"
            :preview-url="mealPreviewUrls[meal.value]"
            :is-loading="isLoading"
            :upload-meal-type="uploadMealType"
            :manual-meal-type="manualMealType"
            :manual-products="manualProducts"
            :editing-analysis-id="editingAnalysisId"
            :editable-products="editableProducts"
            :all-products="allProducts"
            @toggle="toggleMeal"
            @file-change="handleFileChange"
            @analyze="analyzeMealImage"
            @start-manual-entry="startManualMealEntry"
            @cancel-manual-entry="cancelManualMealEntry"
            @save-manual-entry="saveManualMealEntry"
            @edit-products="startEditProducts"
            @delete-analysis="askDeleteAnalysis"
            @cancel-edit="cancelEditProducts"
            @save-edit="saveEditedProducts"
            @update:editable-products="editableProducts = $event"
            @update:manual-products="manualProducts = $event"
          />
        </div>
      </template>
    </main>

    <AppFooter />

    <div
      v-if="analysisToDelete"
      class="modal-overlay"
      @click.self="closeDeleteModal"
    >
      <div class="confirm-modal">
        <div class="confirm-modal-icon">
          <IconResolver
            name="Trash2"
            :size="22"
          />
        </div>

        <div class="confirm-modal-content">
          <h2>Удалить запись?</h2>

          <p>
            Запись анализа #{{ analysisToDelete.id }} будет удалена из дневника питания.
            Это действие нельзя отменить.
          </p>
        </div>

        <div class="confirm-modal-actions">
          <button
            type="button"
            class="light-button"
            :disabled="isLoading"
            @click="closeDeleteModal"
          >
            Отмена
          </button>

          <button
            type="button"
            class="danger-button"
            :disabled="isLoading"
            @click="confirmDeleteAnalysis"
          >
            {{ isLoading ? 'Удаляем...' : 'Удалить запись' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

import AppHeader from '../components/layout/AppHeader.vue'
import AppFooter from '../components/layout/AppFooter.vue'
import IconResolver from '../components/ui/IconResolver.vue'

import DailySummary from '../components/dashboard/DailySummary.vue'
import WeeklyStats from '../components/dashboard/WeeklyStats.vue'
import MealSection from '../components/dashboard/MealSection.vue'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const isInitialLoading = ref(true)
const isWarmingUp = ref(false)
const isLoading = ref(false)

const analyses = ref([])
const pendingAnalyses = ref([])
const allProducts = ref([])

const selectedDate = ref(getTodayDate())

const uploadMealType = ref(null)
const editingAnalysisId = ref(null)
const editableProducts = ref([])

const manualMealType = ref(null)
const manualProducts = ref([])

const analysisToDelete = ref(null)

const mlRequestInProgress = ref(false)
const mlCooldownUntil = ref(null)
const ML_COOLDOWN_MS = 8000

let mlWarmupPromise = null

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

const collapsedMeals = ref({
  breakfast: true,
  lunch: true,
  dinner: true,
  snack: true,
})

const theme = ref(localStorage.getItem('theme') || 'light')

const mealTypes = [
  {
    value: 'breakfast',
    label: 'Завтрак',
    description: 'Первый приём пищи и старт дневной нормы',
    icon: 'Coffee',
  },
  {
    value: 'lunch',
    label: 'Обед',
    description: 'Основной приём пищи в течение дня',
    icon: 'Utensils',
  },
  {
    value: 'dinner',
    label: 'Ужин',
    description: 'Вечерний приём пищи и баланс дня',
    icon: 'Moon',
  },
  {
    value: 'snack',
    label: 'Перекусы',
    description: 'Дополнительные продукты и небольшие порции',
    icon: 'Apple',
  },
]

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
      {
        kcal: 0,
        protein: 0,
        fat: 0,
        carbs: 0,
      },
    )
})

watch(selectedDate, () => {
  editingAnalysisId.value = null
  editableProducts.value = []
  manualMealType.value = null
  manualProducts.value = []
})

onMounted(async () => {
  applyTheme(theme.value)

  try {
    await warmUpServices()

    await Promise.all([
      fetchProducts(),
      fetchAnalyses(),
    ])
  } finally {
    isInitialLoading.value = false
  }
})

function getTodayDate() {
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
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

function applyTheme(value) {
  theme.value = value
  localStorage.setItem('theme', value)
  document.documentElement.setAttribute('data-theme', value)
}

function toggleTheme() {
  applyTheme(theme.value === 'light' ? 'dark' : 'light')
}

function goToday() {
  selectedDate.value = getTodayDate()
}

function toggleMeal(mealType) {
  collapsedMeals.value[mealType] = !collapsedMeals.value[mealType]
}

function openMeal(mealType) {
  collapsedMeals.value[mealType] = false
}

async function logout() {
  await authStore.logout()

  if (router.currentRoute.value.path !== '/login') {
    router.push('/login')
  }
}

async function fetchProducts() {
  const response = await api.get('/products')
  allProducts.value = response.data.data || []
}

async function fetchAnalyses() {
  const response = await api.get('/analyses')
  analyses.value = response.data.data || []
}

async function warmUpServices() {
  isWarmingUp.value = true

  try {
    await api.get('/health')
  } catch (error) {
    console.warn('Backend warm-up failed', error)
  }

  try {
    await warmUpMlOnly()
  } finally {
    isWarmingUp.value = false
  }
}

function warmUpMlOnly() {
  const mlUrl = import.meta.env.VITE_ML_URL

  if (!mlUrl) {
    return null
  }

  if (!mlWarmupPromise) {
    mlWarmupPromise = fetch(`${mlUrl}/health`)
      .catch((error) => {
        console.warn('ML warm-up failed', error)
      })
      .finally(() => {
        setTimeout(() => {
          mlWarmupPromise = null
        }, 10000)
      })
  }

  return mlWarmupPromise
}

function handleFileChange(event, mealType) {
  const file = event.target.files?.[0]

  if (!file) {
    return
  }

  const allowedTypes = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/webp',
  ]

  if (!allowedTypes.includes(file.type)) {
    toastStore.error('Поддерживаются только изображения JPG, PNG и WEBP.')
    event.target.value = ''
    return
  }

  if (mealPreviewUrls.value[mealType]) {
    URL.revokeObjectURL(mealPreviewUrls.value[mealType])
  }

  mealUploadFiles.value[mealType] = file
  mealPreviewUrls.value[mealType] = URL.createObjectURL(file)

  openMeal(mealType)
  warmUpMlOnly()
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
  const pendingId = addPendingAnalysis(mealType, previewUrl)

  openMeal(mealType)

  isLoading.value = true
  uploadMealType.value = mealType
  mlRequestInProgress.value = true

  try {
    updatePendingAnalysis(pendingId, {
      progress_step: 'compressing',
    })

    const compressedFile = await compressImage(file, 1024, 0.78)

    updatePendingAnalysis(pendingId, {
      progress_step: 'uploading',
    })

    await warmUpMlOnly()

    const formData = new FormData()
    formData.append('image', compressedFile)
    formData.append('meal_type', mealType)
    formData.append('entry_date', selectedDate.value)

    updatePendingAnalysis(pendingId, {
      progress_step: 'recognizing',
    })

    const response = await api.post('/analyze', formData)
    const createdAnalysis = response.data.data

    updatePendingAnalysis(pendingId, {
      progress_step: 'finalizing',
    })

    await fetchAnalyses()

    removePendingAnalysis(pendingId)

    mealUploadFiles.value[mealType] = null

    if (mealPreviewUrls.value[mealType]) {
      URL.revokeObjectURL(mealPreviewUrls.value[mealType])
      mealPreviewUrls.value[mealType] = null
    }

    const freshAnalysis = analyses.value.find((item) => item.id === createdAnalysis?.id)

    if (freshAnalysis) {
      openMeal(mealType)
      startEditProducts(freshAnalysis)
      toastStore.success('Фото распознано. Уточните вес порции.')
    } else {
      toastStore.success('Фото добавлено в дневник.')
    }
  } catch (error) {
    console.error(error)

    removePendingAnalysis(pendingId)

    toastStore.error(getFriendlyErrorMessage(error))
  } finally {
    isLoading.value = false
    uploadMealType.value = null
    mlRequestInProgress.value = false
    startMlCooldown()
  }
}

function compressImage(file, maxSize = 1024, quality = 0.78) {
  return new Promise((resolve, reject) => {
    const allowedTypes = [
      'image/jpeg',
      'image/jpg',
      'image/png',
      'image/webp',
    ]

    if (!allowedTypes.includes(file.type)) {
      reject(new Error('Поддерживаются только изображения JPG, PNG и WEBP'))
      return
    }

    const reader = new FileReader()

    reader.onload = (event) => {
      const image = new Image()

      image.onload = () => {
        const canvas = document.createElement('canvas')

        let width = image.width
        let height = image.height

        if (width > height && width > maxSize) {
          height = Math.round((height * maxSize) / width)
          width = maxSize
        } else if (height > maxSize) {
          width = Math.round((width * maxSize) / height)
          height = maxSize
        }

        canvas.width = width
        canvas.height = height

        const context = canvas.getContext('2d')

        if (!context) {
          reject(new Error('Не удалось подготовить изображение'))
          return
        }

        context.drawImage(image, 0, 0, width, height)

        canvas.toBlob(
          (blob) => {
            if (!blob) {
              reject(new Error('Не удалось сжать изображение'))
              return
            }

            const compressedFile = new File(
              [blob],
              getCompressedFileName(file.name),
              {
                type: 'image/jpeg',
                lastModified: Date.now(),
              },
            )

            resolve(compressedFile)
          },
          'image/jpeg',
          quality,
        )
      }

      image.onerror = () => {
        reject(new Error('Не удалось прочитать изображение. Выберите JPG, PNG или WEBP.'))
      }

      image.src = event.target.result
    }

    reader.onerror = () => {
      reject(new Error('Не удалось загрузить файл'))
    }

    reader.readAsDataURL(file)
  })
}

function getCompressedFileName(originalName) {
  const nameWithoutExtension = originalName.replace(/\.[^/.]+$/, '')

  return `${nameWithoutExtension || 'image'}_compressed.jpg`
}

function addPendingAnalysis(mealType, previewUrl) {
  const pending = {
    id: `pending-${Date.now()}`,
    meal_type: mealType,
    entry_date: selectedDate.value,
    image_url: previewUrl,
    status: 'processing',
    products: [],
    created_at: new Date().toISOString(),
    is_pending: true,
    progress_step: 'preparing',
  }

  pendingAnalyses.value.unshift(pending)

  return pending.id
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
  pendingAnalyses.value = pendingAnalyses.value.filter((item) => item.id !== id)
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

function getFriendlyErrorMessage(error) {
  const data = error.response?.data

  if (!data) {
    return 'Не удалось выполнить запрос. Проверьте подключение к интернету.'
  }

  if (data.message === 'ML service busy') {
    return data.user_message || 'AI-сервис занят. Повторите попытку через несколько секунд.'
  }

  if (data.message === 'ML service connection error') {
    return 'Сервис распознавания запускается. Подождите немного и повторите попытку.'
  }

  if (data.message === 'ML service error') {
    return data.user_message || 'Сервис распознавания временно недоступен. Запись не создана, попробуйте позже.'
  }

  if (data.errors) {
    const firstError = Object.values(data.errors).flat()[0]

    if (firstError) {
      return stripHtml(firstError)
    }
  }

  if (data.message) {
    return stripHtml(data.message)
  }

  return 'Произошла ошибка при анализе изображения. Попробуйте ещё раз.'
}

function stripHtml(value) {
  if (!value) {
    return ''
  }

  return String(value)
    .replace(/<[^>]*>/g, '')
    .replace(/\s+/g, ' ')
    .trim()
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
    {
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    },
  )
}

function getAnalysisTotals(analysis) {
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

function startManualMealEntry(mealType) {
  manualMealType.value = mealType
  editingAnalysisId.value = null
  editableProducts.value = []

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

  const validProducts = manualProducts.value.filter((product) => {
    return product.class_name && Number(product.weight_g) > 0
  })

  if (validProducts.length === 0) {
    toastStore.info('Добавьте хотя бы один продукт и укажите массу.')
    return
  }

  isLoading.value = true

  try {
    await api.post('/analyses/manual', {
      meal_type: manualMealType.value,
      entry_date: selectedDate.value,
      products: validProducts.map((product) => ({
        class_name: product.class_name,
        weight_g: Number(product.weight_g),
      })),
    })

    manualMealType.value = null
    manualProducts.value = []

    await fetchAnalyses()

    toastStore.success('Ручная запись добавлена.')
  } catch (error) {
    console.error(error)
    toastStore.error(getFriendlyErrorMessage(error))
  } finally {
    isLoading.value = false
  }
}

function startEditProducts(analysis) {
  editingAnalysisId.value = analysis.id

  editableProducts.value = (analysis.products || []).map((product) => ({
    class_name: product.class_name,
    query: product.name_ru || product.class_name,
    weight_g: product.weight_g || 100,
  }))
}

function cancelEditProducts() {
  editingAnalysisId.value = null
  editableProducts.value = []
}

async function saveEditedProducts() {
  if (!editingAnalysisId.value) {
    return
  }

  const validProducts = editableProducts.value.filter((product) => {
    return product.class_name && Number(product.weight_g) > 0
  })

  if (validProducts.length === 0) {
    toastStore.info('Добавьте хотя бы один продукт и укажите массу.')
    return
  }

  isLoading.value = true

  try {
    await api.put(`/analyses/${editingAnalysisId.value}/products`, {
      products: validProducts.map((product) => ({
        class_name: product.class_name,
        weight_g: Number(product.weight_g),
      })),
    })

    editingAnalysisId.value = null
    editableProducts.value = []

    await fetchAnalyses()

    toastStore.success('Вес порции обновлён.')
  } catch (error) {
    console.error(error)
    toastStore.error(getFriendlyErrorMessage(error))
  } finally {
    isLoading.value = false
  }
}

function askDeleteAnalysis(analysis) {
  if (!analysis || analysis.is_pending) {
    return
  }

  analysisToDelete.value = analysis
}

function closeDeleteModal() {
  if (isLoading.value) {
    return
  }

  analysisToDelete.value = null
}

async function confirmDeleteAnalysis() {
  if (!analysisToDelete.value) {
    return
  }

  const analysis = analysisToDelete.value

  isLoading.value = true

  try {
    await api.delete(`/analyses/${analysis.id}`)

    if (editingAnalysisId.value === analysis.id) {
      cancelEditProducts()
    }

    analysisToDelete.value = null

    await fetchAnalyses()

    toastStore.success('Запись удалена.')
  } catch (error) {
    console.error(error)
    toastStore.error(getFriendlyErrorMessage(error))
  } finally {
    isLoading.value = false
  }
}
</script>