<script setup lang="ts">
definePageMeta({ layout: false })

const { login, isAuthenticated } = useAuth()
const toast = useToast()

const form = reactive({
  email: '',
  password: '',
  remember: false
})
const loading = ref(false)

if (isAuthenticated.value) {
  navigateTo('/')
}

async function onSubmit() {
  loading.value = true
  try {
    await login(form.email, form.password, form.remember)
    navigateTo('/')
  } catch (err: any) {
    toast.add({
      title: 'Erro ao entrar',
      description: err?.data?.message || 'Credenciais inválidas.',
      color: 'error'
    })
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
            Inovai NFS-e
          </h1>
          <p class="mt-1 text-sm text-muted">
            Entre com suas credenciais
          </p>
        </div>
      </template>

      <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
        <UFormField label="E-mail">
          <UInput
            v-model="form.email"
            type="email"
            placeholder="seu@email.com"
            icon="i-lucide-mail"
            required
            autofocus
            class="w-full"
          />
        </UFormField>

        <UFormField label="Senha">
          <UInput
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            icon="i-lucide-lock"
            required
            class="w-full"
          />
        </UFormField>

        <UCheckbox v-model="form.remember" label="Lembrar de mim" />

        <UButton
          type="submit"
          label="Entrar"
          block
          :loading="loading"
        />
      </form>

      <template #footer>
        <p class="text-center text-sm text-muted">
          Não tem conta?
          <NuxtLink to="/register" class="text-primary font-medium">
            Cadastre-se
          </NuxtLink>
        </p>
      </template>
    </UCard>
  </div>
</template>
