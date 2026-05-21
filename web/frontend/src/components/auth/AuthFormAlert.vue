<template>
  <div
    v-if="message"
    class="form-alert"
  >
    <strong>{{ title }}</strong>
    <span>{{ message }}</span>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    default: '',
  },
})
</script>