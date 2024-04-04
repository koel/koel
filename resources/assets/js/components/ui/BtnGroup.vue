<template>
  <span class="btn-group">
    <slot />
  </span>
</template>

<style lang="postcss" scoped>
.btn-group {
  --radius: 5px;

  display: inline-flex;
  position: relative;
  flex-wrap: nowrap;

  :deep(button) {
    &:not(:first-child) {
      border-radius: 0;
    }

    &:first-of-type {
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
      border-top-left-radius: var(--radius);
      border-bottom-left-radius: var(--radius);
    }

    &:last-of-type {
      border-top-right-radius: var(--radius);
      border-bottom-right-radius: var(--radius);
    }
  }

  &[uppercased] :deep(button) {
    text-transform: uppercase;
  }
}
</style>
