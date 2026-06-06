<template>
  <span
    :class="size"
    class="play-icon absolute flex opacity-0 no-hover:opacity-100 items-center justify-center aspect-square rounded-full top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-k-highlight group-hover:opacity-100 duration-500 transition z-20"
    aria-hidden="true"
  >
    <Icon v-if="playing" :icon="faPause" data-testid="icon-pause" class="text-k-highlight-fg" :size="iconSize" />
    <Icon v-else :icon="faPlay" data-testid="icon-play" class="ml-0.5 text-k-highlight-fg" :size="iconSize" />
  </span>
</template>

<script lang="ts" setup>
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { computed } from 'vue'

const props = withDefaults(defineProps<{ playing?: boolean; size?: 'sm' | 'lg' }>(), {
  playing: false,
  size: 'lg',
})

const iconSize = computed(() => (props.size === 'lg' ? 'lg' : undefined))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.play-icon.sm {
  @apply w-[24px];
}

.play-icon.lg {
  @apply w-[32px];
}
</style>
