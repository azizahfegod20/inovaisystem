import { createSharedComposable } from '@vueuse/core'
import type { AuthUser, CompanySummary } from '~/types'

const _useAuth = () => {
  const user = ref<AuthUser | null>(null)
  const companies = ref<CompanySummary[]>([])
  const currentCompanyId = ref<number | null>(null)
  const isAuthenticated = computed(() => !!user.value)

  const currentCompany = computed(() =>
    companies.value.find(c => c.id === currentCompanyId.value) ?? null
  )

  async function fetchUser() {
    try {
      const data = await $fetch<AuthUser & { companies: CompanySummary[] }>('/api/auth/user', {
        credentials: 'include'
      })
      user.value = { id: data.id, name: data.name, email: data.email }
      companies.value = data.companies ?? []
    } catch {
      user.value = null
      companies.value = []
    }
  }

  async function login(email: string, password: string, remember = false) {
    const data = await $fetch<{ user: AuthUser, companies: CompanySummary[] }>('/api/auth/login', {
      method: 'POST',
      body: { email, password, remember },
      credentials: 'include'
    })
    user.value = data.user
    companies.value = data.companies ?? []
  }

  async function register(name: string, email: string, password: string, password_confirmation: string) {
    const data = await $fetch<{ user: AuthUser }>('/api/auth/register', {
      method: 'POST',
      body: { name, email, password, password_confirmation },
      credentials: 'include'
    })
    user.value = data.user
    companies.value = []
  }

  async function logout() {
    await $fetch('/api/auth/logout', {
      method: 'POST',
      credentials: 'include'
    })
    user.value = null
    companies.value = []
    currentCompanyId.value = null
    navigateTo('/login')
  }

  async function selectCompany(companyId: number) {
    await $fetch(`/api/companies/${companyId}/select`, {
      method: 'POST',
      credentials: 'include'
    })
    currentCompanyId.value = companyId
  }

  return {
    user,
    companies,
    currentCompanyId,
    currentCompany,
    isAuthenticated,
    fetchUser,
    login,
    register,
    logout,
    selectCompany
  }
}

export const useAuth = createSharedComposable(_useAuth)
