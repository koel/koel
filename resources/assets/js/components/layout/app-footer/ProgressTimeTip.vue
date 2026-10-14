<template>
  <span :class="align" :style="position" class="tooltip show time-tip">
    {{ secondsToHis(seconds) }}
    <span class="tooltip-arrow" />
  </span>
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
  @apply fixed -mt-3 -translate-y-full border border-white/15 drop-shadow-lg;

  .tooltip-arrow {
    @apply -bottom-[5px] border-r border-b border-white/15;
  }

  &.center {
    @apply -translate-x-1/2;

    .tooltip-arrow {
      @apply left-[calc(50%-4px)];
    }
  }

  &.end {
    @apply -translate-x-full;

    .tooltip-arrow {
      @apply right-0.5;
    }
  }
}
</style>
