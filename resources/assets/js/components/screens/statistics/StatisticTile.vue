<template>
  <WithGradientBorder
    :color="toneColors[tone]"
    border-color="color-mix(in srgb, var(--color-fg), transparent 97%)"
    border-width="1px"
    class="flex flex-col gap-1 p-4 rounded-md bg-k-fg-5"
  >
    <dt class="text-sm text-k-fg-70 truncate">{{ label }}</dt>
    <dd class="text-2xl text-k-fg tabular-nums">{{ value }}</dd>
    <slot />
  </WithGradientBorder>
</template>

<script lang="ts" setup>
import WithGradientBorder from '@/components/ui/WithGradientBorder.vue'

export type StatisticTileTone = 'highlight' | 'primary' | 'success' | 'love' | 'warning' | 'danger'

withDefaults(defineProps<{ label: string; value: string; tone?: StatisticTileTone }>(), {
  tone: 'highlight',
})

const toneColors: Record<StatisticTileTone, string> = {
  highlight: 'var(--color-highlight)',
  primary: 'var(--color-primary)',
  success: 'var(--color-success)',
  love: 'var(--color-love)',
  warning: 'var(--color-warning)',
  danger: 'var(--color-danger)',
}
</script>
