<script setup lang="ts">
import type { Certificate } from '~/types'

const toast = useToast()
const loading = ref(false)
const pfxFile = ref<File | null>(null)
const password = ref('')

const { data: certificates, refresh } = await useFetch<Certificate[]>('/api/certificates', {
  credentials: 'include',
  lazy: true
})

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  pfxFile.value = target.files?.[0] ?? null
}

async function uploadCertificate() {
  if (!pfxFile.value || !password.value) {
    toast.add({ title: 'Selecione o arquivo e informe a senha', color: 'warning' })
    return
  }

  loading.value = true
  const formData = new FormData()
  formData.append('pfx_file', pfxFile.value)
  formData.append('password', password.value)

  try {
    await $fetch('/api/certificates', {
      method: 'POST',
      body: formData,
      credentials: 'include'
    })
    toast.add({ title: 'Certificado enviado com sucesso', color: 'success' })
    pfxFile.value = null
    password.value = ''
    await refresh()
  } catch (err: any) {
    toast.add({ title: 'Erro ao enviar certificado', description: err?.data?.message, color: 'error' })
  } finally {
    loading.value = false
  }
}

async function deleteCertificate(id: number) {
  try {
    await $fetch(`/api/certificates/${id}`, { method: 'DELETE', credentials: 'include' })
    toast.add({ title: 'Certificado removido', color: 'success' })
    await refresh()
  } catch (err: any) {
    toast.add({ title: 'Erro', description: err?.data?.message, color: 'error' })
  }
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>

<template>
  <UDashboardPanelContent>
    <div class="max-w-2xl flex flex-col gap-6">
      <!-- Upload -->
      <UCard>
        <template #header>
          <h3 class="font-semibold">
            Enviar Certificado Digital A1
          </h3>
        </template>

        <form class="flex flex-col gap-4" @submit.prevent="uploadCertificate">
          <UFormField label="Arquivo .pfx / .p12">
            <input
              type="file"
              accept=".pfx,.p12"
              class="block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20"
              @change="onFileChange"
            >
          </UFormField>

          <UFormField label="Senha do Certificado">
            <UInput v-model="password" type="password" placeholder="Digite a senha" class="w-full" />
          </UFormField>

          <UButton type="submit" label="Enviar Certificado" icon="i-lucide-upload" :loading="loading" />
        </form>
      </UCard>

      <!-- Lista -->
      <UCard>
        <template #header>
          <h3 class="font-semibold">
            Certificados Cadastrados
          </h3>
        </template>

        <div v-if="!certificates?.length" class="text-sm text-muted py-4 text-center">
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
              @click="deleteCertificate(cert.id)"
            />
          </div>
        </div>
      </UCard>
    </div>
  </UDashboardPanelContent>
</template>
