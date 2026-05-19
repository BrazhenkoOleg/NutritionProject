<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'
import { useAuthStore } from '../stores/auth'

import DailySummary from '../components/dashboard/DailySummary.vue'
import WeeklyStats from '../components/dashboard/WeeklyStats.vue'
import MealSection from '../components/dashboard/MealSection.vue'

const router = useRouter()
const authStore = useAuthStore()

const theme = ref(localStorage.getItem('theme') || 'light')

function applyTheme(value) {
  theme.value = value
  localStorage.setItem('theme', value)
  document.documentElement.setAttribute('data-theme', value)
}

function toggleTheme() {
  applyTheme(theme.value === 'light' ? 'dark' : 'light')
}

const message = ref('')
const isLoading = ref(false)

const analyses = ref([])
const allProducts = ref([])

const selectedDate = ref(getTodayDate())

function getTodayDate() {
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

const uploadMealType = ref(null)

const editingAnalysisId = ref(null)
const editableProducts = ref([])

const manualMealType = ref(null)
const manualProducts = ref([])

const analysisToDelete = ref(null)

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
  breakfast: false,
  lunch: false,
  dinner: false,
  snack: false,
})

const mealTypes = [
  {
    value: 'breakfast',
    label: 'Завтрак',
    icon: '🌅',
  },
  {
    value: 'lunch',
    label: 'Обед',
    icon: '☀️',
  },
  {
    value: 'dinner',
    label: 'Ужин',
    icon: '🌙',
  },
  {
    value: 'snack',
    label: 'Перекусы',
    icon: '🍏',
  },
]

const dailyAnalyses = computed(() => {
  return analyses.value.filter((analysis) => {
    return getAnalysisDate(analysis) === selectedDate.value
  })
})

const groupedAnalyses = computed(() => {
  const groups = {
    breakfast: [],
    lunch: [],
    dinner: [],
    snack: [],
  }

  dailyAnalyses.value.forEach((analysis) => {
    const mealType = analysis.meal_type || 'snack'

    if (!groups[mealType]) {
      groups.snack.push(analysis)
      return
    }

    groups[mealType].push(analysis)
  })

  return groups
})

const dailyTotals = computed(() => {
  return getAnalysesTotals(dailyAnalyses.value)
})

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

function getTotalKcal(products) {
  return products.reduce((sum, product) => sum + getProductKcal(product), 0)
}

function getTotalProtein(products) {
  return products.reduce((sum, product) => sum + getProductProtein(product), 0)
}

function getTotalFat(products) {
  return products.reduce((sum, product) => sum + getProductFat(product), 0)
}

function getTotalCarbs(products) {
  return products.reduce((sum, product) => sum + getProductCarbs(product), 0)
}

function getAnalysisTotals(analysis) {
  const products = analysis.products || []

  return {
    kcal: getTotalKcal(products),
    protein: getTotalProtein(products),
    fat: getTotalFat(products),
    carbs: getTotalCarbs(products),
  }
}

function getAnalysesTotals(items) {
  return items.reduce(
    (totals, analysis) => {
      const analysisTotals = getAnalysisTotals(analysis)

      return {
        kcal: totals.kcal + analysisTotals.kcal,
        protein: totals.protein + analysisTotals.protein,
        fat: totals.fat + analysisTotals.fat,
        carbs: totals.carbs + analysisTotals.carbs,
      }
    },
    {
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    },
  )
}

function getMealTotals(mealType) {
  return getAnalysesTotals(groupedAnalyses.value[mealType] || [])
}

function toggleMeal(mealType) {
  collapsedMeals.value[mealType] = !collapsedMeals.value[mealType]
}

