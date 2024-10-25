<template>
  <SidebarItem :href="url('youtube')" screen="YouTube">
    <template #icon>
      <Icon :icon="faYoutube" fixed-width />
    </template>
    <slot />
  </SidebarItem>
</template>

<script lang="ts" setup>
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { useRouter } from '@/composables/useRouter'

import SidebarItem from './SidebarItem.vue'

const { url } = useRouter()
</script>
