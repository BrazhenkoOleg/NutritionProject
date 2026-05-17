<script setup>
import MealUploadPanel from './MealUploadPanel.vue'
import MealAnalysisCard from './MealAnalysisCard.vue'
import ProductEditor from './ProductEditor.vue'

defineProps({
  meal: {
    type: Object,
    required: true,
  },
  manualMealType: {
    type: String,
    default: null,
},
    manualProducts: {
    type: Array,
    default: () => [],
},
  analyses: {
    type: Array,
    default: () => [],
  },
  totals: {
    type: Object,
    required: true,
  },
  collapsed: {
    type: Boolean,
    required: true,
  },
  uploadFile: {
    type: [File, Object, null],
    default: null,
  },
  previewUrl: {
    type: String,
    default: null,
  },
  isLoading: {
    type: Boolean,
    required: true,
  },
  uploadMealType: {
    type: String,
    default: null,
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
})

defineEmits([
  'toggle',
  'file-change',
  'analyze',

  'start-manual-entry',
  'cancel-manual-entry',
  'save-manual-entry',

  'edit-products',
  'manual-add-products',
  'cancel-edit',
  'save-edit',
  'delete-analysis',

  'update:editableProducts',
  'update:manualProducts',
])

function formatNumber(value) {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  return Number(value).toFixed(2)
}
</script>

<template>
  <section class="meal-section">
    <button
      type="button"
      class="meal-toggle"
      @click="$emit('toggle')"
    >
      <div class="meal-title">
        <span class="meal-arrow">
          {{ collapsed ? '▸' : '▾' }}
        </span>

        <div>
          <h3>
            <span class="meal-name-icon">{{ meal.icon }}</span>
            {{ meal.label }}
          </h3>
          <small>{{ analyses.length }} записей</small>
        </div>
      </div>

      <div class="meal-inline-totals">
        <span>{{ formatNumber(totals.kcal) }} ккал</span>
        <span>Б {{ formatNumber(totals.protein) }}</span>
        <span>Ж {{ formatNumber(totals.fat) }}</span>
        <span>У {{ formatNumber(totals.carbs) }}</span>
      </div>
    </button>

    <template v-if="!collapsed">
      <MealUploadPanel
        :meal="meal"
        :upload-file="uploadFile"
        :preview-url="previewUrl"
        :is-loading="isLoading"
        :upload-meal-type="uploadMealType"
        @file-change="$emit('file-change', $event)"
        @analyze="$emit('analyze')"
      />

      <div class="manual-entry-panel">
        <div
            v-if="manualMealType !== meal.value"
            class="manual-entry-compact"
        >
            <div>
            <strong>Нет фото?</strong>
            <span>Добавьте продукты вручную с указанием граммовки.</span>
            </div>

            <button
            type="button"
            class="secondary-button"
            @click="$emit('start-manual-entry')"
            >
            Добавить вручную
            </button>
        </div>

        <div
            v-else
            class="manual-entry-editor"
        >
            <div class="manual-entry-header">
            <div>
                <strong>Ручная запись</strong>
                <span>Выберите продукты и укажите массу для раздела «{{ meal.label }}»</span>
            </div>
            </div>

            <ProductEditor
            :editable-products="manualProducts"
            :all-products="allProducts"
            :is-loading="isLoading"
            @update:editable-products="$emit('update:manualProducts', $event)"
            @save="$emit('save-manual-entry')"
            @cancel="$emit('cancel-manual-entry')"
            />
        </div>
      </div>

      <div class="meal-content">
        <p
          v-if="analyses.length === 0"
          class="empty meal-empty"
        >
          Нет записей
        </p>

        <div
          v-else
          class="meal-analysis-list"
        >
          <MealAnalysisCard
            v-for="analysis in analyses"
            :key="analysis.id"
            :analysis="analysis"
            :editing-analysis-id="editingAnalysisId"
            :editable-products="editableProducts"
            :all-products="allProducts"
            :is-loading="isLoading"
            @edit-products="$emit('edit-products', $event)"
            @manual-add-products="$emit('manual-add-products', $event)"
            @cancel-edit="$emit('cancel-edit')"
            @save-edit="$emit('save-edit')"
            @delete-analysis="$emit('delete-analysis', $event)"
            @update:editable-products="$emit('update:editableProducts', $event)"
          />
        </div>
      </div>
    </template>
  </section>
</template>