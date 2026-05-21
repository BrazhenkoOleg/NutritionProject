import { defineStore } from 'pinia'

import {
  fetchCurrentUser,
  loginUser,
  logoutUser,
  registerUser,
  updateUserProfile,
} from '../services/authService'

export const AUTH_TOKEN_KEY = 'auth_token'

function getStoredToken() {
  return localStorage.getItem(AUTH_TOKEN_KEY)
}

export const useAuthStore = defineStore('auth', {
  state: () => {
    const token = getStoredToken()

    return {
      user: null,
      token,
      isLoading: false,
      isAuthReady: !token,
    }
  },

  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    isProfileCompleted: (state) => Boolean(state.user?.profile_completed),
  },

  actions: {
    setToken(token) {
      this.token = token
      this.isAuthReady = true
      localStorage.setItem(AUTH_TOKEN_KEY, token)
    },

    clearToken() {
      this.token = null
      this.user = null
      this.isAuthReady = true
      localStorage.removeItem(AUTH_TOKEN_KEY)
    },

    setAuthData(data) {
      this.setToken(data.token)
      this.user = data.user
    },

    async fetchUser() {
      if (!this.token) {
        this.user = null
        this.isAuthReady = true
        return null
      }

      this.isAuthReady = false

      try {
        this.user = await fetchCurrentUser()

        return this.user
      } catch (error) {
        this.clearToken()
        throw error
      } finally {
        this.isAuthReady = true
      }
    },

    async login(form) {
      this.isLoading = true

      try {
        const data = await loginUser(form)

        this.setAuthData(data)

        return data
      } finally {
        this.isLoading = false
      }
    },

    async register(form) {
      this.isLoading = true

      try {
        const data = await registerUser(form)

        this.setAuthData(data)

        return data
      } finally {
        this.isLoading = false
      }
    },

    async updateProfile(form) {
      this.isLoading = true

      try {
        const data = await updateUserProfile(form)

        this.user = data.user

        return data
      } finally {
        this.isLoading = false
      }
    },

    async logout() {
      const hadToken = Boolean(this.token)

      this.clearToken()

      if (!hadToken) {
        return
      }

      try {
        await logoutUser()
      } catch (error) {
        if (error.response?.status !== 401) {
          console.warn('Серверный logout не выполнен:', error)
        }
      }
    }
  },
})