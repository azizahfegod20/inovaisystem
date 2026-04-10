<script setup lang="ts">
import { h, resolveComponent } from 'vue'
import type { TableColumn } from '@nuxt/ui'
import type { Period, Range, Invoice } from '~/types'

const props = defineProps<{
  period: Period
  range: Range
}>()

const UBadge = resolveComponent('UBadge')

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

const { data } = await useAsyncData('recent-invoices', async () => {
  try {
    const result = await $fetch<{ data: Invoice[] }>('/api/invoices', {
      params: {
        per_page: 5,
        date_from: props.range.start.toISOString().slice(0, 10),
        date_to: props.range.end.toISOString().slice(0, 10)
      },
      credentials: 'include'
    })
    return result.data ?? []
  } catch {
    return []
  }
}, {
  watch: [() => props.period, () => props.range],
  default: () => []
})

const columns: TableColumn<Invoice>[] = [
  {
    accessorKey: 'numero_nfse',
    header: 'Nº',
    cell: ({ row }) => row.original.numero_nfse ? `#${row.original.numero_nfse}` : `DPS ${row.original.dps_number}`
  },
  {
    accessorKey: 'data_emissao',
    header: 'Data',
    cell: ({ row }) => {
      return new Date(row.original.data_emissao).toLocaleString('pt-BR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      })
    }
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      return h(UBadge, {
        class: 'capitalize',
        variant: 'subtle',
        color: statusColor(row.original.status) as any
      }, () => statusLabel(row.original.status))
    }
  },
  {
    accessorKey: 'customer',
    header: 'Tomador',
    cell: ({ row }) => row.original.customer?.razao_social ?? '—'
  },
  {
    accessorKey: 'valor_servico',
    header: () => h('div', { class: 'text-right' }, 'Valor'),
    cell: ({ row }) => {
      const formatted = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      }).format(row.original.valor_servico)

      return h('div', { class: 'text-right font-medium' }, formatted)
    }
  }
]
</script>

<template>
  <UTable
    :data="data"
    :columns="columns"
    class="shrink-0"
    :ui="{
      base: 'table-fixed border-separate border-spacing-0',
      thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
      tbody: '[&>tr]:last:[&>td]:border-b-0',
      th: 'first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
      td: 'border-b border-default'
    }"
  />
</template>
