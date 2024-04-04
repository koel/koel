<template>
  <a href class="upgrade-to-plus-btn inset-when-pressed" @click.prevent="openModal">
    <Icon :icon="faPlus" fixed-width />
    Upgrade to Plus
  </a>
</template>

<script setup lang="ts">
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils'

const openModal = () => eventBus.emit('MODAL_SHOW_KOEL_PLUS')
</script>

<style scoped lang="postcss">
a.upgrade-to-plus-btn {
  background: linear-gradient(97.78deg, #671ce4 17.5%, #c62be8 113.39%);
  border-radius: 5px;
  border-style: solid;
  padding: .65rem 1rem;

  &:hover {
    color: var(--color-text-primary) !important;
  }

  &:active {
    padding: .65rem 1rem; /* prevent layout jump in sidebar */
  }
}
</style>