function handleMealFileChange(event, mealType) {
  const file = event.target.files[0]

  mealUploadFiles.value[mealType] = file || null
  uploadMealType.value = mealType
  editingAnalysisId.value = null
  editableProducts.value = []
  message.value = ''

  if (mealPreviewUrls.value[mealType]) {
    URL.revokeObjectURL(mealPreviewUrls.value[mealType])
  }

  mealPreviewUrls.value[mealType] = file ? URL.createObjectURL(file) : null
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

function formatFileSize(bytes) {
  if (!bytes) {
    return '0 КБ'
  }

  const kb = bytes / 1024

  if (kb < 1024) {
    return `${kb.toFixed(1)} КБ`
  }

  return `${(kb / 1024).toFixed(2)} МБ`
}

async function fetchProducts() {
  try {
    const response = await api.get('/products')
    allProducts.value = response.data.data
  } catch (error) {
    console.error('Ошибка загрузки справочника продуктов:', error)
  }
}

async function fetchAnalyses() {
  try {
    const response = await api.get('/analyses')
    analyses.value = response.data.data
  } catch (error) {
    console.error('Ошибка загрузки истории:', error)
  }
}

async function analyzeMealImage(mealType) {
  const file = mealUploadFiles.value[mealType]

  if (!file) {
    message.value = 'Выберите изображение'
    return
  }

  isLoading.value = true
  uploadMealType.value = mealType
  message.value = 'Подготавливаем изображение...'

  try {
    const compressedFile = await compressImage(file, 1024, 0.78)

    console.log('Исходный файл:', formatFileSize(file.size))
    console.log('Сжатый файл:', formatFileSize(compressedFile.size))

    const formData = new FormData()
    formData.append('image', compressedFile)
    formData.append('meal_type', mealType)
    formData.append('entry_date', selectedDate.value)

    message.value = `Отправляем изображение на анализ (${formatFileSize(compressedFile.size)})...`

    await api.post('/analyze', formData)

    mealUploadFiles.value[mealType] = null

    if (mealPreviewUrls.value[mealType]) {
      URL.revokeObjectURL(mealPreviewUrls.value[mealType])
      mealPreviewUrls.value[mealType] = null
    }

    message.value = 'Фото успешно проанализировано'

    await fetchAnalyses()
  } catch (error) {
    console.error(error)
    console.log(error.response?.data)

    if (error.response?.status === 401) {
      await logout()
      return
    }

    if (error.response?.data?.ml_body) {
      message.value = `Ошибка ML-сервиса: ${error.response.data.ml_body}`
    } else if (error.response?.data?.message) {
      message.value = error.response.data.message
    } else if (error.message) {
      message.value = error.message
    } else {
      message.value = 'Ошибка при анализе изображения'
    }
  } finally {
    isLoading.value = false
    uploadMealType.value = null
  }
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

  message.value = ''
}

function cancelManualMealEntry() {
  manualMealType.value = null
  manualProducts.value = []
  message.value = ''
}

async function saveManualMealEntry() {
  if (!manualMealType.value) {
    return
  }

  const validProducts = manualProducts.value.filter((product) => {
    return product.class_name && Number(product.weight_g) > 0
  })

  if (validProducts.length === 0) {
    message.value = 'Добавьте хотя бы один продукт и укажите массу'
    return
  }

  isLoading.value = true
  message.value = 'Создаём ручную запись...'

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
    message.value = 'Ручная запись добавлена'

    await fetchAnalyses()
  } catch (error) {
    console.error(error)

    if (error.response?.status === 401) {
      await logout()
      return
    }

    if (error.response?.data?.message) {
      message.value = error.response.data.message
    } else {
      message.value = 'Ошибка при создании ручной записи'
    }
  } finally {
    isLoading.value = false
  }
}

function startEditAnalysisProducts(analysis) {
  if (!analysis) {
    return
  }

  editingAnalysisId.value = analysis.id

  editableProducts.value = (analysis.products || []).map((product) => ({
    class_name: product.class_name,
    query: product.name_ru
      ? `${product.name_ru} — ${product.class_name}`
      : product.class_name,
    weight_g: product.weight_g || 100,
  }))

  message.value = `Редактирование анализа ID: ${analysis.id}`
}

