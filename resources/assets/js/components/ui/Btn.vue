<template>
  <button ref="button" type="button">
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

<style lang="scss" scoped>
button {
  background: var(--color-blue);
  color: var(--color-text-primary);
  font-size: 1rem;
  padding: .6rem 1rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .3rem;

  &:hover {
    box-shadow: inset 0 0 0 10rem rgba(0, 0, 0, .05);
  }

  &[small] {
    font-size: .9rem;
    padding: .4rem .7rem;
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

  &[uppercase] {
    text-transform: uppercase;
  }
}
</style>
