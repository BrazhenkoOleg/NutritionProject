<template>
  <button
    type="submit"
    class="auth-submit-button"
    :disabled="isLoading"
  >
    <IconResolver
      v-if="isLoading"
      name="Loader2"
      :size="18"
      class="spin-icon"
    />

    <IconResolver
      v-else
      :name="icon"
      :size="18"
    />

    <span>{{ isLoading ? loadingText : text }}</span>
  </button>
</template>

<script setup>
import IconResolver from '../ui/IconResolver.vue'

defineProps({
  isLoading: {
    type: Boolean,
    default: false,
  },
  icon: {
    type: String,
    default: 'CheckCircle2',
  },
  text: {
    type: String,
    required: true,
  },
  loadingText: {
    type: String,
    required: true,
  },
})
</script>