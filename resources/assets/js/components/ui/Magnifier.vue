<template>
  <span>
    <button title="Zoom out" type="button" @click.prevent="$emit('out')">
      <icon :icon="faSearchMinus" />
    </button>
    <button title="Zoom in" type="button" @click.prevent="$emit('in')">
      <icon :icon="faSearchPlus" />
    </button>
  </span>
</template>

<script lang="ts" setup>
import { faSearchMinus, faSearchPlus } from '@fortawesome/free-solid-svg-icons'

const emit = defineEmits<{ (e: 'in' | 'out'): void }>()
</script>

<style lang="scss" scoped>
span {
  display: flex;
  transition: .2s;

  button {
    @include inset-when-pressed();

    background: var(--color-bg-primary);
    border: 1px solid rgba(255, 255, 255, .2);
    opacity: .8;
    color: var(--color-text-primary);
    transition: background .2s;
    padding: .5rem .75rem;

    &:hover {
      opacity: 1;
      background: var(--color-bg-primary);
      color: var(--color-text-primary);
    }

    &:first-child {
      border-radius: 4px 0 0 4px;
      border-right: 0;
    }

    &:last-child {
      border-radius: 0 4px 4px 0;
    }
  }
}
</style>
