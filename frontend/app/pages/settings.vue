<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const links = [[{
  label: 'Empresa',
  icon: 'i-lucide-building-2',
  to: '/settings',
  exact: true
}, {
  label: 'Certificado Digital',
  icon: 'i-lucide-shield-check',
  to: '/settings/certificate'
}, {
  label: 'Membros',
  icon: 'i-lucide-users',
  to: '/settings/members'
}]] satisfies NavigationMenuItem[][]
</script>

<template>
  <UDashboardPanel id="settings" :ui="{ body: 'lg:py-12' }">
    <template #header>
      <UDashboardNavbar title="Configurações">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <!-- NOTE: The `-mx-1` class is used to align with the `DashboardSidebarCollapse` button here. -->
        <UNavigationMenu :items="links" highlight class="-mx-1 flex-1" />
      </UDashboardToolbar>
    </template>

    <template #body>
      <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-2xl mx-auto">
        <NuxtPage />
      </div>
    </template>
  </UDashboardPanel>
</template>
