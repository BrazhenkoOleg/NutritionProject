<template>
  <section class="meal-section">
    <button
      type="button"
      class="meal-toggle"
      @click="$emit('toggle', meal.value)"
    >
      <div class="meal-title">
        <div class="meal-icon">
          <IconResolver
            :name="meal.icon"
            :size="20"
          />
        </div>

        <div>
          <h3>{{ meal.label }}</h3>
          <span>{{ meal.description }}</span>
        </div>
      </div>

      <div class="meal-inline-totals">
        <div class="meal-kbju-preview">
          <div class="meal-kbju-main">
            <strong>{{ Math.round(totals.kcal || 0) }}</strong>
            <span>ккал</span>
          </div>

          <div>
            <strong>{{ formatNumber(totals.protein) }}</strong>
            <span>Б, г</span>
          </div>

          <div>
            <strong>{{ formatNumber(totals.fat) }}</strong>
            <span>Ж, г</span>
          </div>

          <div>
            <strong>{{ formatNumber(totals.carbs) }}</strong>
            <span>У, г</span>
          </div>
        </div>

        <span class="meal-records-count">{{ analyses.length }} записей</span>

        <IconResolver
          name="ChevronDown"
          :size="18"
          class="chevron"
          :class="{ rotated: !collapsed }"
        />
      </div>
    </button>

    <div
      v-if="!collapsed"
      class="meal-content"
    >
      <MealUploadPanel
        :meal="meal"
        :upload-file="uploadFile"
        :preview-url="previewUrl"
        :is-loading="isLoading"
        :upload-meal-type="uploadMealType"
        @file-change="$emit('file-change', $event, meal.value)"
        @analyze="$emit('analyze', meal.value)"
      />

      <div class="meal-manual-actions">
        <button
          v-if="manualMealType !== meal.value"
          type="button"
          class="light-button"
          @click="$emit('start-manual-entry', meal.value)"
        >
          Добавить вручную
        </button>
      </div>

      <ProductEditor
        v-if="manualMealType === meal.value"
        :editable-products="manualProducts"
        :all-products="allProducts"
        :is-loading="isLoading"
        @update:editable-products="$emit('update:manualProducts', $event)"
        @save="$emit('save-manual-entry')"
        @cancel="$emit('cancel-manual-entry')"
      />

      <div
        v-if="analyses.length === 0"
        class="meal-empty-state"
      >
        <IconResolver
          name="Camera"
          :size="24"
        />

        <div>
          <strong>Пока нет записей</strong>
          <span>Добавьте фото блюда или внесите продукты вручную.</span>
        </div>
      </div>

      <div
        v-else
        class="meal-analyses-list"
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
          @delete-analysis="$emit('delete-analysis', $event)"
          @cancel-edit="$emit('cancel-edit')"
          @save-edit="$emit('save-edit')"
          @update:editable-products="$emit('update:editableProducts', $event)"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import IconResolver from '../ui/IconResolver.vue'
import MealUploadPanel from './MealUploadPanel.vue'
import MealAnalysisCard from './MealAnalysisCard.vue'
import ProductEditor from './ProductEditor.vue'

function formatNumber(value) {
  return Number(value || 0).toFixed(1)
}

defineProps({
  meal: {
    type: Object,
    required: true,
  },
  analyses: {
    type: Array,
    default: () => [],
  },
  totals: {
    type: Object,
    default: () => ({
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    }),
  },
  collapsed: {
    type: Boolean,
    default: true,
  },
  uploadFile: {
    type: File,
    default: null,
  },
  previewUrl: {
    type: String,
    default: null,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  uploadMealType: {
    type: String,
    default: null,
  },
  manualMealType: {
    type: String,
    default: null,
  },
  manualProducts: {
    type: Array,
    default: () => [],
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
  'delete-analysis',
  'cancel-edit',
  'save-edit',
  'update:editableProducts',
  'update:manualProducts',
])
</script>