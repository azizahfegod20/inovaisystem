<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { ServiceItem } from '~/types'

const UButton = resolveComponent('UButton')
const UBadge = resolveComponent('UBadge')

const toast = useToast()

const { data: services, status, refresh } = await useFetch<ServiceItem[]>('/api/services', {
  credentials: 'include',
  lazy: true
})

const showAddModal = ref(false)
const editingService = ref<ServiceItem | null>(null)
const loading = ref(false)

const form = reactive({
  codigo_lc116: '',
  codigo_nbs: '',
  descricao: '',
  aliquota_iss: 0.05,
  is_favorite: false
})

function resetForm() {
  form.codigo_lc116 = ''
  form.codigo_nbs = ''
  form.descricao = ''
  form.aliquota_iss = 0.05
  form.is_favorite = false
  editingService.value = null
}

function openEdit(service: ServiceItem) {
  editingService.value = service
  form.codigo_lc116 = service.codigo_lc116
  form.codigo_nbs = service.codigo_nbs ?? ''
  form.descricao = service.descricao
  form.aliquota_iss = service.aliquota_iss
  form.is_favorite = service.is_favorite
  showAddModal.value = true
}

async function onSubmit() {
  loading.value = true
  try {
    if (editingService.value) {
      await $fetch(`/api/services/${editingService.value.id}`, {
        method: 'PUT',
        body: form,
        credentials: 'include'
      })
      toast.add({ title: 'Serviço atualizado', color: 'success' })
    } else {
      await $fetch('/api/services', {
        method: 'POST',
        body: form,
        credentials: 'include'
      })
      toast.add({ title: 'Serviço cadastrado', color: 'success' })
    }
    showAddModal.value = false
    resetForm()
    await refresh()
  } catch (err: any) {
    toast.add({ title: 'Erro', description: err?.data?.message, color: 'error' })
  } finally {
    loading.value = false
  }
}

async function deleteService(id: number) {
  try {
    await $fetch(`/api/services/${id}`, { method: 'DELETE', credentials: 'include' })
    toast.add({ title: 'Serviço excluído', color: 'success' })
    await refresh()
  } catch (err: any) {
    toast.add({ title: 'Erro ao excluir', description: err?.data?.message, color: 'error' })
  }
}

const columns: TableColumn<ServiceItem>[] = [
  { accessorKey: 'codigo_lc116', header: 'Código LC' },
  { accessorKey: 'descricao', header: 'Descrição' },
  {
    accessorKey: 'aliquota_iss',
    header: 'Alíquota ISS',
    cell: ({ row }) => `${(row.original.aliquota_iss * 100).toFixed(2)}%`
  },
  {
    accessorKey: 'is_favorite',
    header: 'Favorito',
    cell: ({ row }) => h(UBadge, {
      variant: 'subtle',
      color: row.original.is_favorite ? 'warning' : 'neutral'
    }, () => row.original.is_favorite ? '★' : '—')
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) => h('div', { class: 'flex gap-1 justify-end' }, [
      h(UButton, { icon: 'i-lucide-pencil', variant: 'ghost', color: 'neutral', size: 'xs', onClick: () => openEdit(row.original) }),
      h(UButton, { icon: 'i-lucide-trash', variant: 'ghost', color: 'error', size: 'xs', onClick: () => deleteService(row.original.id) })
    ])
  }
]
</script>

<template>
  <UDashboardPanel id="services">
    <template #header>
      <UDashboardNavbar title="Serviços">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton label="Novo Serviço" icon="i-lucide-plus" @click="resetForm(); showAddModal = true" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UTable
        :data="services ?? []"
        :columns="columns"
        :loading="status === 'pending'"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default',
          separator: 'h-0'
        }"
      />

      <UModal v-model:open="showAddModal">
        <template #content>
          <UCard>
            <template #header>
              <h3 class="font-semibold">
                {{ editingService ? 'Editar' : 'Novo' }} Serviço
              </h3>
            </template>

            <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
              <UFormField label="Código LC 116">
                <UInput v-model="form.codigo_lc116" placeholder="01.01" required class="w-full" />
              </UFormField>
              <UFormField label="Código NBS">
                <UInput v-model="form.codigo_nbs" placeholder="1.0101" class="w-full" />
              </UFormField>
              <UFormField label="Descrição">
                <UTextarea v-model="form.descricao" required :rows="2" class="w-full" />
              </UFormField>
              <UFormField label="Alíquota ISS">
                <UInput v-model.number="form.aliquota_iss" type="number" step="0.0001" min="0" max="1" class="w-full" />
              </UFormField>
              <UCheckbox v-model="form.is_favorite" label="Marcar como favorito" />

              <div class="flex justify-end gap-2">
                <UButton label="Cancelar" variant="ghost" color="neutral" @click="showAddModal = false" />
                <UButton type="submit" :label="editingService ? 'Salvar' : 'Cadastrar'" :loading="loading" />
              </div>
            </form>
          </UCard>
        </template>
      </UModal>
    </template>
  </UDashboardPanel>
</template>
