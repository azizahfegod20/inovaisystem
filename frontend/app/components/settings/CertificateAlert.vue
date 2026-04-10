<script setup lang="ts">
import type { Certificate } from '~/types'

const { isAuthenticated } = useAuth()

const { data: certificates } = await useFetch<Certificate[]>('/api/certificates', {
  credentials: 'include',
  lazy: true,
  server: false,
  immediate: isAuthenticated.value
})

const expiredCerts = computed(() =>
  (certificates.value ?? []).filter(c => c.is_expired)
)

const expiringCerts = computed(() =>
  (certificates.value ?? []).filter(c => c.is_expiring_soon && !c.is_expired)
)

const showAlert = computed(() => expiredCerts.value.length > 0 || expiringCerts.value.length > 0)
</script>

<template>
  <div v-if="showAlert" class="flex flex-col gap-2 px-4 pb-2">
    <div
      v-if="expiredCerts.length"
      class="rounded-lg border border-error/30 bg-error/5 px-3 py-2 text-xs text-error flex items-center gap-2"
    >
      <UIcon name="i-lucide-shield-alert" class="size-4 shrink-0" />
      <span>Certificado expirado! Envie um novo em <NuxtLink to="/settings/certificate" class="underline font-medium">Configurações</NuxtLink>.</span>
    </div>

    <div
      v-else-if="expiringCerts.length"
      class="rounded-lg border border-warning/30 bg-warning/5 px-3 py-2 text-xs text-warning flex items-center gap-2"
    >
      <UIcon name="i-lucide-clock" class="size-4 shrink-0" />
      <span>Certificado vence em breve. <NuxtLink to="/settings/certificate" class="underline font-medium">Renove agora</NuxtLink>.</span>
    </div>
  </div>
</template>
