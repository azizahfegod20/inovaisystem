<script setup lang="ts">
import { sub } from 'date-fns'
import type { DropdownMenuItem } from '@nuxt/ui'
import type { Period, Range } from '~/types'

const { isNotificationsSlideoverOpen } = useDashboard()

const items = [[{
  label: 'Emitir NFS-e',
  icon: 'i-lucide-file-plus',
  to: '/invoices/new'
}, {
  label: 'Novo Tomador',
  icon: 'i-lucide-user-plus',
  to: '/tomadores'
}]] satisfies DropdownMenuItem[][]

const range = shallowRef<Range>({
  start: sub(new Date(), { days: 14 }),
  end: new Date()
})
const period = ref<Period>('daily')
</script>

<template>
  <UDashboardPanel id="dashboard">
    <template #header>
      <UDashboardNavbar title="Dashboard" :ui="{ right: 'gap-3' }">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UTooltip text="Notificações" :shortcuts="['N']">
            <UButton
              color="neutral"
              variant="ghost"
              square
              @click="isNotificationsSlideoverOpen = true"
            >
              <UChip color="error" inset>
                <UIcon name="i-lucide-bell" class="size-5 shrink-0" />
              </UChip>
            </UButton>
          </UTooltip>

          <UDropdownMenu :items="items">
            <UButton icon="i-lucide-plus" size="md" class="rounded-full" />
          </UDropdownMenu>
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <template #left>
          <NfseDateRangePicker v-model="range" class="-ms-1" />

          <NfsePeriodSelect v-model="period" :range="range" />
        </template>
      </UDashboardToolbar>
    </template>

    <template #body>
      <NfseStats :period="period" :range="range" />
      <NfseChart :period="period" :range="range" />
      <NfseRecentInvoices :period="period" :range="range" />
    </template>
  </UDashboardPanel>
</template>
