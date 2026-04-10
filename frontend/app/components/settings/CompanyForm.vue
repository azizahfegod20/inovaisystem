<script setup lang="ts">
import type { Company } from '~/types'

const props = defineProps<{
  company?: Company | null
}>()

const emit = defineEmits<{
  saved: []
}>()

const toast = useToast()
const loading = ref(false)

const form = reactive({
  razao_social: props.company?.razao_social ?? '',
  nome_fantasia: props.company?.nome_fantasia ?? '',
  inscricao_municipal: props.company?.inscricao_municipal ?? '',
  logradouro: props.company?.logradouro ?? '',
  numero: props.company?.numero ?? '',
  complemento: props.company?.complemento ?? '',
  bairro: props.company?.bairro ?? '',
  codigo_ibge: props.company?.codigo_ibge ?? '',
  uf: props.company?.uf ?? '',
  cep: props.company?.cep ?? '',
  telefone: props.company?.telefone ?? '',
  email: props.company?.email ?? '',
  regime_tributario: props.company?.regime_tributario ?? 1,
  ambiente: props.company?.ambiente ?? 2
})

watch(() => props.company, (c) => {
  if (c) {
    Object.assign(form, {
      razao_social: c.razao_social,
      nome_fantasia: c.nome_fantasia ?? '',
      inscricao_municipal: c.inscricao_municipal ?? '',
      logradouro: c.logradouro,
      numero: c.numero,
      complemento: c.complemento ?? '',
      bairro: c.bairro,
      codigo_ibge: c.codigo_ibge,
      uf: c.uf,
      cep: c.cep,
      telefone: c.telefone ?? '',
      email: c.email ?? '',
      regime_tributario: c.regime_tributario,
      ambiente: c.ambiente
    })
  }
}, { deep: true })

async function onSubmit() {
  if (!props.company) return

  loading.value = true
  try {
    await $fetch(`/api/companies/${props.company.id}`, {
      method: 'PUT',
      body: form,
      credentials: 'include'
    })
    toast.add({ title: 'Empresa atualizada', color: 'success' })
    emit('saved')
  } catch (err: any) {
    toast.add({ title: 'Erro', description: err?.data?.message, color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div class="grid grid-cols-2 gap-3">
      <UFormField label="CNPJ" class="col-span-2">
        <UInput :model-value="company?.cnpj" disabled class="w-full" />
      </UFormField>

      <UFormField label="Razão Social" class="col-span-2">
        <UInput v-model="form.razao_social" required class="w-full" />
      </UFormField>

      <UFormField label="Nome Fantasia" class="col-span-2">
        <UInput v-model="form.nome_fantasia" class="w-full" />
      </UFormField>

      <UFormField label="Inscrição Municipal">
        <UInput v-model="form.inscricao_municipal" class="w-full" />
      </UFormField>

      <UFormField label="Regime Tributário">
        <USelect
          v-model="form.regime_tributario"
          :items="[
            { label: 'Simples Nacional', value: 1 },
            { label: 'Lucro Presumido', value: 2 },
            { label: 'Lucro Real', value: 3 }
          ]"
          class="w-full"
        />
      </UFormField>

      <UFormField label="CEP">
        <UInput v-model="form.cep" class="w-full" />
      </UFormField>

      <UFormField label="Logradouro">
        <UInput v-model="form.logradouro" required class="w-full" />
      </UFormField>

      <UFormField label="Número">
        <UInput v-model="form.numero" required class="w-full" />
      </UFormField>

      <UFormField label="Complemento">
        <UInput v-model="form.complemento" class="w-full" />
      </UFormField>

      <UFormField label="Bairro">
        <UInput v-model="form.bairro" required class="w-full" />
      </UFormField>

      <UFormField label="UF">
        <UInput v-model="form.uf" maxlength="2" required class="w-full" />
      </UFormField>

      <UFormField label="Cód. IBGE">
        <UInput v-model="form.codigo_ibge" required class="w-full" />
      </UFormField>

      <UFormField label="Telefone">
        <UInput v-model="form.telefone" class="w-full" />
      </UFormField>

      <UFormField label="E-mail">
        <UInput v-model="form.email" type="email" class="w-full" />
      </UFormField>

      <UFormField label="Ambiente" class="col-span-2">
        <USelect
          v-model="form.ambiente"
          :items="[
            { label: 'Homologação (Teste)', value: 2 },
            { label: 'Produção', value: 1 }
          ]"
          class="w-full"
        />
      </UFormField>
    </div>

    <div class="flex justify-end">
      <UButton type="submit" label="Salvar Alterações" :loading="loading" />
    </div>
  </form>
</template>
