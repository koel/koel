<template>
  <span class="btn-group">
    <slot></slot>
  </span>
</template>

<style lang="scss">
.btn-group {
  display: flex;
  position: relative;
  flex-wrap: nowrap;

  button {
    &:not(:first-child) {
      border-radius: 0;
    }

    &:first-of-type {
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
      border-top-left-radius: 9999px;
      border-bottom-left-radius: 9999px;
    }

    &:last-of-type {
      border-top-right-radius: 9999px;
      border-bottom-right-radius: 9999px;
    }
  }

  &[uppercased] button {
    text-transform: uppercase;
  }
}
</style>
