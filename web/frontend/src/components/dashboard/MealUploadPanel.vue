<script setup>
defineProps({
  meal: {
    type: Object,
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
})

defineEmits(['file-change', 'analyze'])
</script>

<template>
  <div class="meal-upload-panel">
    <div class="meal-upload-info">
      <strong>📷 Добавить фото</strong>
      <span>Загрузите изображение для раздела «{{ meal.label }}»</span>
    </div>

    <div class="meal-upload-actions">
      <label class="meal-file-button">
        📁 Выбрать

        <input
          type="file"
          accept="image/jpeg,image/jpg,image/png,image/webp"
          @change="$emit('file-change', $event, meal.value)"
        />
      </label>

      <button
        type="button"
        :disabled="isLoading || !uploadFile"
        @click="$emit('analyze')"
      >
        {{
          isLoading && uploadMealType === meal.value
            ? 'Анализ...'
            : 'Анализировать'
        }}
      </button>
    </div>

    <div
      v-if="previewUrl"
      class="meal-upload-preview"
    >
      <img
        :src="previewUrl"
        alt="Предпросмотр"
      />

      <span>{{ uploadFile?.name }}</span>
    </div>
  </div>
</template>