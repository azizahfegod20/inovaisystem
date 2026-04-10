<script setup lang="ts">
import type { CnpjLookup } from '~/types'

const emit = defineEmits<{
  saved: []
}>()

const open = ref(false)
const loading = ref(false)
const lookupLoading = ref(false)
const toast = useToast()

const state = reactive({
  tipo_documento: '2',
  documento: '',
  razao_social: '',
  nome_fantasia: '',
  inscricao_municipal: '',
  logradouro: '',
  numero: '',
  complemento: '',
  bairro: '',
  codigo_ibge: '',
  uf: '',
  cep: '',
  email: '',
  telefone: ''
})

function resetForm() {
  state.tipo_documento = '2'
  state.documento = ''
  state.razao_social = ''
  state.nome_fantasia = ''
  state.inscricao_municipal = ''
  state.logradouro = ''
  state.numero = ''
  state.complemento = ''
  state.bairro = ''
  state.codigo_ibge = ''
  state.uf = ''
  state.cep = ''
  state.email = ''
  state.telefone = ''
}

async function lookupCnpj() {
  const doc = state.documento.replace(/\D/g, '')
  if (doc.length !== 14) return

  lookupLoading.value = true
  try {
    const data = await $fetch<CnpjLookup>(`/api/cnpj-lookup/${doc}`, { credentials: 'include' })
    state.razao_social = data.razao_social ?? ''
    state.nome_fantasia = data.nome_fantasia ?? ''
    state.logradouro = data.logradouro ?? ''
    state.numero = data.numero ?? ''
    state.complemento = data.complemento ?? ''
    state.bairro = data.bairro ?? ''
    state.codigo_ibge = data.codigo_ibge ?? ''
    state.uf = data.uf ?? ''
    state.cep = data.cep ?? ''
    state.email = data.email ?? ''
    state.telefone = data.telefone ?? ''
    toast.add({ title: 'CNPJ encontrado', color: 'success' })
  } catch {
    toast.add({ title: 'CNPJ não encontrado', color: 'warning' })
  } finally {
    lookupLoading.value = false
  }
}

async function onSubmit() {
  loading.value = true
  try {
    await $fetch('/api/customers', {
      method: 'POST',
      body: {
        ...state,
        documento: state.documento.replace(/\D/g, ''),
        cep: state.cep.replace(/\D/g, '')
      },
      credentials: 'include'
    })
    toast.add({ title: 'Tomador cadastrado', color: 'success' })
    open.value = false
    resetForm()
    emit('saved')
  } catch (err: any) {
    toast.add({ title: 'Erro', description: err?.data?.message, color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Novo Tomador" description="Cadastre um novo tomador de serviço">
    <UButton label="Novo Tomador" icon="i-lucide-plus" />

    <template #body>
      <form class="space-y-4" @submit.prevent="onSubmit">
        <div class="grid grid-cols-2 gap-3">
          <UFormField label="Tipo">
            <USelect
              v-model="state.tipo_documento"
              :items="[
                { label: 'CNPJ', value: '2' },
                { label: 'CPF', value: '1' }
              ]"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Documento">
            <div class="flex gap-2">
              <UInput v-model="state.documento" class="flex-1" />
              <UButton
                v-if="state.tipo_documento === '2'"
                icon="i-lucide-search"
                variant="outline"
                color="neutral"
                :loading="lookupLoading"
                @click="lookupCnpj"
              />
            </div>
          </UFormField>

          <UFormField label="Razão Social" class="col-span-2">
            <UInput v-model="state.razao_social" required class="w-full" />
          </UFormField>

          <UFormField label="Nome Fantasia" class="col-span-2">
            <UInput v-model="state.nome_fantasia" class="w-full" />
          </UFormField>

          <UFormField label="Inscrição Municipal">
            <UInput v-model="state.inscricao_municipal" class="w-full" />
          </UFormField>

          <UFormField label="CEP">
            <UInput v-model="state.cep" class="w-full" />
          </UFormField>

          <UFormField label="Logradouro">
            <UInput v-model="state.logradouro" required class="w-full" />
          </UFormField>

          <UFormField label="Número">
            <UInput v-model="state.numero" required class="w-full" />
          </UFormField>

          <UFormField label="Complemento">
            <UInput v-model="state.complemento" class="w-full" />
          </UFormField>

          <UFormField label="Bairro">
            <UInput v-model="state.bairro" required class="w-full" />
          </UFormField>

          <UFormField label="UF">
            <UInput v-model="state.uf" maxlength="2" required class="w-full" />
          </UFormField>

          <UFormField label="Cód. IBGE">
            <UInput v-model="state.codigo_ibge" required class="w-full" />
          </UFormField>

          <UFormField label="E-mail">
            <UInput v-model="state.email" type="email" class="w-full" />
          </UFormField>

          <UFormField label="Telefone">
            <UInput v-model="state.telefone" class="w-full" />
          </UFormField>
        </div>

        <div class="flex justify-end gap-2">
          <UButton label="Cancelar" color="neutral" variant="subtle" @click="open = false" />
          <UButton label="Cadastrar" color="primary" variant="solid" type="submit" :loading="loading" />
        </div>
      </form>
    </template>
  </UModal>
</template>
