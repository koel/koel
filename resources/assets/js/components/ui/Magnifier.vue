<template>
  <span class="flex transition-opacity duration-200">
    <button class="!rounded-l !border-r-0" title="Zoom out" type="button" @click.prevent="$emit('out')">
      <Icon :icon="faSearchMinus" />
    </button>
    <button class="!rounded-r" title="Zoom in" type="button" @click.prevent="$emit('in')">
      <Icon :icon="faSearchPlus" />
    </button>
  </span>
</template>

<script lang="ts" setup>
import { faSearchMinus, faSearchPlus } from '@fortawesome/free-solid-svg-icons'

const emit = defineEmits<{ (e: 'in' | 'out'): void }>()
</script>

<style lang="postcss" scoped>
button {
  @apply text-k-text-primary bg-k-bg-primary border border-solid border-white/20 opacity-80
    px-3 py-1.5 hover:opacity-100 active:scale-95;
}
</style>
