<template>
  <span class="btn-group inline-flex relative flex-nowrap">
    <slot />
  </span>
</template>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.btn-group {
  :deep(button) {
    &:not(:first-child) {
      @apply rounded-none;
    }

    &:not(:first-of-type) {
      @apply border-l-k-fg-10;
    }

    &:first-of-type {
      @apply rounded-l-md rounded-r-none;
    }

    &:last-of-type {
      @apply rounded-r-md rounded-l-none;
    }

    &:only-of-type {
      @apply rounded-sm;
    }
  }

  &[uppercase] :deep(button) {
    @apply uppercase;
  }
}
</style>
