<template>
  <button
    v-if="tag === 'button'"
    ref="button"
    :data-variant="variant"
    class="text-base text-white border border-transparent bg-k-primary px-3.5 py-2 rounded cursor-pointer"
    type="button"
  >
    <slot>Click me</slot>
  </button>
  <a
    v-else
    ref="button"
    :data-variant="variant"
    class="text-base text-white border border-transparent bg-k-primary px-3.5 py-2 rounded cursor-pointer"
  >
    <slot>Click me</slot>
  </a>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

type Variant = 'success' | 'destructive' | 'highlight' | 'ghost'

withDefaults(defineProps<{ tag?: 'button' | 'a'; variant?: Variant }>(), {
  tag: 'button',
})

const button = ref<HTMLButtonElement>()

defineExpose({
  button,
})
</script>

<style lang="postcss" scoped>
/**
 * Except for the `highlight` variant, button text colors are independent from the theme.
 */
button,
a {
  &:not([disabled]):hover {
    box-shadow: inset 0 0 0 10rem rgba(0, 0, 0, 0.1);
  }

  &:not([disabled]):active {
    box-shadow: inset 0 10px 10px -10px rgba(0, 0, 0, 0.6);
  }

  &[big] {
    @apply px-6 py-3;
  }

  &[small] {
    @apply text-[0.9rem] px-3 py-1;
  }

  &[data-variant='success'] {
    @apply bg-k-success text-white;
  }

  &[data-variant='ghost'] {
    @apply bg-transparent text-k-fg hover:text-k-fg-80 active:text-k-fg-70;
  }

  &[data-variant='destructive'] {
    @apply bg-k-danger text-white;
  }

  &[data-variant='highlight'] {
    @apply bg-k-highlight text-k-highlight-fg;
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

  &[bordered] {
    @apply border-k-fg-20 bg-transparent;

    &[data-variant='success'] {
      @apply border-k-success;
    }

    &[data-variant='destructive'] {
      @apply border-k-danger;
    }

    &[data-variant='highlight'] {
      @apply border-k-highlight;
    }
  }
}
</style>
