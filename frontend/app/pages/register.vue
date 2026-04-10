<script setup lang="ts">
definePageMeta({ layout: false })

const { register, isAuthenticated } = useAuth()
const toast = useToast()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})
const loading = ref(false)

if (isAuthenticated.value) {
  navigateTo('/')
}

async function onSubmit() {
  loading.value = true
  try {
    await register(form.name, form.email, form.password, form.password_confirmation)
    navigateTo('/onboarding')
  } catch (err: any) {
    const msg = err?.data?.message || 'Erro ao cadastrar.'
    toast.add({ title: 'Erro', description: msg, color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-default px-4">
    <UCard class="w-full max-w-sm">
      <template #header>
        <div class="text-center">
          <h1 class="text-2xl font-bold text-highlighted">
            Criar Conta
          </h1>
          <p class="mt-1 text-sm text-muted">
            Cadastre-se para emitir NFS-e
          </p>
        </div>
      </template>

      <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
        <UFormField label="Nome">
          <UInput
            v-model="form.name"
            placeholder="Seu nome"
            icon="i-lucide-user"
            required
            autofocus
            class="w-full"
          />
        </UFormField>

        <UFormField label="E-mail">
          <UInput
            v-model="form.email"
            type="email"
            placeholder="seu@email.com"
            icon="i-lucide-mail"
            required
            class="w-full"
          />
        </UFormField>

        <UFormField label="Senha">
          <UInput
            v-model="form.password"
            type="password"
            placeholder="Mín. 8 caracteres"
            icon="i-lucide-lock"
            required
            class="w-full"
          />
        </UFormField>

        <UFormField label="Confirmar Senha">
          <UInput
            v-model="form.password_confirmation"
            type="password"
            placeholder="Repita a senha"
            icon="i-lucide-lock"
            required
            class="w-full"
          />
        </UFormField>

        <UButton
          type="submit"
          label="Cadastrar"
          block
          :loading="loading"
        />
      </form>

      <template #footer>
        <p class="text-center text-sm text-muted">
          Já tem conta?
          <NuxtLink to="/login" class="text-primary font-medium">
            Entrar
          </NuxtLink>
        </p>
      </template>
    </UCard>
  </div>
</template>
