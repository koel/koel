<template>
  <button :data-variant="variant" class="text-k-fg hover:text-k-highlight [font:inherit]" type="button">
    <slot />
  </button>
</template>

<script lang="ts" setup>
defineProps<{ variant?: 'highlight' }>()
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

button[data-variant='highlight'] {
  @apply text-k-highlight hover:text-k-fg;
}
</style>
