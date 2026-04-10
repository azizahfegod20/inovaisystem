import { createSharedComposable } from '@vueuse/core'
import type { Company } from '~/types'

interface Member {
  user_id: number
  name: string
  email: string
  role: string
}

const _useCompany = () => {
  const { currentCompanyId } = useAuth()

  async function getCompany(): Promise<Company | null> {
    if (!currentCompanyId.value) return null
    return $fetch<Company>(`/api/companies/${currentCompanyId.value}`, { credentials: 'include' })
  }

  async function create(data: Partial<Company>) {
    return $fetch<Company>('/api/companies', {
      method: 'POST',
      body: data,
      credentials: 'include'
    })
  }

  async function update(data: Partial<Company>) {
    if (!currentCompanyId.value) throw new Error('Nenhuma empresa selecionada')
    return $fetch<Company>(`/api/companies/${currentCompanyId.value}`, {
      method: 'PUT',
      body: data,
      credentials: 'include'
    })
  }

  async function getMembers(): Promise<Member[]> {
    if (!currentCompanyId.value) return []
    return $fetch<Member[]>(`/api/companies/${currentCompanyId.value}/members`, { credentials: 'include' })
  }

  async function addMember(email: string, role: string) {
    if (!currentCompanyId.value) throw new Error('Nenhuma empresa selecionada')
    return $fetch(`/api/companies/${currentCompanyId.value}/members`, {
      method: 'POST',
      body: { email, role },
      credentials: 'include'
    })
  }

  async function updateMemberRole(userId: number, role: string) {
    if (!currentCompanyId.value) throw new Error('Nenhuma empresa selecionada')
    return $fetch(`/api/companies/${currentCompanyId.value}/members/${userId}`, {
      method: 'PUT',
      body: { role },
      credentials: 'include'
    })
  }

  async function removeMember(userId: number) {
    if (!currentCompanyId.value) throw new Error('Nenhuma empresa selecionada')
    return $fetch(`/api/companies/${currentCompanyId.value}/members/${userId}`, {
      method: 'DELETE',
      credentials: 'include'
    })
  }

  return {
    getCompany,
    create,
    update,
    getMembers,
    addMember,
    updateMemberRole,
    removeMember
  }
}

export const useCompany = createSharedComposable(_useCompany)
