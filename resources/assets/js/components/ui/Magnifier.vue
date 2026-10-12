<template>
  <span class="flex transition-opacity duration-200">
    <button v-koel-tooltip class="rounded-l! border-r-0!" type="button" @click.prevent="$emit('out')">
      <Icon :icon="faSearchMinus" />
      <span class="sr-only">Zoom out</span>
    </button>
    <button v-koel-tooltip class="rounded-r!" type="button" @click.prevent="$emit('in')">
      <Icon :icon="faSearchPlus" />
      <span class="sr-only">Zoom in</span>
    </button>
  </span>
</template>

<script lang="ts" setup>
import { faSearchMinus, faSearchPlus } from '@fortawesome/free-solid-svg-icons'

defineEmits<{ (e: 'in' | 'out'): void }>()
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
button {
  @apply text-k-fg bg-k-bg border border-solid border-white/20 opacity-80
  px-3 py-1.5 hover:opacity-100 active:scale-95;
}
</style>
