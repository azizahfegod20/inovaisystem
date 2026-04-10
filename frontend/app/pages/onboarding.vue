<script setup lang="ts">
import type { CnpjLookup } from '~/types'

definePageMeta({ layout: false })

const { selectCompany, fetchUser } = useAuth()
const toast = useToast()

const step = ref(1)
const loading = ref(false)
const lookupLoading = ref(false)

const form = reactive({
  cnpj: '',
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
  telefone: '',
  email: '',
  regime_tributario: 1,
  ambiente: 2
})

async function lookupCnpj() {
  const cnpj = form.cnpj.replace(/\D/g, '')
  if (cnpj.length !== 14) {
    toast.add({ title: 'CNPJ deve ter 14 dígitos', color: 'warning' })
    return
  }

  lookupLoading.value = true
  try {
    const data = await $fetch<CnpjLookup>(`/api/cnpj-lookup/${cnpj}`, { credentials: 'include' })
    form.razao_social = data.razao_social ?? ''
    form.nome_fantasia = data.nome_fantasia ?? ''
    form.logradouro = data.logradouro ?? ''
    form.numero = data.numero ?? ''
    form.complemento = data.complemento ?? ''
    form.bairro = data.bairro ?? ''
    form.codigo_ibge = data.codigo_ibge ?? ''
    form.uf = data.uf ?? ''
    form.cep = data.cep ?? ''
    form.email = data.email ?? ''
    form.telefone = data.telefone ?? ''
    if (data.simples_nacional) form.regime_tributario = 1
    step.value = 2
    toast.add({ title: 'CNPJ encontrado', color: 'success' })
  } catch (err: any) {
    toast.add({ title: 'Erro ao buscar CNPJ', description: err?.data?.message, color: 'error' })
  } finally {
    lookupLoading.value = false
  }
}

async function createCompany() {
  loading.value = true
  try {
    const company = await $fetch<{ id: number }>('/api/companies', {
      method: 'POST',
      body: { ...form, cnpj: form.cnpj.replace(/\D/g, ''), cep: form.cep.replace(/\D/g, '') },
      credentials: 'include'
    })
    await selectCompany(company.id)
    await fetchUser()
    navigateTo('/')
  } catch (err: any) {
    toast.add({ title: 'Erro ao cadastrar empresa', description: err?.data?.message, color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-default px-4">
    <UCard class="w-full max-w-lg">
      <template #header>
        <div class="text-center">
          <h1 class="text-2xl font-bold text-highlighted">
            Cadastrar Empresa
          </h1>
          <p class="mt-1 text-sm text-muted">
            {{ step === 1 ? 'Informe o CNPJ para auto-preenchimento' : 'Confirme os dados da empresa' }}
          </p>
        </div>
      </template>

      <!-- Step 1: CNPJ lookup -->
      <form v-if="step === 1" class="flex flex-col gap-4" @submit.prevent="lookupCnpj">
        <UFormField label="CNPJ">
          <UInput
            v-model="form.cnpj"
            placeholder="00.000.000/0000-00"
            icon="i-lucide-building-2"
            required
            autofocus
            class="w-full"
          />
        </UFormField>

        <UButton type="submit" label="Buscar CNPJ" block :loading="lookupLoading" />

        <UButton
          label="Preencher manualmente"
          variant="ghost"
          color="neutral"
          block
          @click="step = 2"
        />
      </form>

      <!-- Step 2: Company form -->
      <form v-else class="flex flex-col gap-4" @submit.prevent="createCompany">
        <div class="grid grid-cols-2 gap-3">
          <UFormField label="CNPJ" class="col-span-2">
            <UInput v-model="form.cnpj" required class="w-full" />
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
            <UInput v-model="form.cep" required class="w-full" />
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

          <UFormField label="E-mail" class="col-span-2">
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

        <div class="flex gap-2">
          <UButton
            label="Voltar"
            variant="outline"
            color="neutral"
            class="flex-1"
            @click="step = 1"
          />
          <UButton
            type="submit"
            label="Cadastrar Empresa"
            class="flex-1"
            :loading="loading"
          />
        </div>
      </form>
    </UCard>
  </div>
</template>
