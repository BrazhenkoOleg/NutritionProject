<template>
  <div
    v-if="isAppPreparing"
    class="app-shell-loader"
  >
    <div class="app-shell-card">
      <div class="app-shell-brand">
        <div class="app-shell-logo">
          <IconResolver
            name="ScanSearch"
            :size="28"
          />
        </div>

        <div>
          <strong>NutriVision</strong>
          <span>Подготавливаем AI-дневник питания</span>
        </div>
      </div>

      <div class="app-shell-progress">
        <span></span>
      </div>
    </div>
  </div>

  <RouterView v-else />

  <ToastContainer />
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { RouterView } from 'vue-router'

import { useTheme } from './composables/useTheme'
import { useAuthStore } from './stores/auth'

import IconResolver from './components/ui/IconResolver.vue'
import ToastContainer from './components/ui/ToastContainer.vue'

const authStore = useAuthStore()

const {
  theme,
  applyTheme,
} = useTheme()

const isAppPreparing = computed(() => {
  return Boolean(authStore.token && !authStore.isAuthReady)
})

onMounted(() => {
  applyTheme(theme.value)
})
</script>