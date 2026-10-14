<template>
  <span :class="align" :style="position" class="time-tip">{{ secondsToHis(seconds) }}</span>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { secondsToHis } from '@/utils/formatters'

const props = withDefaults(
  defineProps<{
    trackRect: DOMRect
    percent: number
    seconds: number
    align?: 'center' | 'end'
  }>(),
  { align: 'center' },
)

const position = computed(() => ({
  left: `${props.trackRect.left + (props.trackRect.width * props.percent) / 100}px`,
  top: `${props.trackRect.top}px`,
}))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

.time-tip {
  @apply fixed z-9999 -mt-3 w-max px-3 py-2 rounded-md border border-white/15 bg-black text-white/80 tabular-nums
  drop-shadow-lg pointer-events-none -translate-y-full no-hover:hidden;

  &::after {
    @apply absolute -bottom-[5px] size-2 rotate-45 border-r border-b border-white/15 bg-black content-[''];
  }

  &.center {
    @apply -translate-x-1/2;

    &::after {
      @apply left-[calc(50%-4px)];
    }
  }

  &.end {
    @apply -translate-x-full;

    &::after {
      @apply right-0.5;
    }
  }
}
</style>
