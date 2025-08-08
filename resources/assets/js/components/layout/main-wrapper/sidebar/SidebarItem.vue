<template>
  <li
    :class="{ active }"
    class="relative before:right-0 px-6 before:top-1/4 before:w-[4px] before:h-1/2 before:absolute before:rounded-full
    before:transition-[box-shadow,_background-color] before:ease-in-out before:duration-500"
    data-testid="sidebar-item"
    @click="onClick"
  >
    <a
      :href="props.href"
      class="flex items-center overflow-x-hidden gap-3 h-11 relative active:pt-0.5 active:pr-0 active:pb-0 active:pl-0.5
      text-k-text-secondary hover:text-k-text-primary"
    >
      <span>
        <slot name="icon" />
      </span>

      <span class="overflow-hidden text-ellipsis whitespace-nowrap">
        <slot />
      </span>
    </a>
  </li>
</template>

<script lang="ts" setup>
import { eventBus } from '@/utils/eventBus'

const props = withDefaults(defineProps<{ href?: string | undefined, active?: boolean }>(), {
  active: false,
})

const onClick = () => eventBus.emit('TOGGLE_SIDEBAR')
</script>

<style lang="postcss" scoped>
li.active {
  a {
    @apply text-k-text-primary !important;
  }

  &::before {
    @apply bg-k-highlight !important;
    box-shadow: 0 0 40px 10px var(--color-highlight);
  }
}
</style>
