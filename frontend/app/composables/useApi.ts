import type { UseFetchOptions } from 'nuxt/app'

export function useApi<T>(url: string | (() => string), options?: UseFetchOptions<T>) {
  return useFetch<T>(url, {
    ...options,
    $fetch: useRequestFetch()
  })
}
