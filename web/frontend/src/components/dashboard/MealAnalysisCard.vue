<template>
  <article
    v-if="analysis.is_pending"
    class="pending-analysis-card"
  >
    <div class="pending-image">
      <img
        v-if="analysis.image_url"
        :src="analysis.image_url"
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
      v-if="analysis.image_url"
      class="meal-analysis-image"
    >
      <img
        :src="analysis.image_url"
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
            class="mini-cancel-button"
            @click="$emit('cancel-edit')"
          >
            Закрыть
          </button>
        </div>
      </div>

      <div class="meal-analysis-summary">
        <div class="summary-main">
          <strong>{{ Math.round(totals.kcal || 0) }}</strong>
          <span>ккал</span>
        </div>

        <div>
          <strong>{{ formatNumber(totals.protein) }}</strong>
          <span>белки, г</span>
        </div>

        <div>
          <strong>{{ formatNumber(totals.fat) }}</strong>
          <span>жиры, г</span>
        </div>

        <div>
          <strong>{{ formatNumber(totals.carbs) }}</strong>
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
          v-for="product in analysis.products"
          :key="`${analysis.id}-${product.class_name}`"
          class="analysis-product-row"
        >
          <div>
            <strong>{{ product.name_ru || product.class_name }}</strong>
            <span>{{ formatNumber(product.weight_g || 0) }} г</span>
          </div>

          <span>{{ Math.round(product.total_kcal || 0) }} ккал</span>
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

function formatNumber(value) {
  return Number(value || 0).toFixed(1)
}

function formatTime(value) {
  if (!value) {
    return '--:--'
  }

  return new Date(value).toLocaleTimeString('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getPendingTitle(step) {
  const titles = {
    preparing: 'Подготовка анализа',
    compressing: 'Оптимизируем изображение',
    uploading: 'Загружаем фото',
    recognizing: 'AI распознаёт продукты',
    finalizing: 'Считаем КБЖУ',
    failed: 'Анализ не выполнен',
  }

  return titles[step] || 'Обрабатываем фото'
}

function getPendingDescription(step) {
  const descriptions = {
    preparing: 'Создаём запись и готовим изображение.',
    compressing: 'Уменьшаем размер файла перед отправкой.',
    uploading: 'Передаём изображение в сервис распознавания.',
    recognizing: 'Модель определяет продукты на изображении.',
    finalizing: 'Формируем список продуктов и расчёт питательности.',
    failed: 'Сервис распознавания временно недоступен. Попробуйте ещё раз.',
  }

  return descriptions[step] || 'Это займёт немного времени.'
}

function getPendingProgress(step) {
  const progress = {
    preparing: 12,
    compressing: 28,
    uploading: 48,
    recognizing: 76,
    finalizing: 94,
    failed: 100,
  }

  return progress[step] || 18
}

function isPendingStepActive(currentStep, step) {
  const order = ['compressing', 'uploading', 'recognizing', 'finalizing']

  return order.indexOf(step) <= order.indexOf(currentStep)
}
</script>