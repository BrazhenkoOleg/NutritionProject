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
import { useAuthStore } from './stores/auth'

import IconResolver from './components/ui/IconResolver.vue'
import ToastContainer from './components/ui/ToastContainer.vue'

const authStore = useAuthStore()

const isAppPreparing = computed(() => {
  return authStore.token && !authStore.isAuthReady && !authStore.user
})

onMounted(() => {
  const savedTheme = localStorage.getItem('theme') || 'light'
  document.documentElement.setAttribute('data-theme', savedTheme)
})
</script>