<template>
  <a :href="storeUrl" target="_blank">Upgrade to Plus</a>
</template>

<script setup lang="ts">
import { useKoelPlus } from '@/composables'

const { storeUrl } = useKoelPlus()
</script>

<style scoped lang="scss">
a {
  @include inset-when-pressed();

  background: transparent;
  border-width: 1px;
  border-style: solid;
  padding: .5rem 1rem;
  border-radius: 5px;
  border-color: var(--color-text-primary);

  &:hover {
    transition: .3s ease-in-out;
    background: var(--color-highlight);
    border-color: var(--color-highlight);
    color: var(--color-text-primary) !important;
  }

  &:active {
    transition: none;
  }
}
</style>
