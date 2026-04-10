import type { Invoice, PaginatedResponse } from '~/types'

export function useInvoice() {
  async function emit(data: Record<string, any>): Promise<Invoice> {
    return $fetch<Invoice>('/api/invoices', {
      method: 'POST',
      body: data,
      credentials: 'include'
    })
  }

  async function getById(id: number | string): Promise<Invoice> {
    return $fetch<Invoice>(`/api/invoices/${id}`, { credentials: 'include' })
  }

  async function list(params?: Record<string, any>): Promise<PaginatedResponse<Invoice>> {
    return $fetch<PaginatedResponse<Invoice>>('/api/invoices', {
      params,
      credentials: 'include'
    })
  }

  async function cancel(id: number | string, motivo: string): Promise<Invoice> {
    return $fetch<Invoice>(`/api/invoices/${id}/cancel`, {
      method: 'POST',
      body: { motivo },
      credentials: 'include'
    })
  }

  async function replace(id: number | string, data: Record<string, any>): Promise<Invoice> {
    return $fetch<Invoice>(`/api/invoices/${id}/replace`, {
      method: 'POST',
      body: data,
      credentials: 'include'
    })
  }

  function downloadPdf(id: number | string) {
    window.open(`/api/invoices/${id}/pdf`, '_blank')
  }

  function downloadXml(id: number | string) {
    window.open(`/api/invoices/${id}/xml`, '_blank')
  }

  return {
    emit,
    getById,
    list,
    cancel,
    replace,
    downloadPdf,
    downloadXml
  }
}
