<script setup lang="ts">
import type { Company } from '~/types'

const { currentCompanyId } = useAuth()

const { data: company, refresh } = await useFetch<Company>(
  () => `/api/companies/${currentCompanyId.value}`,
  { credentials: 'include', lazy: true }
)
</script>

<template>
  <div>
    <UPageCard
      title="Dados da Empresa"
      description="Informações cadastrais utilizadas na emissão de NFS-e."
      variant="naked"
      orientation="horizontal"
      class="mb-4"
    />

    <UPageCard variant="subtle">
      <SettingsCompanyForm :company="company" @saved="refresh()" />
    </UPageCard>
  </div>
</template>
