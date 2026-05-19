import { defineStore } from 'pinia'
import api from '../api/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token'),
    isLoading: false,
    isAuthReady: !localStorage.getItem('auth_token'),
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    isProfileCompleted: (state) => Boolean(state.user?.profile_completed),
  },

  actions: {
    setToken(token) {
      this.token = token
      this.isAuthReady = true
      localStorage.setItem('auth_token', token)
    },

    clearToken() {
      this.token = null
      this.user = null
      this.isAuthReady = true
      localStorage.removeItem('auth_token')
    },

    async fetchUser() {
      if (!this.token) {
        this.user = null
        this.isAuthReady = true
        return null
      }

      this.isAuthReady = false

      try {
        const response = await api.get('/user')
        this.user = response.data.user

        return this.user
      } catch (error) {
        this.user = null
        this.clearToken()
        throw error
      } finally {
        this.isAuthReady = true
      }
    },

    async login(form) {
      this.isLoading = true

      try {
        const response = await api.post('/login', {
          email: form.email,
          password: form.password,
        })

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
        const response = await api.post('/register', {
          name: form.name,
          email: form.email,
          password: form.password,
          password_confirmation: form.password_confirmation,
        })

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
        if (error.response?.status !== 401) {
          console.error('Ошибка выхода:', error)
        }
      } finally {
        this.user = null
        this.clearToken()
        this.isAuthReady = true
      }
    },
  },
})