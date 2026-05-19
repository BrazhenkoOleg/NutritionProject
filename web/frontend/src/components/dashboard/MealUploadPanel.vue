<template>
  <div class="upload-panel">
    <div class="upload-preview-block">
      <div
        v-if="previewUrl"
        class="upload-preview"
      >
        <img
          :src="previewUrl"
          alt="Предпросмотр блюда"
        />
      </div>

      <div
        v-else
        class="upload-placeholder"
      >
        <IconResolver
          name="Camera"
          :size="24"
        />
      </div>
    </div>

    <div class="upload-content">
      <strong>Добавить фото блюда</strong>
      <span>JPG, PNG или WEBP. Изображение будет оптимизировано перед анализом.</span>

      <label class="upload-file-button">
        <input
          type="file"
          accept="image/jpeg,image/jpg,image/png,image/webp"
          @change="$emit('file-change', $event, meal.value)"
        />

        <IconResolver
          name="UploadCloud"
          :size="17"
        />

        <span>{{ uploadFile ? 'Заменить фото' : 'Выбрать фото' }}</span>
      </label>
    </div>

    <div class="upload-action">
      <button
        type="button"
        class="analyze-button"
        :disabled="!uploadFile || isLoading"
        @click="$emit('analyze', meal.value)"
      >
        <IconResolver
          v-if="isLoading && uploadMealType === meal.value"
          name="Loader2"
          :size="17"
          class="spin-icon"
        />

        <IconResolver
          v-else
          name="ScanSearch"
          :size="17"
        />

        <span>{{ analyzeButtonText }}</span>
      </button>

      <small v-if="isLoading && uploadMealType === meal.value">
        ML-сервис обрабатывает изображение
      </small>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import IconResolver from '../ui/IconResolver.vue'

const props = defineProps({
  meal: {
    type: Object,
    required: true,
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
})

defineEmits(['file-change', 'analyze'])

const analyzeButtonText = computed(() => {
  if (props.isLoading && props.uploadMealType === props.meal.value) {
    return 'AI анализирует'
  }

  if (props.uploadFile) {
    return 'Анализировать'
  }

  return 'Выберите фото'
})
</script>