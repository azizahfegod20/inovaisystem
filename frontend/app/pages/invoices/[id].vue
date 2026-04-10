<script setup lang="ts">
import type { Invoice } from '~/types'

const route = useRoute()
const toast = useToast()
const router = useRouter()

const invoiceId = route.params.id as string

const { data: invoice, refresh } = await useFetch<Invoice>(`/api/invoices/${invoiceId}`, {
  credentials: 'include'
})

const showCancelModal = ref(false)
const cancelMotivo = ref('')
const cancelLoading = ref(false)

function statusColor(s: string) {
  return ({
    authorized: 'success',
    pending: 'warning',
    processing: 'info',
    cancelled: 'error',
    rejected: 'error',
    replaced: 'neutral',
    error: 'error'
  } as Record<string, string>)[s] ?? 'neutral'
}

function statusLabel(s: string) {
  return ({
    authorized: 'Autorizada',
    pending: 'Pendente',
    processing: 'Processando',
    cancelled: 'Cancelada',
    rejected: 'Rejeitada',
    replaced: 'Substituída',
    error: 'Erro'
  } as Record<string, string>)[s] ?? s
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

function formatDate(dateStr?: string) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function cancelInvoice() {
  if (cancelMotivo.value.length < 15) {
    toast.add({ title: 'Motivo deve ter pelo menos 15 caracteres', color: 'warning' })
    return
  }

  cancelLoading.value = true
  try {
    await $fetch(`/api/invoices/${invoiceId}/cancel`, {
      method: 'POST',
      body: { motivo: cancelMotivo.value },
      credentials: 'include'
    })
    toast.add({ title: 'NFS-e cancelada com sucesso', color: 'success' })
    showCancelModal.value = false
    await refresh()
  } catch (err: any) {
    toast.add({ title: 'Erro ao cancelar', description: err?.data?.message, color: 'error' })
  } finally {
    cancelLoading.value = false
  }
}
</script>

<template>
  <UDashboardPanel id="invoice-detail">
    <template #header>
      <UDashboardNavbar :title="`NFS-e #${invoice?.numero_nfse ?? invoice?.id ?? ''}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UButton
            label="Voltar"
            variant="ghost"
            color="neutral"
            icon="i-lucide-arrow-left"
            to="/invoices"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div v-if="invoice" class="max-w-3xl mx-auto flex flex-col gap-6">
        <!-- Status Header -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <UBadge :color="statusColor(invoice.status) as any" variant="subtle" size="lg">
              {{ statusLabel(invoice.status) }}
            </UBadge>
            <span v-if="invoice.chave_acesso" class="text-xs font-mono text-muted">
              {{ invoice.chave_acesso }}
            </span>
          </div>

          <div class="flex gap-2">
            <UButton
              v-if="invoice.status === 'authorized'"
              label="PDF"
              icon="i-lucide-download"
              variant="outline"
              size="sm"
              :href="`/api/invoices/${invoice.id}/pdf`"
              target="_blank"
            />
            <UButton
              label="XML"
              icon="i-lucide-file-code"
              variant="outline"
              color="neutral"
              size="sm"
              :href="`/api/invoices/${invoice.id}/xml`"
              target="_blank"
            />
            <UButton
              v-if="invoice.status === 'authorized'"
              label="Cancelar"
              icon="i-lucide-x-circle"
              variant="outline"
              color="error"
              size="sm"
              @click="showCancelModal = true"
            />
            <UButton
              v-if="invoice.status === 'authorized'"
              label="Substituir"
              icon="i-lucide-replace"
              variant="outline"
              color="warning"
              size="sm"
              :to="`/invoices/new?replace=${invoice.id}`"
            />
          </div>
        </div>

        <!-- Dados da Nota -->
        <UCard>
          <template #header>
            <h3 class="font-semibold">
              Dados da Nota
            </h3>
          </template>

          <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div>
              <p class="text-muted">Nº NFS-e</p>
              <p class="font-medium">{{ invoice.numero_nfse ?? '—' }}</p>
            </div>
            <div>
              <p class="text-muted">ID DPS</p>
              <p class="font-mono text-xs">{{ invoice.id_dps }}</p>
            </div>
            <div>
              <p class="text-muted">Série / Número</p>
              <p class="font-medium">{{ invoice.dps_serie }} / {{ invoice.dps_number }}</p>
            </div>
            <div>
              <p class="text-muted">Data Emissão</p>
              <p class="font-medium">{{ formatDate(invoice.data_emissao) }}</p>
            </div>
            <div v-if="invoice.data_cancelamento">
              <p class="text-muted">Data Cancelamento</p>
              <p class="font-medium text-error">{{ formatDate(invoice.data_cancelamento) }}</p>
            </div>
          </div>
        </UCard>

        <!-- Tomador -->
        <UCard v-if="invoice.customer">
          <template #header>
            <h3 class="font-semibold">
              Tomador
            </h3>
          </template>

          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-muted">Razão Social</p>
              <p class="font-medium">{{ invoice.customer.razao_social }}</p>
            </div>
            <div>
              <p class="text-muted">Documento</p>
              <p class="font-medium">{{ invoice.customer.documento }}</p>
            </div>
            <div>
              <p class="text-muted">Endereço</p>
              <p>{{ invoice.customer.logradouro }}, {{ invoice.customer.numero }} — {{ invoice.customer.bairro }}</p>
            </div>
            <div>
              <p class="text-muted">E-mail</p>
              <p>{{ invoice.customer.email ?? '—' }}</p>
            </div>
          </div>
        </UCard>

        <!-- Valores -->
        <UCard>
          <template #header>
            <h3 class="font-semibold">
              Valores
            </h3>
          </template>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
              <p class="text-muted">Valor Serviço</p>
              <p class="font-semibold">{{ formatCurrency(invoice.valor_servico) }}</p>
            </div>
            <div>
              <p class="text-muted">Deduções</p>
              <p>{{ formatCurrency(invoice.valor_deducoes) }}</p>
            </div>
            <div>
              <p class="text-muted">Desconto</p>
              <p>{{ formatCurrency(invoice.valor_desconto) }}</p>
            </div>
            <div>
              <p class="text-muted">Valor Líquido</p>
              <p class="font-bold text-primary">{{ formatCurrency(invoice.valor_liquido) }}</p>
            </div>
            <div>
              <p class="text-muted">Alíquota ISS</p>
              <p>{{ (invoice.aliquota_iss * 100).toFixed(2) }}%</p>
            </div>
            <div>
              <p class="text-muted">ISS</p>
              <p>{{ formatCurrency(invoice.valor_iss) }}</p>
            </div>
            <div>
              <p class="text-muted">ISS Retido</p>
              <p>{{ invoice.iss_retido ? 'Sim' : 'Não' }}</p>
            </div>
          </div>

          <div v-if="invoice.valor_ir || invoice.valor_csll || invoice.valor_cofins || invoice.valor_pis || invoice.valor_inss" class="mt-4 border-t border-default pt-4">
            <p class="text-sm font-semibold mb-2">
              Retenções Federais
            </p>
            <div class="grid grid-cols-5 gap-4 text-sm">
              <div><p class="text-muted">IR</p><p>{{ formatCurrency(invoice.valor_ir) }}</p></div>
              <div><p class="text-muted">CSLL</p><p>{{ formatCurrency(invoice.valor_csll) }}</p></div>
              <div><p class="text-muted">COFINS</p><p>{{ formatCurrency(invoice.valor_cofins) }}</p></div>
              <div><p class="text-muted">PIS</p><p>{{ formatCurrency(invoice.valor_pis) }}</p></div>
              <div><p class="text-muted">INSS</p><p>{{ formatCurrency(invoice.valor_inss) }}</p></div>
            </div>
          </div>
        </UCard>

        <!-- Descrição -->
        <UCard>
          <template #header>
            <h3 class="font-semibold">
              Descrição do Serviço
            </h3>
          </template>

          <p class="whitespace-pre-wrap text-sm">
            {{ invoice.descricao_servico }}
          </p>
        </UCard>

        <!-- Motivo cancelamento -->
        <UCard v-if="invoice.motivo_cancelamento">
          <template #header>
            <h3 class="font-semibold text-error">
              Motivo do Cancelamento
            </h3>
          </template>

          <p class="text-sm">
            {{ invoice.motivo_cancelamento }}
          </p>
        </UCard>
      </div>

      <!-- Cancel Modal -->
      <UModal v-model:open="showCancelModal">
        <template #content>
          <UCard>
            <template #header>
              <h3 class="font-semibold">
                Cancelar NFS-e
              </h3>
            </template>

            <form @submit.prevent="cancelInvoice">
              <UFormField label="Motivo do cancelamento (mín. 15 caracteres)">
                <UTextarea
                  v-model="cancelMotivo"
                  placeholder="Descreva o motivo do cancelamento..."
                  :rows="3"
                  class="w-full"
                />
              </UFormField>

              <div class="flex justify-end gap-2 mt-4">
                <UButton
                  label="Voltar"
                  variant="ghost"
                  color="neutral"
                  @click="showCancelModal = false"
                />
                <UButton
                  type="submit"
                  label="Confirmar Cancelamento"
                  color="error"
                  :loading="cancelLoading"
                />
              </div>
            </form>
          </UCard>
        </template>
      </UModal>
    </template>
  </UDashboardPanel>
</template>
