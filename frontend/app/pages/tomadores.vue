<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Customer, PaginatedResponse } from '~/types'

const UButton = resolveComponent('UButton')
const UBadge = resolveComponent('UBadge')

const toast = useToast()

const page = ref(1)
const perPage = ref(10)
const search = ref('')

const { data, status, refresh } = await useFetch<PaginatedResponse<Customer>>('/api/customers', {
  params: computed(() => ({
    page: page.value,
    per_page: perPage.value,
    search: search.value || undefined
  })),
  credentials: 'include',
  lazy: true
})

const tomadores = computed(() => data.value?.data ?? [])
const total = computed(() => data.value?.total ?? 0)

async function deleteTomador(id: number) {
  try {
    await $fetch(`/api/customers/${id}`, { method: 'DELETE', credentials: 'include' })
    toast.add({ title: 'Tomador excluído', color: 'success' })
    await refresh()
  } catch (err: any) {
    toast.add({ title: 'Erro ao excluir', description: err?.data?.message, color: 'error' })
  }
}

function formatDocumento(tipo: string, doc: string) {
  if (tipo === '1' && doc.length === 11) {
    return `${doc.slice(0, 3)}.${doc.slice(3, 6)}.${doc.slice(6, 9)}-${doc.slice(9)}`
  }
  if (tipo === '2' && doc.length === 14) {
    return `${doc.slice(0, 2)}.${doc.slice(2, 5)}.${doc.slice(5, 8)}/${doc.slice(8, 12)}-${doc.slice(12)}`
  }
  return doc
}

const columns: TableColumn<Customer>[] = [
  {
    accessorKey: 'documento',
    header: 'Documento',
    cell: ({ row }) => formatDocumento(row.original.tipo_documento, row.original.documento)
  },
  {
    accessorKey: 'razao_social',
    header: 'Razão Social'
  },
  {
    accessorKey: 'email',
    header: 'E-mail',
    cell: ({ row }) => row.original.email ?? '—'
  },
  {
    accessorKey: 'uf',
    header: 'UF'
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) => h('div', { class: 'flex gap-1 justify-end' }, [
      h(UButton, {
        icon: 'i-lucide-trash',
        variant: 'ghost',
        color: 'error',
        size: 'xs',
        onClick: () => deleteTomador(row.original.id)
      })
    ])
  }
]
</script>

<template>
  <UDashboardPanel id="tomadores">
    <template #header>
      <UDashboardNavbar title="Tomadores">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <TomadoresAddModal @saved="refresh()" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <UInput
          v-model="search"
          icon="i-lucide-search"
          placeholder="Buscar tomador..."
          class="max-w-xs"
        />
      </div>

      <UTable
        :data="tomadores"
        :columns="columns"
        :loading="status === 'pending'"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default',
          separator: 'h-0'
        }"
      />

      <div v-if="total > perPage" class="flex justify-center pt-4 mt-auto">
        <UPagination
          :default-page="page"
          :items-per-page="perPage"
          :total="total"
          @update:page="(p: number) => { page = p; refresh() }"
        />
      </div>
    </template>
  </UDashboardPanel>
</template>
