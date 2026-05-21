import axios from 'axios'

import { notifyUnauthorized } from './authEvents'
import { AUTH_TOKEN_KEY } from '../constants/auth'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: false,
  timeout: 30000,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(AUTH_TOKEN_KEY)

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const url = error.config?.url || ''

    const isAuthRequest = url.includes('/login') || url.includes('/register')
    const isLogoutRequest = url.includes('/logout')

    if (status === 401 && !isAuthRequest && !isLogoutRequest) {
      notifyUnauthorized()
    }

    return Promise.reject(error)
  },
)

export default api