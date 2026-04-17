export const setupGuards = router => {
  router.beforeEach(to => {
    if (to.meta.public)
      return

    const isLoggedIn = !!(useCookie('accessToken').value && useCookie('userData').value)

    if (to.meta.unauthenticatedOnly) {
      if (isLoggedIn)
        return '/dashboard'
      else
        return undefined
    }

    if (to.meta.requiresAuth && !isLoggedIn) {
      return {
        name: 'login',
        query: {
          ...to.query,
          to: to.fullPath !== '/' ? to.path : undefined,
        },
      }
    }

    if (to.meta.roles && isLoggedIn) {
      const userData = useCookie('userData').value
      const userRole = userData?.role

      if (!to.meta.roles.includes(userRole)) {
        if (userRole === 'patient')
          return { name: 'patient-dashboard' }

        return { name: 'dashboard' }
      }
    }

    if (to.meta.title)
      document.title = `${to.meta.title} | ClinicWeb`
  })
}
