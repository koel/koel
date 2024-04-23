<template>
  <SidebarItem screen="YouTube" href="#/youtube">
    <template #icon>
      <Icon :icon="faYoutube" fixed-width />
    </template>
    <slot />
  </SidebarItem>
</template>

<script lang="ts" setup>
import { faYoutube } from '@fortawesome/free-brands-svg-icons'

import SidebarItem from './SidebarItem.vue'
</script>
