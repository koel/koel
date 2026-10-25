<template>
  <span class="flex transition-opacity duration-200">
    <button v-koel-tooltip type="button" @click.prevent="$emit('out')">
      <Icon :icon="faSearchMinus" fixed-width />
      <span class="sr-only">Zoom out</span>
    </button>
    <button v-koel-tooltip type="button" @click.prevent="$emit('in')">
      <Icon :icon="faSearchPlus" fixed-width />
      <span class="sr-only">Zoom in</span>
    </button>
    <slot />
  </span>
</template>

<script lang="ts" setup>
import { faSearchMinus, faSearchPlus } from '@fortawesome/free-solid-svg-icons'

defineEmits<{ (e: 'in' | 'out'): void }>()
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
button,
:slotted(button) {
  @apply text-k-fg bg-k-bg border border-r-0 border-solid border-white/20 opacity-80
  px-3 py-1.5 hover:opacity-100 active:scale-95;
}

button:first-child {
  @apply rounded-l;
}

button:last-child,
:slotted(button:last-child) {
  @apply rounded-r border-r;
}
</style>
