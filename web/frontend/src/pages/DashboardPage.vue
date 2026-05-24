<template>
  <div class="page">
    <AppHeader
      :theme="theme"
      :is-authenticated="authStore.isAuthenticated"
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

            <h1>Анализируйте питания рацион по фото</h1>

            <p>
              NutriVision помогает распознавать продукты на изображении, уточняет вес порции
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
          :insight="weeklyInsight"
          :is-insight-loading="isWeeklyInsightLoading"
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
            :is-loading="isSavingProducts || isSavingManualEntry || isAnalyzingImage || isDeletingAnalysis"
            :upload-meal-type="uploadMealType"
            :manual-meal-type="manualMealType"
            :manual-products="manualProducts"
            :editing-analysis-id="editingAnalysisId"
            :editable-products="editableProducts"
            :all-products="allProducts"
            @toggle="toggleMeal"
            @file-change="handleFileChange"
            @analyze="handleAnalyzeMealImage"
            @start-manual-entry="startManualMealEntry"
            @cancel-manual-entry="cancelManualMealEntry"
            @save-manual-entry="handleSaveManualMealEntry"
            @edit-products="startEditProducts"
            @delete-analysis="askDeleteAnalysis"
            @cancel-edit="cancelEditProducts"
            @save-edit="handleSaveEditedProducts"
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
            :disabled="isDeletingAnalysis"
            @click="closeDeleteModal"
          >
            Отмена
          </button>

          <button
            type="button"
            class="danger-button"
            :disabled="isDeletingAnalysis"
            @click="handleConfirmDeleteAnalysis"
          >
            {{ isDeletingAnalysis ? 'Удаляем...' : 'Удалить запись' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { MEAL_TYPES } from '../constants/mealTypes'

import { getTodayDate } from '../utils/date'

import { useServiceWarmup } from '../composables/useServiceWarmup'

import { useDashboardData } from '../composables/useDashboardData'
import { useProductEditing } from '../composables/useProductEditing'
import { useManualEntry } from '../composables/useManualEntry'
import { useImageAnalysis } from '../composables/useImageAnalysis'

import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

import { useTheme } from '../composables/useTheme'
import { useMealCollapse } from '../composables/useMealCollapse'
import { useAnalysisDelete } from '../composables/useAnalysisDelete'

import AppHeader from '../components/layout/AppHeader.vue'
import AppFooter from '../components/layout/AppFooter.vue'
import IconResolver from '../components/ui/IconResolver.vue'

import DailySummary from '../components/dashboard/DailySummary.vue'
import WeeklyStats from '../components/dashboard/WeeklyStats.vue'
import MealSection from '../components/dashboard/MealSection.vue'

import { useWeeklyInsight } from '../composables/useWeeklyInsight'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const isInitialLoading = ref(true)

const selectedDate = ref(getTodayDate())

const mealTypes = MEAL_TYPES

const {
  theme,
  applyTheme,
  toggleTheme,
} = useTheme()

const {
  collapsedMeals,
  toggleMeal,
  openMeal,
} = useMealCollapse()

const {
  analyses,
  allProducts,
  analysesByMeal,
  dailyTotals,
  fetchDashboardData,
  upsertAnalysis,
  removeAnalysisFromList,
  addPendingAnalysis,
  updatePendingAnalysis,
  removePendingAnalysis,
  getMealTotals,
} = useDashboardData({
  selectedDate,
})

const {
  weeklyInsight,
  isWeeklyInsightLoading,
  fetchWeeklyInsight,
  clearWeeklyInsight,
} = useWeeklyInsight({
  toastStore,
})

const {
  editingAnalysisId,
  editableProducts,
  isSavingProducts,
  startEditProducts,
  cancelEditProducts,
  saveEditedProducts: saveEditedProductsBase,
} = useProductEditing({
  allProducts,
  upsertAnalysis,
  toastStore,
})

const {
  analysisToDelete,
  isDeletingAnalysis,
  askDeleteAnalysis,
  closeDeleteModal,
  confirmDeleteAnalysis: confirmDeleteAnalysisBase,
} = useAnalysisDelete({
  removeAnalysisFromList,
  editingAnalysisId,
  cancelEditProducts,
  toastStore,
})

const {
  uploadMealType,
  isAnalyzingImage,
  mealUploadFiles,
  mealPreviewUrls,
  handleFileChange,
  analyzeMealImage: analyzeMealImageBase,
  warmUpMlOnly,
} = useImageAnalysis({
  selectedDate,
  upsertAnalysis,
  addPendingAnalysis,
  updatePendingAnalysis,
  removePendingAnalysis,
  openMeal,
  startEditProducts,
  toastStore,
})

const {
  isWarmingUp,
  warmUpServices,
} = useServiceWarmup({
  warmUpMlOnly,
})

const {
  manualMealType,
  manualProducts,
  isSavingManualEntry,
  startManualMealEntry,
  cancelManualMealEntry,
  saveManualMealEntry: saveManualMealEntryBase,
} = useManualEntry({
  selectedDate,
  upsertAnalysis,
  openMeal,
  cancelEditProducts,
  toastStore,
})

watch(selectedDate, async () => {
  cancelEditProducts()
  cancelManualMealEntry()
  clearWeeklyInsight()

  await refreshDashboard()
})

onMounted(async () => {
  applyTheme(theme.value)

  try {
    await warmUpServices()
    await refreshDashboard()
  } finally {
    isInitialLoading.value = false
  }
})

function goToday() {
  selectedDate.value = getTodayDate()
}

function logout() {
  authStore.logout()

  if (router.currentRoute.value.path !== '/login') {
    router.push('/login')
  }
}

async function refreshDashboard({
  refreshData = true,
  refreshInsight = true,
} = {}) {
  const tasks = []

  if (refreshData) {
    tasks.push(fetchDashboardData())
  }

  if (refreshInsight) {
    tasks.push(fetchWeeklyInsight(selectedDate.value))
  }

  await Promise.all(tasks)
}

async function handleAnalyzeMealImage(mealType) {
  await analyzeMealImageBase(mealType)
  await refreshWeeklyInsightOnly()
}

async function handleSaveManualMealEntry(mealType) {
  await saveManualMealEntryBase(mealType)
  await refreshWeeklyInsightOnly()
}

async function handleSaveEditedProducts() {
  await saveEditedProductsBase()
  await refreshWeeklyInsightOnly()
}

async function handleConfirmDeleteAnalysis() {
  await confirmDeleteAnalysisBase()
  await refreshWeeklyInsightOnly()
}

async function refreshWeeklyInsightOnly() {
  await refreshDashboard({
    refreshData: false,
    refreshInsight: true,
  })
}
</script>