import { defineStore } from 'pinia'
import api from '../api/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token'),
    isLoading: false,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token && state.user),
    isProfileCompleted: (state) => Boolean(state.user?.profile_completed),
  },

  actions: {
    setToken(token) {
      this.token = token
      localStorage.setItem('auth_token', token)
    },

    clearToken() {
      this.token = null
      localStorage.removeItem('auth_token')
    },

    async fetchUser() {
      if (!this.token) {
        return null
      }

      try {
        const response = await api.get('/user')
        this.user = response.data.user

        return this.user
      } catch (error) {
        this.user = null
        this.clearToken()
        throw error
      }
    },

    async login(form) {
      this.isLoading = true

      try {
        const response = await api.post('/login', form)

        this.setToken(response.data.token)
        this.user = response.data.user

        return response.data
      } finally {
        this.isLoading = false
      }
    },

    async register(form) {
      this.isLoading = true

      try {
        const response = await api.post('/register', form)

        this.setToken(response.data.token)
        this.user = response.data.user

        return response.data
      } finally {
        this.isLoading = false
      }
    },

    async updateProfile(form) {
      this.isLoading = true

      try {
        const response = await api.put('/profile', form)

        this.user = response.data.user

        return response.data
      } finally {
        this.isLoading = false
      }
    },

    async logout() {
      try {
        if (this.token) {
          await api.post('/logout')
        }
      } catch (error) {
        console.error('Ошибка выхода:', error)
      } finally {
        this.user = null
        this.clearToken()
      }
    },
  },
})