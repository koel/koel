<template>
  <SidebarItem screen="YouTube" href="#/youtube">
    <template #icon>
      <Icon :icon="faYoutube" fixed-width />
    </template>
    {{ title }}
  </SidebarItem>
</template>

<script lang="ts" setup>
import { unescape } from 'lodash'
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { ref } from 'vue'
import { eventBus } from '@/utils'

import SidebarItem from './SidebarItem.vue'

const title = ref('')

eventBus.on('PLAY_YOUTUBE_VIDEO', payload => (title.value = unescape(payload.title)))
</script>
