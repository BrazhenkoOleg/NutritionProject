import { ref } from 'vue'

import { checkBackendHealth } from '../services/healthService'

export function useServiceWarmup({
  warmUpMlOnly,
}) {
  const isWarmingUp = ref(false)

  async function warmUpServices() {
    isWarmingUp.value = true

    try {
      await checkBackendHealth()
    } catch (error) {
      console.warn('Backend warm-up failed', error)
    }

    try {
      await warmUpMlOnly({
        silent: true,
      })
    } finally {
      isWarmingUp.value = false
    }
  }

  return {
    isWarmingUp,
    warmUpServices,
  }
}