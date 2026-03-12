<template>
  <svg class="absolute inset-0 pointer-events-none" :viewBox="`0 0 ${width} ${height}`" preserveAspectRatio="none">
    <defs>
      <linearGradient id="eq-curve-gradient" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="var(--color-highlight)" stop-opacity="0.8" />
        <stop offset="100%" stop-color="var(--color-success)" stop-opacity="0.8" />
      </linearGradient>
    </defs>
    <path :d="curvePath" fill="none" stroke="url(#eq-curve-gradient)" stroke-width="2" stroke-linecap="round" />
  </svg>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const props = defineProps<{
  gains: number[]
  width: number
  height: number
}>()

const dbToY = (db: number) => ((20 - db) / 40) * props.height

const curvePath = computed(() => {
  if (!props.gains.length || !props.width || !props.height) {
    return ''
  }

  const count = props.gains.length
  const padding = props.width / (count * 2)
  const spacing = (props.width - padding * 2) / (count - 1)
  const points = props.gains.map((db, i) => ({ x: padding + i * spacing, y: dbToY(db) }))

  if (points.length < 2) {
    return ''
  }

  // Catmull-Rom to cubic bezier conversion
  const segments: string[] = [`M ${points[0].x} ${points[0].y}`]

  for (let i = 0; i < points.length - 1; i++) {
    const p0 = points[Math.max(i - 1, 0)]
    const p1 = points[i]
    const p2 = points[i + 1]
    const p3 = points[Math.min(i + 2, points.length - 1)]

    const cp1x = p1.x + (p2.x - p0.x) / 6
    const cp1y = p1.y + (p2.y - p0.y) / 6
    const cp2x = p2.x - (p3.x - p1.x) / 6
    const cp2y = p2.y - (p3.y - p1.y) / 6

    segments.push(`C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x} ${p2.y}`)
  }

  return segments.join(' ')
})
</script>
