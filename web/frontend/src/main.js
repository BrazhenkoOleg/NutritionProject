import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import { setUnauthorizedHandler } from './api/authEvents'
import { useAuthStore } from './stores/auth'

import './assets/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

setUnauthorizedHandler(() => {
  const authStore = useAuthStore()
  const currentRoute = router.currentRoute.value

  if (!authStore.isAuthenticated) {
    return
  }

  authStore.clearToken()

  if (isAuthRoute(currentRoute.name)) {
    return
  }

  router.push({
    name: 'login',
    query: getLoginRedirectQuery(currentRoute),
  })
})

function isAuthRoute(routeName) {
  return ['login', 'register'].includes(routeName)
}

function getLoginRedirectQuery(route) {
  if (isPublicRedirectRoute(route.name)) {
    return {}
  }

  return {
    redirect: route.fullPath,
  }
}

function isPublicRedirectRoute(routeName) {
  return [
    'login',
    'register',
    'profile-setup',
  ].includes(routeName)
}

app.mount('#app')