<template>
  <figure class="flex flex-col gap-3">
    <figcaption class="text-k-fg">{{ title }}</figcaption>
    <div class="relative h-40">
      <canvas ref="canvas" :aria-label="title" role="img" />
    </div>
  </figure>
</template>

<script lang="ts" setup>
import { BarController, BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import type { PlaysBar } from '@/utils/listeningStatistics'
import { pluralize } from '@/utils/formatters'

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip)

const props = defineProps<{ title: string; bars: PlaysBar[] }>()

const canvas = ref<HTMLCanvasElement>()
let chart: Chart<'bar'> | null = null

const readCssVariable = (name: string) => getComputedStyle(document.documentElement).getPropertyValue(name).trim()

const withOpacity = (hexColor: string, opacity: number) => {
  const match = /^#([\da-f]{2})([\da-f]{2})([\da-f]{2})$/i.exec(hexColor)

  if (!match) {
    return hexColor
  }

  const [red, green, blue] = match.slice(1).map(channel => Number.parseInt(channel, 16))

  return `rgba(${red}, ${green}, ${blue}, ${opacity})`
}

const renderChart = () => {
  if (!canvas.value) {
    return
  }

  const highlightColor = readCssVariable('--color-highlight')
  const foregroundColor = readCssVariable('--color-fg')

  chart = new Chart(canvas.value, {
    type: 'bar',
    data: {
      labels: props.bars.map(bar => bar.label),
      datasets: [
        {
          data: props.bars.map(bar => bar.plays),
          backgroundColor: withOpacity(highlightColor, 0.75),
          hoverBackgroundColor: highlightColor,
          borderRadius: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: false,
      font: { family: readCssVariable('--font-family') },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: withOpacity(foregroundColor, 0.5), autoSkip: true, maxRotation: 0 },
        },
        y: {
          beginAtZero: true,
          grid: { color: withOpacity(foregroundColor, 0.08) },
          border: { display: false },
          ticks: { color: withOpacity(foregroundColor, 0.5), precision: 0, maxTicksLimit: 4 },
        },
      },
      plugins: {
        tooltip: {
          displayColors: false,
          callbacks: {
            label: context => pluralize(Number(context.raw), 'play'),
          },
        },
      },
    },
  })
}

const updateChart = () => {
  if (!chart) {
    return
  }

  chart.data.labels = props.bars.map(bar => bar.label)
  chart.data.datasets[0].data = props.bars.map(bar => bar.plays)
  chart.update()
}

watch(() => props.bars, updateChart)

onMounted(renderChart)

onBeforeUnmount(() => chart?.destroy())
</script>
