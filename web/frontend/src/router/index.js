import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const LoginPage = () => import('../pages/LoginPage.vue')
const RegisterPage = () => import('../pages/RegisterPage.vue')
const DashboardPage = () => import('../pages/DashboardPage.vue')
const ProfileSetupPage = () => import('../pages/ProfileSetupPage.vue')
const ProfilePage = () => import('../pages/ProfilePage.vue')

const routes = [
  {
    path: '/',
    redirect: '/dashboard',
  },

  {
    path: '/login',
    name: 'login',
    component: LoginPage,
    meta: {
      guestOnly: true,
    },
  },

  {
    path: '/register',
    name: 'register',
    component: RegisterPage,
    meta: {
      guestOnly: true,
    },
  },

  {
    path: '/profile-setup',
    name: 'profile-setup',
    component: ProfileSetupPage,
    meta: {
      requiresAuth: true,
      onlyIncompleteProfile: true,
    },
  },

  {
    path: '/profile',
    name: 'profile',
    component: ProfilePage,
    meta: {
      requiresAuth: true,
    },
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

  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  await prepareAuth(authStore)

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return {
      name: 'login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return getAuthenticatedRedirect(authStore)
  }

  if (to.meta.requiresProfile && !authStore.isProfileCompleted) {
    return {
      name: 'profile-setup',
    }
  }

  if (to.meta.onlyIncompleteProfile && authStore.isProfileCompleted) {
    return {
      name: 'dashboard',
    }
  }

  return true
})

async function prepareAuth(authStore) {
  if (!authStore.token || authStore.user) {
    return
  }

  try {
    await authStore.fetchUser()
  } catch {
    // authStore.fetchUser() сам очищает token при ошибке
  }
}

function getAuthenticatedRedirect(authStore) {
  if (authStore.isProfileCompleted) {
    return {
      name: 'dashboard',
    }
  }

  return {
    name: 'profile-setup',
  }
}

export default router