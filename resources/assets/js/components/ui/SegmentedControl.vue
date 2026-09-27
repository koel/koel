<template>
  <div
    ref="container"
    class="relative inline-flex flex-wrap gap-1 rounded-full bg-k-fg-5 p-1 shadow-sm"
    role="radiogroup"
  >
    <span
      v-if="indicator"
      :style="{
        left: `${indicator.left}px`,
        top: `${indicator.top}px`,
        width: `${indicator.width}px`,
        height: `${indicator.height}px`,
      }"
      class="absolute rounded-full bg-k-fg-10 shadow-sm transition-all duration-200 ease-out"
      data-testid="segmented-control-indicator"
    />
    <label
      v-for="option in options"
      :key="option.value"
      :data-testid="option.testId"
      class="relative flex items-center gap-2 rounded-full px-4 has-[[data-badge]]:pr-[6px] py-1.5 cursor-pointer text-k-fg-70 hover:text-k-fg has-checked:text-k-fg has-focus-visible:outline-2 has-focus-visible:outline-k-highlight"
    >
      <input v-model="value" :name :value="option.value" class="sr-only" type="radio" />
      <slot :option>{{ option.label }}</slot>
    </label>
  </div>
</template>

<script lang="ts" setup generic="T extends string">
import { useResizeObserver } from '@vueuse/core'
import { nextTick, onMounted, ref, watch } from 'vue'

defineProps<{
  name: string
  options: { value: T; label: string; testId?: string }[]
}>()

const value = defineModel<T>({ required: true })

const container = ref<HTMLElement>()
const indicator = ref<{ left: number; top: number; width: number; height: number } | null>(null)

const moveIndicatorToSelectedOption = () => {
  const selectedLabel = container.value?.querySelector<HTMLInputElement>('input:checked')?.parentElement

  indicator.value = selectedLabel?.offsetWidth
    ? {
        left: selectedLabel.offsetLeft,
        top: selectedLabel.offsetTop,
        width: selectedLabel.offsetWidth,
        height: selectedLabel.offsetHeight,
      }
    : null
}

onMounted(() => nextTick(moveIndicatorToSelectedOption))
watch(value, () => nextTick(moveIndicatorToSelectedOption))
useResizeObserver(container, moveIndicatorToSelectedOption)
</script>
