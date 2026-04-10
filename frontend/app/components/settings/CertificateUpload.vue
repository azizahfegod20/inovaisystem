<script setup lang="ts">

const emit = defineEmits<{
  uploaded: []
}>()

const loading = ref(false)
const pfxFile = ref<File | null>(null)
const password = ref('')
const toast = useToast()

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  pfxFile.value = target.files?.[0] ?? null
}

async function onSubmit() {
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
    emit('uploaded')
  } catch (err: any) {
    toast.add({ title: 'Erro ao enviar certificado', description: err?.data?.message, color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
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
</template>
