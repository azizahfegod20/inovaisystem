<script setup lang="ts">

const props = defineProps<{
  invoiceId: number
}>()

const emit = defineEmits<{
  cancelled: []
}>()

const open = ref(false)
const motivo = ref('')
const loading = ref(false)
const toast = useToast()

async function onSubmit() {
  if (motivo.value.length < 15) {
    toast.add({ title: 'Motivo deve ter pelo menos 15 caracteres', color: 'warning' })
    return
  }

  loading.value = true
  try {
    await $fetch(`/api/invoices/${props.invoiceId}/cancel`, {
      method: 'POST',
      body: { motivo: motivo.value },
      credentials: 'include'
    })
    toast.add({ title: 'NFS-e cancelada com sucesso', color: 'success' })
    open.value = false
    motivo.value = ''
    emit('cancelled')
  } catch (err: any) {
    toast.add({ title: 'Erro ao cancelar', description: err?.data?.message, color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Cancelar NFS-e" description="Informe o motivo do cancelamento">
    <slot />

    <template #body>
      <form class="space-y-4" @submit.prevent="onSubmit">
        <UFormField label="Motivo (mín. 15 caracteres)">
          <UTextarea
            v-model="motivo"
            placeholder="Descreva o motivo do cancelamento..."
            :rows="3"
            class="w-full"
          />
        </UFormField>

        <div class="flex justify-end gap-2">
          <UButton label="Voltar" color="neutral" variant="subtle" @click="open = false" />
          <UButton
            type="submit"
            label="Confirmar Cancelamento"
            color="error"
            variant="solid"
            :loading="loading"
          />
        </div>
      </form>
    </template>
  </UModal>
</template>
