<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Invoice, PaginatedResponse } from '~/types'

const UBadge = resolveComponent('UBadge')
const UButton = resolveComponent('UButton')

const toast = useToast()
const router = useRouter()

const page = ref(1)
const perPage = ref(10)
const statusFilter = ref('')
const search = ref('')

const { data, status, refresh } = await useFetch<PaginatedResponse<Invoice>>('/api/invoices', {
  params: computed(() => ({
    page: page.value,
    per_page: perPage.value,
    status: statusFilter.value || undefined,
    search: search.value || undefined
  })),
  credentials: 'include',
  lazy: true
})

const invoices = computed(() => data.value?.data ?? [])
const total = computed(() => data.value?.total ?? 0)

function statusColor(s: string) {
  return ({
    authorized: 'success',
    pending: 'warning',
    processing: 'info',
    cancelled: 'error',
    rejected: 'error',
    replaced: 'neutral',
    error: 'error'
  } as Record<string, string>)[s] ?? 'neutral'
}

function statusLabel(s: string) {
  return ({
    authorized: 'Autorizada',
    pending: 'Pendente',
    processing: 'Processando',
    cancelled: 'Cancelada',
    rejected: 'Rejeitada',
    replaced: 'Substituída',
    error: 'Erro'
  } as Record<string, string>)[s] ?? s
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

const columns: TableColumn<Invoice>[] = [
  { accessorKey: 'numero_nfse', header: 'Nº NFS-e' },
  { accessorKey: 'id_dps', header: 'ID DPS', cell: ({ row }) => h('span', { class: 'font-mono text-xs' }, row.original.id_dps.slice(-10)) },
  {
    accessorKey: 'customer',
    header: 'Tomador',
    cell: ({ row }) => row.original.customer?.razao_social ?? '—'
  },
  {
    accessorKey: 'valor_servico',
    header: 'Valor',
    cell: ({ row }) => formatCurrency(row.original.valor_servico)
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => h(UBadge, {
      variant: 'subtle',
      color: statusColor(row.original.status) as any
    }, () => statusLabel(row.original.status))
  },
  {
    accessorKey: 'data_emissao',
    header: 'Emissão',
    cell: ({ row }) => formatDate(row.original.data_emissao)
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) => h('div', { class: 'flex gap-1 justify-end' }, [
      h(UButton, {
        icon: 'i-lucide-eye',
        variant: 'ghost',
        color: 'neutral',
        size: 'xs',
        onClick: () => router.push(`/invoices/${row.original.id}`)
      }),
      row.original.status === 'authorized'
        ? h(UButton, {
            icon: 'i-lucide-download',
            variant: 'ghost',
            color: 'neutral',
            size: 'xs',
            onClick: () => window.open(`/api/invoices/${row.original.id}/pdf`, '_blank')
          })
        : null
    ])
  }
]
</script>

<template>
  <UDashboardPanel id="invoices">
    <template #header>
      <UDashboardNavbar title="Notas Fiscais">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UButton
            label="Emitir NFS-e"
            icon="i-lucide-plus"
            to="/invoices/new"
          />
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

        <USelect
          v-model="statusFilter"
          placeholder="Todos os status"
          :items="[
            { label: 'Todos', value: '' },
            { label: 'Autorizada', value: 'authorized' },
            { label: 'Pendente', value: 'pending' },
            { label: 'Cancelada', value: 'cancelled' },
            { label: 'Rejeitada', value: 'rejected' },
            { label: 'Substituída', value: 'replaced' }
          ]"
          class="min-w-36"
        />
      </div>

      <UTable
        :data="invoices"
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