function startManualAddProducts(analysis) {
  if (!analysis) {
    return
  }

  editingAnalysisId.value = analysis.id

  editableProducts.value = [
    {
      class_name: '',
      query: '',
      weight_g: 100,
    },
  ]

  message.value = `Добавление продуктов в анализ ID: ${analysis.id}`
}

function cancelEditProducts() {
  editingAnalysisId.value = null
  editableProducts.value = []
  message.value = ''
}

async function saveEditedProducts() {
  if (!editingAnalysisId.value) {
    return
  }

  const validProducts = editableProducts.value.filter((product) => {
    return product.class_name && Number(product.weight_g) > 0
  })

  if (validProducts.length === 0) {
    message.value = 'Добавьте хотя бы один продукт и укажите массу'
    return
  }

  isLoading.value = true
  message.value = 'Сохраняем изменения...'

  try {
    await api.put(`/analyses/${editingAnalysisId.value}/products`, {
      products: validProducts.map((product) => ({
        class_name: product.class_name,
        weight_g: Number(product.weight_g),
      })),
    })

    editingAnalysisId.value = null
    editableProducts.value = []
    message.value = 'Список продуктов и масса обновлены'

    await fetchAnalyses()
  } catch (error) {
    console.error(error)

    if (error.response?.status === 401) {
      await logout()
      return
    }

    if (error.response?.data?.message) {
      message.value = error.response.data.message
    } else {
      message.value = 'Ошибка при сохранении изменений'
    }
  } finally {
    isLoading.value = false
  }
}

