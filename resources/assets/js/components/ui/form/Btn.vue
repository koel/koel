<template>
  <button
    ref="button"
    class="text-base text-k-text-primary bg-k-primary px-4 py-2.5 rounded cursor-pointer"
    type="button"
  >
    <slot>Click me</slot>
  </button>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

const button = ref<HTMLButtonElement>()

defineExpose({
  button
})
</script>

<style lang="postcss" scoped>
button {
  &:not([disabled]):hover {
    box-shadow: inset 0 0 0 10rem rgba(0, 0, 0, .1);
  }

  &:not([disabled]):active {
    box-shadow: inset 0 10px 10px -10px rgba(0, 0, 0, .6);
  }

  &[big] {
    @apply px-6 py-3;
  }

  &[small] {
    @apply text-[0.9rem] px-3 py-1;
  }

  &[success] {
    @apply bg-k-success;
  }

  &[white] {
    @apply bg-transparent text-k-text-secondary;
  }

  &[danger] {
    @apply bg-k-danger;
  }

  &[grey], &[gray] {
    @apply bg-k-bg-secondary;
  }

  &[highlight] {
    @apply bg-k-highlight;
  }

  &[transparent] {
    @apply bg-transparent;
  }

  &[rounded] {
    @apply rounded-full;
  }

  &[unrounded] {
    @apply rounded-none;
  }

  &[uppercase] {
    @apply uppercase;
  }
}
</style>
