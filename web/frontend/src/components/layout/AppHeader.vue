<template>
  <header class="site-header">
    <RouterLink
      :to="brandLink"
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

    <nav
      v-if="isAuthenticated"
      class="site-nav"
    >
      <RouterLink to="/dashboard">
        Дневник
      </RouterLink>

      <RouterLink to="/profile">
        Профиль
      </RouterLink>

      <button
        type="button"
        class="ghost-nav-button"
        @click="$emit('toggle-theme')"
      >
        <IconResolver
          :name="themeIcon"
          :size="17"
        />

        <span>{{ themeLabel }}</span>
      </button>

      <button
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
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import IconResolver from '../ui/IconResolver.vue'

const props = defineProps({
  theme: {
    type: String,
    default: 'light',
  },
  isAuthenticated: {
    type: Boolean,
    default: false,
  },
})

defineEmits([
  'logout',
  'toggle-theme',
])

const brandLink = computed(() => {
  return props.isAuthenticated ? '/dashboard' : '/login'
})

const themeIcon = computed(() => {
  return props.theme === 'dark' ? 'Sun' : 'Moon'
})

const themeLabel = computed(() => {
  return props.theme === 'dark' ? 'Светлая' : 'Тёмная'
})
</script>