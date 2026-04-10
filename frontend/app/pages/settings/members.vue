<script setup lang="ts">
const { currentCompanyId } = useAuth()
const { getMembers, addMember, updateMemberRole, removeMember } = useCompany()
const toast = useToast()

interface CompanyMember {
  user_id: number
  name: string
  email: string
  role: string
}

const members = ref<CompanyMember[]>([])
const loading = ref(false)

async function loadMembers() {
  members.value = await getMembers()
}

await loadMembers()

const q = ref('')
const filteredMembers = computed(() => {
  if (!q.value) return members.value
  const search = q.value.toLowerCase()
  return members.value.filter(m =>
    m.name.toLowerCase().includes(search) || m.email.toLowerCase().includes(search)
  )
})

const showInviteModal = ref(false)
const inviteForm = reactive({ email: '', role: 'operador' })
const inviteLoading = ref(false)

async function onInvite() {
  inviteLoading.value = true
  try {
    await addMember(inviteForm.email, inviteForm.role)
    toast.add({ title: 'Membro convidado', color: 'success' })
    showInviteModal.value = false
    inviteForm.email = ''
    inviteForm.role = 'operador'
    await loadMembers()
  } catch (err: any) {
    toast.add({ title: 'Erro', description: err?.data?.message, color: 'error' })
  } finally {
    inviteLoading.value = false
  }
}

async function onUpdateRole(userId: number, role: string) {
  try {
    await updateMemberRole(userId, role)
    toast.add({ title: 'Permissão atualizada', color: 'success' })
    await loadMembers()
  } catch (err: any) {
    toast.add({ title: 'Erro', description: err?.data?.message, color: 'error' })
  }
}

async function onRemove(userId: number) {
  try {
    await removeMember(userId)
    toast.add({ title: 'Membro removido', color: 'success' })
    await loadMembers()
  } catch (err: any) {
    toast.add({ title: 'Erro', description: err?.data?.message, color: 'error' })
  }
}
</script>

<template>
  <div>
    <UPageCard
      title="Membros"
      description="Gerencie os membros da empresa e suas permissões."
      variant="naked"
      orientation="horizontal"
      class="mb-4"
    >
      <UButton
        label="Convidar"
        color="neutral"
        icon="i-lucide-user-plus"
        class="w-fit lg:ms-auto"
        @click="showInviteModal = true"
      />
    </UPageCard>

    <UPageCard variant="subtle" :ui="{ container: 'p-0 sm:p-0 gap-y-0', wrapper: 'items-stretch', header: 'p-4 mb-0 border-b border-default' }">
      <template #header>
        <UInput
          v-model="q"
          icon="i-lucide-search"
          placeholder="Buscar membros..."
          autofocus
          class="w-full"
        />
      </template>

      <ul role="list" class="divide-y divide-default">
        <li
          v-for="member in filteredMembers"
          :key="member.user_id"
          class="flex items-center justify-between gap-3 py-3 px-4 sm:px-6"
        >
          <div class="flex items-center gap-3 min-w-0">
            <UAvatar :alt="member.name" size="md" />
            <div class="text-sm min-w-0">
              <p class="text-highlighted font-medium truncate">{{ member.name }}</p>
              <p class="text-muted truncate">{{ member.email }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <USelect
              :model-value="member.role"
              :items="[
                { label: 'Admin', value: 'admin' },
                { label: 'Contador', value: 'contador' },
                { label: 'Operador', value: 'operador' }
              ]"
              color="neutral"
              @update:model-value="(v: string) => onUpdateRole(member.user_id, v)"
            />

            <UButton
              icon="i-lucide-trash"
              color="error"
              variant="ghost"
              size="xs"
              @click="onRemove(member.user_id)"
            />
          </div>
        </li>
      </ul>
    </UPageCard>

    <UModal v-model:open="showInviteModal" title="Convidar Membro" description="Adicione um novo membro por e-mail.">
      <template #content>
        <UCard>
          <template #header>
            <h3 class="font-semibold">Convidar Membro</h3>
          </template>
          <form class="flex flex-col gap-4" @submit.prevent="onInvite">
            <UFormField label="E-mail">
              <UInput v-model="inviteForm.email" type="email" placeholder="email@exemplo.com" required class="w-full" />
            </UFormField>
            <UFormField label="Permissão">
              <USelect
                v-model="inviteForm.role"
                :items="[
                  { label: 'Admin', value: 'admin' },
                  { label: 'Contador', value: 'contador' },
                  { label: 'Operador', value: 'operador' }
                ]"
                class="w-full"
              />
            </UFormField>
            <div class="flex justify-end gap-2">
              <UButton label="Cancelar" variant="ghost" color="neutral" @click="showInviteModal = false" />
              <UButton type="submit" label="Convidar" :loading="inviteLoading" />
            </div>
          </form>
        </UCard>
      </template>
    </UModal>
  </div>
</template>
