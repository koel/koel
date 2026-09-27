<template>
  <div class="inline-flex max-w-full rounded-full bg-k-fg-10 shadow-sm">
    <div
      ref="container"
      class="scroll-mask-x-from-85% relative flex gap-1 overflow-x-auto p-1 [scrollbar-width:none]"
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
        class="absolute rounded-full bg-k-fg-20 shadow-sm transition-all duration-200 ease-out"
        data-testid="segmented-control-indicator"
      />
      <label
        v-for="option in options"
        :key="option.value"
        :data-testid="option.testId"
        class="relative shrink-0 whitespace-nowrap flex items-center gap-2 rounded-full px-4 has-[[data-badge]]:pr-[6px] py-1.5 cursor-pointer text-k-fg-70 hover:text-k-fg has-checked:text-k-fg has-focus-visible:outline-2 has-focus-visible:outline-k-highlight"
      >
        <input v-model="value" :name :value="option.value" class="sr-only" type="radio" />
        <slot :option>{{ option.label }}</slot>
      </label>
    </div>
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

const getSelectedLabel = () => container.value?.querySelector<HTMLInputElement>('input:checked')?.parentElement

const moveIndicatorToSelectedOption = () => {
  const selectedLabel = getSelectedLabel()

  indicator.value = selectedLabel?.offsetWidth
    ? {
        left: selectedLabel.offsetLeft,
        top: selectedLabel.offsetTop,
        width: selectedLabel.offsetWidth,
        height: selectedLabel.offsetHeight,
      }
    : null
}

const scrollSelectedOptionIntoView = () => {
  const selectedLabel = getSelectedLabel()

  if (!container.value || !selectedLabel) {
    return
  }

  const centeredLeft = selectedLabel.offsetLeft - (container.value.clientWidth - selectedLabel.offsetWidth) / 2

  container.value.scrollTo({ left: centeredLeft, behavior: 'smooth' })
}

onMounted(() => nextTick(moveIndicatorToSelectedOption))

watch(value, () =>
  nextTick(() => {
    moveIndicatorToSelectedOption()
    scrollSelectedOptionIntoView()
  }),
)

useResizeObserver(container, moveIndicatorToSelectedOption)
</script>
