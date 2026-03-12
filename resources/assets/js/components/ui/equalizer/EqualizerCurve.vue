<template>
  <svg class="absolute inset-0 pointer-events-none overflow-visible">
    <defs>
      <linearGradient id="eq-curve-gradient" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" y2="100">
        <stop offset="0%" stop-color="var(--color-highlight)" stop-opacity="0.8" />
        <stop offset="100%" stop-color="var(--color-success)" stop-opacity="0.8" />
      </linearGradient>
      <template v-if="curveExtent">
        <linearGradient
          id="eq-fade-left"
          gradientUnits="userSpaceOnUse"
          :x1="curveExtent.left"
          y1="0"
          :x2="curveExtent.firstBand"
          y2="0"
        >
          <stop offset="0%" stop-color="black" />
          <stop offset="100%" stop-color="white" />
        </linearGradient>
        <linearGradient
          id="eq-fade-right"
          gradientUnits="userSpaceOnUse"
          :x1="curveExtent.lastBand"
          y1="0"
          :x2="curveExtent.right"
          y2="0"
        >
          <stop offset="0%" stop-color="white" />
          <stop offset="100%" stop-color="black" />
        </linearGradient>
        <mask id="eq-curve-mask" maskUnits="userSpaceOnUse" x="-50" y="-500" width="2000" height="1000">
          <rect
            :x="curveExtent.left"
            y="-500"
            :width="curveExtent.firstBand - curveExtent.left"
            height="1000"
            fill="url(#eq-fade-left)"
          />
          <rect
            :x="curveExtent.firstBand"
            y="-500"
            :width="curveExtent.lastBand - curveExtent.firstBand"
            height="1000"
            fill="white"
          />
          <rect
            :x="curveExtent.lastBand"
            y="-500"
            :width="curveExtent.right - curveExtent.lastBand"
            height="1000"
            fill="url(#eq-fade-right)"
          />
        </mask>
      </template>
    </defs>
    <path
      :d="curvePath"
      fill="none"
      stroke="url(#eq-curve-gradient)"
      stroke-width="2"
      stroke-linecap="round"
      :mask="curveExtent ? 'url(#eq-curve-mask)' : undefined"
    />
  </svg>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const props = defineProps<{ points: { x: number; y: number }[] }>()

const EXTEND = 15

const curveExtent = computed(() => {
  if (props.points.length < 2) {
    return null
  }

  return {
    left: props.points[0].x - EXTEND,
    firstBand: props.points[0].x,
    lastBand: props.points[props.points.length - 1].x,
    right: props.points[props.points.length - 1].x + EXTEND,
  }
})

const curvePath = computed(() => {
  if (props.points.length < 2) {
    return ''
  }

  const first = props.points[0]
  const second = props.points[1]
  const secondLast = props.points[props.points.length - 2]
  const last = props.points[props.points.length - 1]

  // Extrapolate points beyond the first and last bands so the curve tapers off naturally
  const pts = [
    { x: first.x - EXTEND, y: first.y + ((first.y - second.y) / (second.x - first.x)) * EXTEND },
    ...props.points,
    { x: last.x + EXTEND, y: last.y + ((last.y - secondLast.y) / (last.x - secondLast.x)) * EXTEND },
  ]

  // Catmull-Rom to cubic bezier conversion
  const segments: string[] = [`M ${pts[0].x} ${pts[0].y}`]

  for (let i = 0; i < pts.length - 1; i++) {
    const p0 = pts[Math.max(i - 1, 0)]
    const p1 = pts[i]
    const p2 = pts[i + 1]
    const p3 = pts[Math.min(i + 2, pts.length - 1)]

    const cp1x = p1.x + (p2.x - p0.x) / 6
    const cp1y = p1.y + (p2.y - p0.y) / 6
    const cp2x = p2.x - (p3.x - p1.x) / 6
    const cp2y = p2.y - (p3.y - p1.y) / 6

    segments.push(`C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x} ${p2.y}`)
  }

  return segments.join(' ')
})
</script>
