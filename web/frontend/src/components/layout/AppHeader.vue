<template>
  <header class="site-header">
    <RouterLink
      to="/dashboard"
      class="site-brand"
    >
      <div class="site-brand-mark">
        <IconResolver
          name="ScanSearch"
          :size="24"
        />
      </div>

      <div class="site-brand-copy">
        <strong>NutriVision</strong>
        <span>AI-анализ питания</span>
      </div>
    </RouterLink>

    <nav class="site-nav">
      <RouterLink
        v-if="authStore.token"
        to="/dashboard"
      >
        Дневник
      </RouterLink>

      <RouterLink
        v-if="authStore.token"
        to="/profile"
      >
        Профиль
      </RouterLink>

      <button
        v-if="authStore.token"
        type="button"
        class="ghost-nav-button"
        @click="$emit('toggle-theme')"
      >
        <IconResolver
          :name="theme === 'dark' ? 'Sun' : 'Moon'"
          :size="17"
        />
        <span>{{ theme === 'dark' ? 'Светлая' : 'Тёмная' }}</span>
      </button>

      <button
        v-if="authStore.token"
        type="button"
        class="ghost-nav-button"
        @click="$emit('logout')"
      >
        <IconResolver
          name="LogOut"
          :size="17"
        />
        <span>Выйти</span>
      </button>
    </nav>
  </header>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import IconResolver from '../ui/IconResolver.vue'

defineProps({
  theme: {
    type: String,
    default: 'light',
  },
})

defineEmits(['logout', 'toggle-theme'])

const authStore = useAuthStore()
</script>