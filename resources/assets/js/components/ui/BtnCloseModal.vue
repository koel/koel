<template>
  <button data-testid="close-modal-btn" title="Dismiss" type="button" @click.prevent="$emit('click')">
    <icon :icon="faTimes" />
  </button>
</template>

<script lang="ts" setup>
import { faTimes } from '@fortawesome/free-solid-svg-icons'

defineEmits<{ (e: 'click'): void }>()
</script>

<style lang="scss" scoped>
button {
  display: flex;
  place-content: center;
  place-items: center;
  position: absolute;
  right: 3px;
  top: 3px;
  border-radius: 3px;
  background: var(--color-red);
  width: 22px;
  height: 22px;
  padding: 0;
  color: var(--color-text-primary);
  transition: opacity .3s;

  @media (hover: none) {
    opacity: 1;
  }
}
</style>
