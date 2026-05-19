import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'
import DashboardPage from '../pages/DashboardPage.vue'
import ProfileSetupPage from '../pages/ProfileSetupPage.vue'
import ProfilePage from '../pages/ProfilePage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    { path: '/', redirect: '/dashboard' },

    {
      path: '/login',
      name: 'login',
      component: LoginPage,
      meta: { guestOnly: true },
    },

    {
      path: '/register',
      name: 'register',
      component: RegisterPage,
      meta: { guestOnly: true },
    },

    {
      path: '/profile-setup',
      name: 'profile-setup',
      component: ProfileSetupPage,
      meta: {
        requiresAuth: true,
        profileSetup: true,
      },
    },

    {
      path: '/profile',
      name: 'profile',
      component: ProfilePage,
      meta: { requiresAuth: true },
    },

    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardPage,
      meta: {
        requiresAuth: true,
        requiresProfile: true,
      },
    },
  ],
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      if (to.meta.requiresAuth) {
        return '/login'
      }
    }
  }

  if (to.meta.requiresAuth && !authStore.token) {
    return '/login'
  }

  if (to.meta.guestOnly && authStore.token) {
    if (authStore.user?.profile_completed) {
      return '/dashboard'
    }

    return '/profile-setup'
  }

  if (to.meta.requiresProfile && authStore.user && !authStore.user.profile_completed) {
    return '/profile-setup'
  }

  return true
})

export default router