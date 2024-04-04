<template>
  <span class="btn-group inline-block relative flex-nowrap">
    <slot />
  </span>
</template>

<style lang="postcss" scoped>
.btn-group {
  :deep(button) {
    &:not(:first-child) {
      @apply rounded-none;
    }

    &:first-of-type {
      @apply rounded-l-md rounded-r-none;
    }

    &:last-of-type {
      @apply rounded-r-md rounded-l-none;
    }

    &:only-of-type {
      @apply rounded;
    }
  }

  &[uppercased] :deep(button) {
    @apply uppercase;
  }
}
</style>
