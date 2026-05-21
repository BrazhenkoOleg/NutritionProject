<template>
  <div class="auth-page">
    <section class="auth-card">
      <button
        type="button"
        class="auth-theme-toggle"
        @click="$emit('toggle-theme')"
      >
        <IconResolver
          :name="theme === 'dark' ? 'Sun' : 'Moon'"
          :size="17"
        />

        <span>{{ theme === 'dark' ? 'Светлая тема' : 'Тёмная тема' }}</span>
      </button>

      <div class="auth-header">
        <div class="auth-logo">
          <IconResolver
            name="ScanSearch"
            :size="28"
          />
        </div>

        <span class="eyebrow">
          <IconResolver
            name="Sparkles"
            :size="16"
          />
          NutriVision
        </span>

        <h1>{{ title }}</h1>

        <p>{{ description }}</p>
      </div>

      <slot />

      <div class="auth-switch">
        <slot name="footer" />
      </div>
    </section>
  </div>
</template>

<script setup>
import IconResolver from '../ui/IconResolver.vue'

defineProps({
  theme: {
    type: String,
    default: 'light',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
})

defineEmits(['toggle-theme'])
</script>