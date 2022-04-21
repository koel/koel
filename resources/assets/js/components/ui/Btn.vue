<template>
  <button @click="$emit('click', $event)" type="button">
    <slot>Click me</slot>
  </button>
</template>

<script lang="ts" setup>
defineEmits(['click']) // prevent duplicate events: https://github.com/vuejs/core/issues/813
</script>

<style lang="scss" scoped>
button {
  background: var(--color-blue);
  color: var(--color-text-primary);
  font-size: 1rem;
  padding: .6rem 1rem;
  cursor: pointer;

  &:hover {
    box-shadow: inset 0 0 0 10rem rgba(0, 0, 0, .05);
  }

  &[small] {
    font-size: .9rem;
    padding: .3rem .7rem;
  }

  @include inset-when-pressed();

  &[green] {
    background-color: var(--color-green);
  }

  &[white] {
    background-color: transparent;
    color: var(--color-text-secondary);
  }

  &[red] {
    background-color: var(--color-red);
  }

  &[grey], &[gray] {
    background-color: var(--color-bg-secondary);
  }

  &[orange] {
    background-color: var(--color-highlight);
  }

  &[transparent] {
    background-color: transparent;
  }

  &[rounded] {
    border-radius: 999px;
  }

  &[unrounded] {
    border-radius: 0;
  }

  &[icon-only] {
    i {
      margin-right: 0;
    }
  }

  &[uppercase] {
    text-transform: uppercase;
  }

  i {
    margin-right: 4px;
  }
}
</style>
