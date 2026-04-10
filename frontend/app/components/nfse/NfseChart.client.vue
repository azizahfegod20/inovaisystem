<script setup lang="ts">
import { eachDayOfInterval, eachWeekOfInterval, eachMonthOfInterval, format } from 'date-fns'
import { VisXYContainer, VisLine, VisAxis, VisArea, VisCrosshair, VisTooltip } from '@unovis/vue'
import type { Period, Range, DashboardChart } from '~/types'

const cardRef = useTemplateRef<HTMLElement | null>('cardRef')

const props = defineProps<{
  period: Period
  range: Range
}>()

type DataRecord = {
  date: string
  receita: number
  notas: number
}

const { width } = useElementSize(cardRef)

const data = ref<DataRecord[]>([])

watch([() => props.period, () => props.range], async () => {
  try {
    const chart = await $fetch<DashboardChart>('/api/dashboard/chart', {
      params: {
        date_from: props.range.start.toISOString().slice(0, 10),
        date_to: props.range.end.toISOString().slice(0, 10),
        period: props.period
      },
      credentials: 'include'
    })
    data.value = chart.labels.map((label, i) => ({
      date: label,
      receita: chart.datasets.receita[i] ?? 0,
      notas: chart.datasets.notas[i] ?? 0
    }))
  } catch {
    const dates = ({
      daily: eachDayOfInterval,
      weekly: eachWeekOfInterval,
      monthly: eachMonthOfInterval
    } as Record<Period, typeof eachDayOfInterval>)[props.period](props.range)

    data.value = dates.map(date => ({
      date: format(date, 'yyyy-MM-dd'),
      receita: 0,
      notas: 0
    }))
  }
}, { immediate: true })

const x = (_: DataRecord, i: number) => i
const y = (d: DataRecord) => d.receita

const total = computed(() => data.value.reduce((acc: number, { receita }) => acc + receita, 0))

const formatNumber = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format

const xTicks = (i: number) => {
  if (i === 0 || i === data.value.length - 1 || !data.value[i]) {
    return ''
  }
  return data.value[i].date
}

const template = (d: DataRecord) => `${d.date}: ${formatNumber(d.receita)} (${d.notas} notas)`
</script>

<template>
  <UCard ref="cardRef" :ui="{ root: 'overflow-visible', body: 'px-0! pt-0! pb-3!' }">
    <template #header>
      <div>
        <p class="text-xs text-muted uppercase mb-1.5">
          Receita
        </p>
        <p class="text-3xl text-highlighted font-semibold">
          {{ formatNumber(total) }}
        </p>
      </div>
    </template>

    <VisXYContainer
      :data="data"
      :padding="{ top: 40 }"
      class="h-96"
      :width="width"
    >
      <VisLine
        :x="x"
        :y="y"
        color="var(--ui-primary)"
      />
      <VisArea
        :x="x"
        :y="y"
        color="var(--ui-primary)"
        :opacity="0.1"
      />

      <VisAxis
        type="x"
        :x="x"
        :tick-format="xTicks"
      />

      <VisCrosshair
        color="var(--ui-primary)"
        :template="template"
      />

      <VisTooltip />
    </VisXYContainer>
  </UCard>
</template>

<style scoped>
.unovis-xy-container {
  --vis-crosshair-line-stroke-color: var(--ui-primary);
  --vis-crosshair-circle-stroke-color: var(--ui-bg);

  --vis-axis-grid-color: var(--ui-border);
  --vis-axis-tick-color: var(--ui-border);
  --vis-axis-tick-label-color: var(--ui-text-dimmed);

  --vis-tooltip-background-color: var(--ui-bg);
  --vis-tooltip-border-color: var(--ui-border);
  --vis-tooltip-text-color: var(--ui-text-highlighted);
}
</style>
