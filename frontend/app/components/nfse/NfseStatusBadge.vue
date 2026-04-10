<script setup lang="ts">
import type { InvoiceStatus } from '~/types'

const props = defineProps<{
  status: InvoiceStatus
}>()

const colorMap: Record<string, string> = {
  authorized: 'success',
  pending: 'warning',
  processing: 'info',
  cancelled: 'error',
  rejected: 'error',
  replaced: 'neutral',
  error: 'error'
}

const labelMap: Record<string, string> = {
  authorized: 'Autorizada',
  pending: 'Pendente',
  processing: 'Processando',
  cancelled: 'Cancelada',
  rejected: 'Rejeitada',
  replaced: 'Substituída',
  error: 'Erro'
}

const color = computed(() => (colorMap[props.status] ?? 'neutral') as any)
const label = computed(() => labelMap[props.status] ?? props.status)
</script>

<template>
  <UBadge :color="color" variant="subtle">
    {{ label }}
  </UBadge>
</template>
