<script setup lang="ts">
const props = withDefaults(defineProps<{
  count?: number
}>(), {
  count: 0
})

const emit = defineEmits<{
  confirmed: []
}>()

const open = ref(false)
const loading = ref(false)

async function onSubmit() {
  loading.value = true
  try {
    emit('confirmed')
    open.value = false
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    :title="`Excluir ${count} tomador${count > 1 ? 'es' : ''}`"
    description="Tem certeza? Esta ação não pode ser desfeita."
  >
    <slot />

    <template #body>
      <div class="flex justify-end gap-2">
        <UButton
          label="Cancelar"
          color="neutral"
          variant="subtle"
          @click="open = false"
        />
        <UButton
          label="Excluir"
          color="error"
          variant="solid"
          :loading="loading"
          @click="onSubmit"
        />
      </div>
    </template>
  </UModal>
</template>
