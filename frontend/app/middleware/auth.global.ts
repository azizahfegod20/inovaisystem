export default defineNuxtRouteMiddleware(async (to: { path: string }) => {
  if (import.meta.server) return

  const publicPages = ['/login', '/register']
  const isPublic = publicPages.includes(to.path)

  const { isAuthenticated, fetchUser, companies } = useAuth()

  if (!isAuthenticated.value) {
    await fetchUser()
  }

  if (!isAuthenticated.value && !isPublic) {
    return navigateTo('/login')
  }

  if (isAuthenticated.value && isPublic) {
    return navigateTo('/')
  }

  if (isAuthenticated.value && companies.value.length === 0 && to.path !== '/onboarding') {
    return navigateTo('/onboarding')
  }
})
