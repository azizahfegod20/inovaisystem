<script setup lang="ts">
import type { Certificate } from '~/types'

defineProps<{
  certificates: Certificate[]
}>()

const emit = defineEmits<{
  deleted: [id: number]
}>()

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>

<template>
  <div v-if="!certificates.length" class="text-sm text-muted py-4 text-center">
    Nenhum certificado cadastrado.
  </div>

  <div v-else class="flex flex-col gap-3">
    <div
      v-for="cert in certificates"
      :key="cert.id"
      class="flex items-center justify-between p-3 rounded-lg border border-default"
    >
      <div>
        <p class="font-medium text-sm">
          {{ cert.common_name }}
        </p>
        <p class="text-xs text-muted">
          CNPJ: {{ cert.cnpj }} · Válido: {{ formatDate(cert.valid_from) }} — {{ formatDate(cert.valid_to) }}
        </p>
        <div class="flex gap-2 mt-1">
          <UBadge v-if="cert.is_active" color="success" variant="subtle" size="xs">
            Ativo
          </UBadge>
          <UBadge v-if="cert.is_expired" color="error" variant="subtle" size="xs">
            Expirado
          </UBadge>
          <UBadge v-else-if="cert.is_expiring_soon" color="warning" variant="subtle" size="xs">
            Expirando
          </UBadge>
        </div>
      </div>

      <UButton
        icon="i-lucide-trash"
        variant="ghost"
        color="error"
        size="xs"
        @click="emit('deleted', cert.id)"
      />
    </div>
  </div>
</template>
