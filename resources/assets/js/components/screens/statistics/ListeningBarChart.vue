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
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import type { ListeningBar, ListeningMeasure } from '@/utils/listeningStatistics'
import { pluralize } from '@/utils/formatters'

Chart.register(BarElement, CategoryScale, LinearScale, Tooltip)

const props = defineProps<{ title: string; bars: ListeningBar[]; measure: ListeningMeasure }>()

const readCssVariable = (name: string) => getComputedStyle(document.documentElement).getPropertyValue(name).trim()

const withOpacity = (hexColor: string, opacity: number) => {
  const match = /^#([\da-f]{2})([\da-f]{2})([\da-f]{2})$/i.exec(hexColor)

  if (!match) {
    return hexColor
  }

  const [red, green, blue] = match.slice(1).map(channel => Number.parseInt(channel, 16))

  return `rgba(${red}, ${green}, ${blue}, ${opacity})`
}

const highlightColor = readCssVariable('--color-highlight')
const mutedTextColor = withOpacity(readCssVariable('--color-fg'), 0.5)
const gridColor = withOpacity(readCssVariable('--color-fg'), 0.08)

const chartData = computed<ChartData<'bar'>>(() => ({
  labels: props.bars.map(bar => bar.label),
  datasets: [
    {
      data: props.bars.map(bar => Math.round(bar.value)),
      backgroundColor: withOpacity(highlightColor, 0.75),
      hoverBackgroundColor: highlightColor,
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
      ticks: { color: mutedTextColor, autoSkip: true, maxRotation: 0 },
    },
    y: {
      beginAtZero: true,
      grid: { color: gridColor },
      border: { display: false },
      ticks: { color: mutedTextColor, precision: 0, maxTicksLimit: 4 },
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
