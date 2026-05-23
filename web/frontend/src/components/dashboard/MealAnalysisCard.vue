<template>
  <article
    v-if="analysis.is_pending"
    class="pending-analysis-card"
  >
    <div class="pending-image">
      <img
        v-if="getPreviewImageUrl(analysis)"
        :src="getPreviewImageUrl(analysis)"
        alt="Фото блюда"
      />

      <IconResolver
        v-else
        name="Camera"
        :size="24"
      />
    </div>

    <div class="pending-content">
      <div class="pending-topline">
        <strong>{{ getPendingTitle(analysis.progress_step) }}</strong>
        <span>{{ getPendingDescription(analysis.progress_step) }}</span>
      </div>

      <div class="smart-progress">
        <span
          class="smart-progress-fill"
          :style="{ width: `${getPendingProgress(analysis.progress_step)}%` }"
        ></span>
      </div>

      <div class="pending-steps">
        <span :class="{ active: isPendingStepActive(analysis.progress_step, 'compressing') }">
          Оптимизация
        </span>

        <span :class="{ active: isPendingStepActive(analysis.progress_step, 'uploading') }">
          Загрузка
        </span>

        <span :class="{ active: isPendingStepActive(analysis.progress_step, 'recognizing') }">
          AI-анализ
        </span>

        <span :class="{ active: isPendingStepActive(analysis.progress_step, 'finalizing') }">
          Расчёт
        </span>
      </div>
    </div>
  </article>

  <article
    v-else
    class="meal-analysis-card"
  >
    <div
      v-if="getAnalysisImageUrl(analysis)"
      class="meal-analysis-image"
    >
      <img
        :src="getAnalysisImageUrl(analysis)"
        alt="Фото анализа"
      />
    </div>

    <div
      v-else
      class="meal-analysis-placeholder"
    >
      <IconResolver
        name="Utensils"
        :size="24"
      />
    </div>

    <div class="meal-analysis-body">
      <div class="meal-analysis-top">
        <div class="meal-analysis-meta">
          <strong>Запись в {{ formatTime(analysis.created_at) }}</strong>
          <span>Анализ #{{ analysis.id }}</span>
        </div>

        <div class="meal-analysis-actions">
          <a
            v-if="getAnalysisImageUrl(analysis)"
            class="image-link"
            :href="getAnalysisImageUrl(analysis)"
            target="_blank"
            rel="noopener noreferrer"
          >
            Фото
          </a>

          <span
            v-else
            class="manual-badge"
          >
            Вручную
          </span>

          <button
            v-if="editingAnalysisId !== analysis.id"
            type="button"
            class="mini-edit-button"
            @click="$emit('edit-products', analysis)"
          >
            Редактировать
          </button>

          <button
            v-if="editingAnalysisId !== analysis.id"
            type="button"
            class="mini-delete-button"
            @click="$emit('delete-analysis', analysis)"
          >
            Удалить
          </button>

          <button
            v-else
            type="button"
            class="mini-cancel-button"
            @click="$emit('cancel-edit')"
          >
            Закрыть
          </button>
        </div>
      </div>

      <div class="meal-analysis-summary">
        <div class="summary-main">
          <strong>{{ formatCalories(totals.kcal) }}</strong>
          <span>ккал</span>
        </div>

        <div>
          <strong>{{ formatMacro(totals.protein) }}</strong>
          <span>белки, г</span>
        </div>

        <div>
          <strong>{{ formatMacro(totals.fat) }}</strong>
          <span>жиры, г</span>
        </div>

        <div>
          <strong>{{ formatMacro(totals.carbs) }}</strong>
          <span>углеводы, г</span>
        </div>
      </div>

      <ProductEditor
        v-if="editingAnalysisId === analysis.id"
        :editable-products="editableProducts"
        :all-products="allProducts"
        :is-loading="isLoading"
        @update:editable-products="$emit('update:editableProducts', $event)"
        @save="$emit('save-edit')"
        @cancel="$emit('cancel-edit')"
      />

      <div
        v-else-if="analysis.products?.length"
        class="analysis-products-list"
      >
        <div
          v-for="(product, index) in analysis.products"
          :key="getProductKey(product, index)"
          class="analysis-product-row"
        >
          <div>
            <strong>{{ getProductName(product) }}</strong>
            <span>{{ formatWeight(product.weight_g) }} г</span>
          </div>

          <span>{{ formatCalories(getProductTotals(product).kcal) }} ккал</span>
        </div>
      </div>

      <div
        v-else
        class="analysis-empty-products"
      >
        Продукты не найдены. Можно добавить их вручную.
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'

import {
  PENDING_ANALYSIS_DESCRIPTIONS,
  PENDING_ANALYSIS_PROGRESS,
  PENDING_ANALYSIS_STEP_ORDER,
  PENDING_ANALYSIS_TITLES,
} from '../../constants/pendingAnalysis'

import {
  getAnalysisProductTotals,
  getAnalysisTotals,
} from '../../utils/nutrition'

import {
  getAnalysisImageUrl,
  getPreviewImageUrl,
} from '../../utils/analysisImages'

import {
  formatCalories,
  formatMacro,
  formatTime,
  formatWeight,
} from '../../utils/formatters'

import IconResolver from '../ui/IconResolver.vue'
import ProductEditor from './ProductEditor.vue'

const props = defineProps({
  analysis: {
    type: Object,
    required: true,
  },
  editingAnalysisId: {
    type: [Number, String, null],
    default: null,
  },
  editableProducts: {
    type: Array,
    default: () => [],
  },
  allProducts: {
    type: Array,
    default: () => [],
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
})

defineEmits([
  'edit-products',
  'delete-analysis',
  'cancel-edit',
  'save-edit',
  'update:editableProducts',
])

const totals = computed(() => {
  return getAnalysisTotals(props.analysis)
})

function getProductTotals(product) {
  return getAnalysisProductTotals(product)
}

function getProductKey(product, index) {
  return product.id || `${props.analysis.id}-${product.product_id || 'product'}-${index}`
}

function getProductName(product) {
  return product.name_ru || product.product?.name_ru || product.class_name || product.product?.class_name || 'Продукт'
}

function getPendingTitle(step) {
  return PENDING_ANALYSIS_TITLES[step] || 'Обрабатываем фото'
}

function getPendingDescription(step) {
  return PENDING_ANALYSIS_DESCRIPTIONS[step] || 'Это займёт немного времени.'
}

function getPendingProgress(step) {
  return PENDING_ANALYSIS_PROGRESS[step] || 18
}

function isPendingStepActive(currentStep, step) {
  return PENDING_ANALYSIS_STEP_ORDER.indexOf(step) <= PENDING_ANALYSIS_STEP_ORDER.indexOf(currentStep)
}
</script>