<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const route = useRoute()
const toast = useToast()

const open = ref(false)

const links = [[{
  label: 'Dashboard',
  icon: 'i-lucide-layout-dashboard',
  to: '/',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Emitir NFS-e',
  icon: 'i-lucide-file-plus',
  to: '/invoices/new',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Notas Fiscais',
  icon: 'i-lucide-file-text',
  to: '/invoices',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Tomadores',
  icon: 'i-lucide-users',
  to: '/tomadores',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Serviços',
  icon: 'i-lucide-wrench',
  to: '/services',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Configurações',
  to: '/settings',
  icon: 'i-lucide-settings',
  defaultOpen: false,
  type: 'trigger',
  children: [{
    label: 'Empresa',
    to: '/settings',
    exact: true,
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Certificado Digital',
    to: '/settings/certificate',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Membros',
    to: '/settings/members',
    onSelect: () => {
      open.value = false
    }
  }]
}], [{
  label: 'Documentação',
  icon: 'i-lucide-book-open',
  to: 'https://www.gov.br/nfse',
  target: '_blank'
}, {
  label: 'Suporte',
  icon: 'i-lucide-info',
  to: 'mailto:suporte@inovai.com.br'
}]] satisfies NavigationMenuItem[][]

const groups = computed(() => [{
  id: 'links',
  label: 'Go to',
  items: links.flat()
}, {
  id: 'code',
  label: 'Code',
  items: [{
    id: 'source',
    label: 'View page source',
    icon: 'i-simple-icons-github',
    to: `https://github.com/nuxt-ui-templates/dashboard/blob/main/app/pages${route.path === '/' ? '/index' : route.path}.vue`,
    target: '_blank'
  }]
}])

onMounted(async () => {
  const cookie = useCookie('cookie-consent')
  if (cookie.value === 'accepted') {
    return
  }

  toast.add({
    title: 'We use first-party cookies to enhance your experience on our website.',
    duration: 0,
    close: false,
    actions: [{
      label: 'Accept',
      color: 'neutral',
      variant: 'outline',
      onClick: () => {
        cookie.value = 'accepted'
      }
    }, {
      label: 'Opt out',
      color: 'neutral',
      variant: 'ghost'
    }]
  })
})
</script>

<template>
  <UDashboardGroup unit="rem">
    <UDashboardSidebar
      id="default"
      v-model:open="open"
      collapsible
      resizable
      class="bg-elevated/25"
      :ui="{ footer: 'lg:border-t lg:border-default' }"
    >
      <template #header="{ collapsed }">
        <TeamsMenu :collapsed="collapsed" />
      </template>

      <template #default="{ collapsed }">
        <UDashboardSearchButton :collapsed="collapsed" class="bg-transparent ring-default" />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="links[0]"
          orientation="vertical"
          tooltip
          popover
        />

        <SettingsCertificateAlert v-if="!collapsed" />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="links[1]"
          orientation="vertical"
          tooltip
          class="mt-auto"
        />
      </template>

      <template #footer="{ collapsed }">
        <UserMenu :collapsed="collapsed" />
      </template>
    </UDashboardSidebar>

    <UDashboardSearch :groups="groups" />

    <slot />

    <NotificationsSlideover />
  </UDashboardGroup>
</template>
