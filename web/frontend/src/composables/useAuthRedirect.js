export function useAuthRedirect(router, authStore) {
  function redirectAuthorizedUser(defaultPath = '/dashboard') {
    if (!authStore.token || !authStore.user) {
      return
    }

    if (!authStore.user.profile_completed) {
      router.push('/profile-setup')
      return
    }

    const redirectPath = router.currentRoute.value.query.redirect

    router.push(typeof redirectPath === 'string' ? redirectPath : defaultPath)
  }

  return {
    redirectAuthorizedUser,
  }
}