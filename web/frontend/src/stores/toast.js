import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', {
  state: () => ({
    items: [],
  }),

  actions: {
    show(payload) {
      const item = {
        id: Date.now() + Math.random(),
        type: payload.type || 'info',
        title: payload.title || '',
        message: payload.message || '',
        duration: payload.duration ?? 3600,
      }

      this.items.push(item)

      if (item.duration > 0) {
        setTimeout(() => {
          this.remove(item.id)
        }, item.duration)
      }
    },

    success(message, title = 'Готово') {
      this.show({
        type: 'success',
        title,
        message,
      })
    },

    error(message, title = 'Ошибка') {
      this.show({
        type: 'error',
        title,
        message,
        duration: 5200,
      })
    },

    info(message, title = 'Информация') {
      this.show({
        type: 'info',
        title,
        message,
      })
    },

    loading(message, title = 'Подождите') {
      this.show({
        type: 'loading',
        title,
        message,
        duration: 2200,
      })
    },

    remove(id) {
      this.items = this.items.filter((item) => item.id !== id)
    },
  },
})