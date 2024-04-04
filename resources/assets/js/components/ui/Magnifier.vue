<template>
  <span>
    <button class="inset-when-pressed" title="Zoom out" type="button" @click.prevent="$emit('out')">
      <Icon :icon="faSearchMinus" />
    </button>
    <button class="inset-when-pressed" title="Zoom in" type="button" @click.prevent="$emit('in')">
      <Icon :icon="faSearchPlus" />
    </button>
  </span>
</template>

<script lang="ts" setup>
import { faSearchMinus, faSearchPlus } from '@fortawesome/free-solid-svg-icons'

const emit = defineEmits<{ (e: 'in' | 'out'): void }>()
</script>

<style lang="postcss" scoped>
span {
  display: flex;
  transition: .2s;

  button {
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
