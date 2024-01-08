<template>
  <a :href="storeUrl" target="_blank" class="upgrade-to-plus-btn">
    <Icon :icon="faPlus" fixed-width/>
    Upgrade to Plus
  </a>
</template>

<script setup lang="ts">
import { useKoelPlus } from '@/composables'
import { faPlus } from '@fortawesome/free-solid-svg-icons'

const { storeUrl } = useKoelPlus()
</script>

<style scoped lang="scss">
a.upgrade-to-plus-btn {
  @include inset-when-pressed();

  background: linear-gradient(97.78deg, #671ce4 17.5%, #c62be8 113.39%);
  border-radius: 5px;
  border-style: solid;
  padding: .65rem 1rem;

  &:hover {
    color: var(--color-text-primary) !important;
  }

  &:active {
    padding: .65rem 1rem; // prevent layout jump in sidebar
  }
}
</style>
