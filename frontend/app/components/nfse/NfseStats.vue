<script setup lang="ts">
import type { Period, Range, DashboardStats } from '~/types'

const props = defineProps<{
  period: Period
  range: Range
}>()

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    maximumFractionDigits: 0
  })
}

const { data: stats } = await useAsyncData<DashboardStats>('nfse-stats', async () => {
  try {
    return await $fetch<DashboardStats>('/api/dashboard/stats', {
      params: {
        date_from: props.range.start.toISOString().slice(0, 10),
        date_to: props.range.end.toISOString().slice(0, 10)
      },
      credentials: 'include'
    })
  } catch {
    return {
      total_notas: 0,
      total_receita: 0,
      total_canceladas: 0,
      total_iss: 0,
      total_retencoes: 0
    }
  }
}, {
  watch: [() => props.period, () => props.range],
  default: () => ({
    total_notas: 0,
    total_receita: 0,
    total_canceladas: 0,
    total_iss: 0,
    total_retencoes: 0
  })
})

const cards = computed(() => [
  {
    title: 'Notas Emitidas',
    icon: 'i-lucide-file-text',
    value: stats.value?.total_notas ?? 0,
    to: '/invoices'
  },
  {
    title: 'Receita',
    icon: 'i-lucide-circle-dollar-sign',
    value: formatCurrency(stats.value?.total_receita ?? 0),
    to: '/invoices'
  },
  {
    title: 'ISS Total',
    icon: 'i-lucide-landmark',
    value: formatCurrency(stats.value?.total_iss ?? 0),
    to: '/invoices'
  },
  {
    title: 'Canceladas',
    icon: 'i-lucide-x-circle',
    value: stats.value?.total_canceladas ?? 0,
    to: '/invoices?status=cancelled'
  }
])
</script>

<template>
  <UPageGrid class="lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-px">
    <UPageCard
      v-for="(card, index) in cards"
      :key="index"
      :icon="card.icon"
      :title="card.title"
      :to="card.to"
      variant="subtle"
      :ui="{
        container: 'gap-y-1.5',
        wrapper: 'items-start',
        leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
        title: 'font-normal text-muted text-xs uppercase'
      }"
      class="lg:rounded-none first:rounded-l-lg last:rounded-r-lg hover:z-1"
    >
      <div class="flex items-center gap-2">
        <span class="text-2xl font-semibold text-highlighted">
          {{ card.value }}
        </span>
      </div>
    </UPageCard>
  </UPageGrid>
</template>
