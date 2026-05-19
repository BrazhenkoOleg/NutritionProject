<template>
  <div class="toast-container">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toastStore.items"
        :key="toast.id"
        class="toast"
        :class="`toast-${toast.type}`"
      >
        <div class="toast-icon">
          <IconResolver
            v-if="toast.type === 'success'"
            name="CheckCircle2"
            :size="20"
          />

          <IconResolver
            v-else-if="toast.type === 'error'"
            name="XCircle"
            :size="20"
          />

          <IconResolver
            v-else-if="toast.type === 'loading'"
            name="Loader2"
            :size="20"
            class="spin-icon"
          />

          <IconResolver
            v-else
            name="Sparkles"
            :size="20"
          />
        </div>

        <div class="toast-body">
          <strong>{{ toast.title }}</strong>
          <p>{{ toast.message }}</p>
        </div>

        <button
          type="button"
          class="toast-close"
          @click="toastStore.remove(toast.id)"
        >
          ×
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToastStore } from '../../stores/toast'
import IconResolver from './IconResolver.vue'

const toastStore = useToastStore()
</script>