function askDeleteAnalysis(analysis) {
  if (!analysis) {
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
  message.value = 'Удаляем запись...'

  try {
    await api.delete(`/analyses/${analysis.id}`)

    if (editingAnalysisId.value === analysis.id) {
      editingAnalysisId.value = null
      editableProducts.value = []
    }

    analysisToDelete.value = null
    message.value = 'Запись удалена'

    await fetchAnalyses()
  } catch (error) {
    console.error(error)

    if (error.response?.status === 401) {
      await logout()
      return
    }

    if (error.response?.data?.message) {
      message.value = error.response.data.message
    } else {
      message.value = 'Ошибка при удалении записи'
    }
  } finally {
    isLoading.value = false
  }
}

async function logout() {
  await authStore.logout()

  analyses.value = []
  allProducts.value = []
  editingAnalysisId.value = null
  editableProducts.value = []
  manualMealType.value = null
  manualProducts.value = []
  analysisToDelete.value = null
  message.value = ''

  Object.keys(mealPreviewUrls.value).forEach((mealType) => {
    if (mealPreviewUrls.value[mealType]) {
      URL.revokeObjectURL(mealPreviewUrls.value[mealType])
    }
  })

  mealUploadFiles.value = {
    breakfast: null,
    lunch: null,
    dinner: null,
    snack: null,
  }

  mealPreviewUrls.value = {
    breakfast: null,
    lunch: null,
    dinner: null,
    snack: null,
  }

  uploadMealType.value = null

  router.push('/login')
}

onMounted(async () => {
  applyTheme(theme.value)

  try {
    if (!authStore.user) {
      await authStore.fetchUser()
    }

    if (!authStore.user?.profile_completed) {
      router.push('/profile-setup')
      return
    }

    await fetchProducts()
    await fetchAnalyses()
  } catch (error) {
    console.error(error)
    router.push('/login')
  }
})
</script>

<template>
  <main class="page">
    <section class="app-header-card">
        <div class="app-header">
            <div class="brand-block">
            <div class="brand-icon">
                🍽️
            </div>

            <div>
                <h1>NutriVision</h1>

                <p>
                Дневник питания с распознаванием продуктов, ручной корректировкой и расчётом КБЖУ.
                </p>
            </div>
            </div>

            <div
            v-if="authStore.user"
            class="header-actions"
            >
            <div class="user-chip">
                <span class="user-avatar">👤</span>

                <div>
                <strong>{{ authStore.user.name }}</strong>
                <small>{{ authStore.user.email }}</small>
                </div>
            </div>

            <button
                type="button"
                class="icon-button"
                :title="theme === 'light' ? 'Включить тёмную тему' : 'Включить светлую тему'"
                @click="toggleTheme"
            >
                {{ theme === 'light' ? '🌙' : '☀️' }}
            </button>

            <button
                type="button"
                class="soft-button"
                @click="router.push('/profile')"
            >
                ⚙️ Профиль
            </button>

            <button
                type="button"
                class="ghost-button"
                :disabled="isLoading"
                @click="logout"
            >
                Выйти
            </button>
            </div>
        </div>

        <p
            v-if="message"
            class="message compact-message"
        >
            {{ message }}
        </p>
    </section>

    <section class="card history-card">
      <div class="dashboard-toolbar">
        <div>
            <span class="eyebrow">Личный кабинет</span>

            <h2>Дневник питания</h2>

            <p>
            Выберите дату, добавляйте приёмы пищи и отслеживайте норму КБЖУ.
            </p>
        </div>

        <div class="date-card">
            <span>📅 Дата</span>

            <input
            v-model="selectedDate"
            type="date"
            />
        </div>
      </div>

      <DailySummary
        :daily-totals="dailyTotals"
        :user="authStore.user"
        @open-profile="router.push('/profile')"
      />

      <WeeklyStats
        :analyses="analyses"
        :user="authStore.user"
      />

      <div class="meal-sections">
        <MealSection
            v-for="meal in mealTypes"
            :key="meal.value"
            v-model:editable-products="editableProducts"
            v-model:manual-products="manualProducts"
            :meal="meal"
            :analyses="groupedAnalyses[meal.value]"
            :totals="getMealTotals(meal.value)"
            :collapsed="collapsedMeals[meal.value]"
            :upload-file="mealUploadFiles[meal.value]"
            :preview-url="mealPreviewUrls[meal.value]"
            :is-loading="isLoading"
            :upload-meal-type="uploadMealType"
            :manual-meal-type="manualMealType"
            :editing-analysis-id="editingAnalysisId"
            :all-products="allProducts"
            @toggle="toggleMeal(meal.value)"
            @file-change="handleMealFileChange($event, meal.value)"
            @analyze="analyzeMealImage(meal.value)"
            @start-manual-entry="startManualMealEntry(meal.value)"
            @cancel-manual-entry="cancelManualMealEntry"
            @save-manual-entry="saveManualMealEntry"
            @edit-products="startEditAnalysisProducts"
            @manual-add-products="startManualAddProducts"
            @cancel-edit="cancelEditProducts"
            @save-edit="saveEditedProducts"
            @delete-analysis="askDeleteAnalysis"
        />
      </div>
    </section>
    <div
        v-if="analysisToDelete"
        class="modal-overlay"
        @click.self="closeDeleteModal"
    >
        <div class="confirm-modal">
            <div class="confirm-modal-icon">
            !
            </div>

            <div class="confirm-modal-content">
            <h2>Удалить запись?</h2>

            <p>
                Запись анализа #{{ analysisToDelete.id }} будет удалена из дневника питания.
                Это действие нельзя отменить.
            </p>

            <div class="delete-analysis-preview">
                <img
                v-if="analysisToDelete.image_url"
                :src="analysisToDelete.image_url"
                alt="Фото анализа"
                />

                <div>
                <strong>Запись в {{ new Date(analysisToDelete.created_at).toLocaleTimeString('ru-RU', {
                    hour: '2-digit',
                    minute: '2-digit',
                }) }}</strong>

                <span>
                    Продуктов: {{ analysisToDelete.products?.length || 0 }}
                </span>
                </div>
            </div>
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
    </main>
</template>