<template>
  <figure class="flex flex-col gap-3">
    <figcaption class="text-k-fg">{{ title }}</figcaption>
    <div class="relative h-40">
      <Bar :aria-label="title" :data="chartData" :options="chartOptions" role="img" />
    </div>
  </figure>
</template>

<script lang="ts" setup>
import type { ChartData, ChartOptions } from 'chart.js'
import { BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js'
import StyleObserver from 'style-observer'
import { computed, onBeforeUnmount, ref } from 'vue'
import { Bar } from 'vue-chartjs'
import type { ListeningBar, ListeningMeasure } from '@/utils/listeningStatistics'
import { pluralize } from '@/utils/formatters'
import { cssColorToRgb } from '@/utils/color'

Chart.register(BarElement, CategoryScale, LinearScale, Tooltip)

const props = defineProps<{ title: string; bars: ListeningBar[]; measure: ListeningMeasure }>()

const readCssVariable = (name: string) => getComputedStyle(document.body).getPropertyValue(name).trim()

const withOpacity = (color: string, opacity: number) => {
  const rgb = cssColorToRgb(color)

  return rgb ? `rgb(${rgb.join(' ')} / ${opacity})` : color
}

const highlightColor = ref(readCssVariable('--color-highlight'))
const foregroundColor = ref(readCssVariable('--color-fg'))
const mutedTextColor = computed(() => withOpacity(foregroundColor.value, 0.5))
const gridColor = computed(() => withOpacity(foregroundColor.value, 0.08))

const themeColorObserver = new StyleObserver(records =>
  records.forEach(({ property, value }) => {
    if (property === '--color-highlight') {
      highlightColor.value = value.trim()
    } else {
      foregroundColor.value = value.trim()
    }
  }),
)

themeColorObserver.observe(document.body, ['--color-highlight', '--color-fg'])

onBeforeUnmount(() => themeColorObserver.unobserve(document.body))

const chartData = computed<ChartData<'bar'>>(() => ({
  labels: props.bars.map(bar => bar.label),
  datasets: [
    {
      data: props.bars.map(bar => Math.round(bar.value)),
      backgroundColor: withOpacity(highlightColor.value, 0.75),
      hoverBackgroundColor: highlightColor.value,
      borderRadius: 2,
    },
  ],
}))

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  animation: false,
  font: { family: readCssVariable('--font-family') },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: mutedTextColor.value, autoSkip: true, maxRotation: 0 },
    },
    y: {
      beginAtZero: true,
      grid: { color: gridColor.value },
      border: { display: false },
      ticks: { color: mutedTextColor.value, precision: 0, maxTicksLimit: 4 },
    },
  },
  plugins: {
    tooltip: {
      displayColors: false,
      callbacks: {
        label: context => pluralize(Number(context.raw), props.measure === 'plays' ? 'play' : 'minute'),
      },
    },
  },
}))
</script>
