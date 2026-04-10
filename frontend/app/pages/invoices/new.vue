<script setup lang="ts">
import type { Customer, ServiceItem } from '~/types'

const toast = useToast()
const router = useRouter()

const loading = ref(false)

const { data: customers } = await useFetch<Customer[]>('/api/customers', {
  credentials: 'include',
  lazy: true
})

const { data: services } = await useFetch<ServiceItem[]>('/api/services', {
  credentials: 'include',
  lazy: true
})

const form = reactive({
  customer_id: null as number | null,
  service_id: null as number | null,
  descricao_servico: '',
  valor_servico: 0,
  valor_deducoes: 0,
  valor_desconto: 0,
  aliquota_iss: 0,
  iss_retido: false,
  valor_ir: 0,
  valor_csll: 0,
  valor_cofins: 0,
  valor_pis: 0,
  valor_inss: 0
})

const selectedService = computed(() =>
  services.value?.find(s => s.id === form.service_id)
)

watch(() => form.service_id, () => {
  if (selectedService.value) {
    form.aliquota_iss = selectedService.value.aliquota_iss
    if (!form.descricao_servico) {
      form.descricao_servico = selectedService.value.descricao
    }
  }
})

const baseCalculo = computed(() =>
  form.valor_servico - form.valor_deducoes - form.valor_desconto
)
const valorIss = computed(() =>
  Math.round(baseCalculo.value * form.aliquota_iss * 100) / 100
)
const valorLiquido = computed(() =>
  form.valor_servico - form.valor_desconto
)
const totalRetencoes = computed(() => {
  let total = form.valor_ir + form.valor_csll + form.valor_cofins + form.valor_pis + form.valor_inss
  if (form.iss_retido) total += valorIss.value
  return total
})

function formatCurrency(value: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

const customerOptions = computed(() =>
  (customers.value ?? []).map(c => ({
    label: `${c.razao_social} (${c.documento})`,
    value: c.id
  }))
)

const serviceOptions = computed(() =>
  (services.value ?? []).map(s => ({
    label: `${s.codigo_lc116} — ${s.descricao}`,
    value: s.id
  }))
)

async function onSubmit() {
  if (!form.customer_id || !form.service_id) {
    toast.add({ title: 'Selecione tomador e serviço', color: 'warning' })
    return
  }

  loading.value = true
  try {
    const result = await $fetch<{ id: number, chave_acesso: string }>('/api/invoices', {
      method: 'POST',
      body: form,
      credentials: 'include'
    })
    toast.add({ title: 'NFS-e emitida com sucesso!', color: 'success' })
    router.push(`/invoices/${result.id}`)
  } catch (err: any) {
    toast.add({
      title: 'Erro na emissão',
      description: err?.data?.message || 'Erro inesperado.',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UDashboardPanel id="new-invoice">
    <template #header>
      <UDashboardNavbar title="Emitir NFS-e">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <form class="max-w-3xl mx-auto flex flex-col gap-6" @submit.prevent="onSubmit">
        <!-- Tomador e Serviço -->
        <UCard>
          <template #header>
            <h3 class="font-semibold">
              Tomador e Serviço
            </h3>
          </template>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <UFormField label="Tomador" class="col-span-2">
              <USelectMenu
                v-model="form.customer_id"
                :items="customerOptions"
                value-key="value"
                placeholder="Selecione o tomador..."
                searchable
                class="w-full"
              />
            </UFormField>

            <UFormField label="Serviço" class="col-span-2">
              <USelectMenu
                v-model="form.service_id"
                :items="serviceOptions"
                value-key="value"
                placeholder="Selecione o serviço..."
                searchable
                class="w-full"
              />
            </UFormField>

            <UFormField label="Descrição do Serviço" class="col-span-2">
              <UTextarea
                v-model="form.descricao_servico"
                placeholder="Descreva o serviço prestado..."
                :rows="3"
                class="w-full"
              />
            </UFormField>
          </div>
        </UCard>

        <!-- Valores -->
        <UCard>
          <template #header>
            <h3 class="font-semibold">
              Valores
            </h3>
          </template>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <UFormField label="Valor do Serviço (R$)">
              <UInput v-model.number="form.valor_servico" type="number" step="0.01" min="0.01" required class="w-full" />
            </UFormField>

            <UFormField label="Deduções (R$)">
              <UInput v-model.number="form.valor_deducoes" type="number" step="0.01" min="0" class="w-full" />
            </UFormField>

            <UFormField label="Desconto (R$)">
              <UInput v-model.number="form.valor_desconto" type="number" step="0.01" min="0" class="w-full" />
            </UFormField>

            <UFormField label="Alíquota ISS">
              <UInput v-model.number="form.aliquota_iss" type="number" step="0.0001" min="0" max="1" class="w-full" />
            </UFormField>

            <UFormField label="ISS Retido pelo Tomador">
              <UCheckbox v-model="form.iss_retido" label="Sim, ISS retido" />
            </UFormField>
          </div>
        </UCard>

        <!-- Retenções Federais -->
        <UCard>
          <template #header>
            <h3 class="font-semibold">
              Retenções Federais (R$)
            </h3>
          </template>

          <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <UFormField label="IR">
              <UInput v-model.number="form.valor_ir" type="number" step="0.01" min="0" class="w-full" />
            </UFormField>
            <UFormField label="CSLL">
              <UInput v-model.number="form.valor_csll" type="number" step="0.01" min="0" class="w-full" />
            </UFormField>
            <UFormField label="COFINS">
              <UInput v-model.number="form.valor_cofins" type="number" step="0.01" min="0" class="w-full" />
            </UFormField>
            <UFormField label="PIS">
              <UInput v-model.number="form.valor_pis" type="number" step="0.01" min="0" class="w-full" />
            </UFormField>
            <UFormField label="INSS">
              <UInput v-model.number="form.valor_inss" type="number" step="0.01" min="0" class="w-full" />
            </UFormField>
          </div>
        </UCard>

        <!-- Resumo -->
        <UCard>
          <template #header>
            <h3 class="font-semibold">
              Resumo
            </h3>
          </template>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
              <p class="text-muted">
                Base de Cálculo
              </p>
              <p class="text-lg font-semibold">
                {{ formatCurrency(baseCalculo) }}
              </p>
            </div>
            <div>
              <p class="text-muted">
                ISS
              </p>
              <p class="text-lg font-semibold">
                {{ formatCurrency(valorIss) }}
              </p>
            </div>
            <div>
              <p class="text-muted">
                Total Retenções
              </p>
              <p class="text-lg font-semibold">
                {{ formatCurrency(totalRetencoes) }}
              </p>
            </div>
            <div>
              <p class="text-muted">
                Valor Líquido
              </p>
              <p class="text-lg font-bold text-primary">
                {{ formatCurrency(valorLiquido - totalRetencoes) }}
              </p>
            </div>
          </div>
        </UCard>

        <div class="flex justify-end gap-3">
          <UButton
            label="Cancelar"
            variant="outline"
            color="neutral"
            to="/invoices"
          />
          <UButton
            type="submit"
            label="Emitir NFS-e"
            icon="i-lucide-send"
            :loading="loading"
          />
        </div>
      </form>
    </template>
  </UDashboardPanel>
</template>
