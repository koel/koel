<template>
  <SidebarItem :href screen="MediaBrowser">
    <template #icon>
      <Icon :icon="faFolderTree" fixed-width />
    </template>
    Media Browser
  </SidebarItem>
</template>

<script setup lang="ts">
import { faFolderTree } from '@fortawesome/free-solid-svg-icons'

import { ref } from 'vue'
import { useRouter } from '@/composables/useRouter'

import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'

const { onRouteChanged, url } = useRouter()

const href = ref(url('media-browser'))

// Update the href when the route changes, so that clicking on the item will go to the previously visited folder.
onRouteChanged(handler => {
  if (handler.screen !== 'MediaBrowser') {
    return
  }

  href.value = url('media-browser', handler.params)
})
</script>

<style scoped lang="postcss">

</style>
