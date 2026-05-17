<script setup>
import ProductEditor from './ProductEditor.vue'

defineProps({
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
    required: true,
  },
})

defineEmits([
  'edit-products',
  'manual-add-products',
  'cancel-edit',
  'save-edit',
  'delete-analysis',
  'update:editableProducts',
])

function formatTime(value) {
  if (!value) {
    return '—'
  }

  return new Date(value).toLocaleTimeString('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatNumber(value) {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  return Number(value).toFixed(2)
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

function getAnalysisTotals(analysis) {
  const products = analysis.products || []

  return {
    kcal: products.reduce((sum, product) => sum + getProductKcal(product), 0),
    protein: products.reduce((sum, product) => sum + getProductProtein(product), 0),
    fat: products.reduce((sum, product) => sum + getProductFat(product), 0),
    carbs: products.reduce((sum, product) => sum + getProductCarbs(product), 0),
  }
}
</script>

<template>
  <article class="meal-analysis-card">
    <a
        v-if="analysis.image_url"
        class="meal-analysis-image-link"
        :href="analysis.image_url"
        target="_blank"
        rel="noopener noreferrer"
    >
        <img
            class="meal-analysis-image"
            :src="analysis.image_url"
            alt="Фото приёма пищи"
        />
    </a>

    <div
        v-else
        class="meal-analysis-placeholder"
    >
        <span>Без фото</span>
    </div>

    <div class="meal-analysis-body">
      <div class="meal-analysis-top">
        <div class="meal-analysis-meta">
          <strong>Запись в {{ formatTime(analysis.created_at) }}</strong>
          <span>Анализ #{{ analysis.id }}</span>
        </div>

        <div class="meal-analysis-actions">
          <a
              v-if="analysis.image_url"
              class="image-link"
              :href="analysis.image_url"
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
            class="light-button mini-cancel-button"
            @click="$emit('cancel-edit')"
          >
            Закрыть
          </button>
        </div>
      </div>

      <div class="meal-analysis-summary">
        <div class="summary-main">
            <strong>{{ Math.round(getAnalysisTotals(analysis).kcal || 0) }}</strong>
            <span>ккал</span>
        </div>

        <div>
            <strong>{{ formatNumber(getAnalysisTotals(analysis).protein) }}</strong>
            <span>белки, г</span>
        </div>

        <div>
            <strong>{{ formatNumber(getAnalysisTotals(analysis).fat) }}</strong>
            <span>жиры, г</span>
        </div>

        <div>
            <strong>{{ formatNumber(getAnalysisTotals(analysis).carbs) }}</strong>
            <span>углеводы, г</span>
        </div>
      </div>

      <div
        v-if="analysis.products?.length"
        class="meal-products-list"
      >
        <div
          v-for="product in analysis.products"
          :key="`${analysis.id}-${product.class_name}`"
          class="meal-product-row"
        >
          <div>
            <strong>{{ product.name_ru || product.class_name }}</strong>
            <span>{{ product.weight_g || 100 }} г</span>
          </div>

          <div class="meal-product-kbju">
            <span>{{ formatNumber(getProductKcal(product)) }} ккал</span>
            <span>Б {{ formatNumber(getProductProtein(product)) }}</span>
            <span>Ж {{ formatNumber(getProductFat(product)) }}</span>
            <span>У {{ formatNumber(getProductCarbs(product)) }}</span>
          </div>
        </div>
      </div>

      <div
        v-else
        class="empty-products-block"
      >
        <p class="empty">
          Продукты не указаны
        </p>

        <button
          type="button"
          class="secondary-button"
          @click="$emit('manual-add-products', analysis)"
        >
          Добавить продукты
        </button>
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
    </div>
  </article>
</template>