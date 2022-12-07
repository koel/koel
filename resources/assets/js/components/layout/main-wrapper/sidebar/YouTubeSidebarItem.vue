<template>
  <SidebarItem screen="YouTube" href="#/youtube" :icon="faYoutube">{{ title }}</SidebarItem>
</template>

<script lang="ts" setup>
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { ref } from 'vue'
import { eventBus } from '@/utils'

import SidebarItem from './SidebarItem.vue'

const title = ref('')

eventBus.on('PLAY_YOUTUBE_VIDEO', payload => (title.value = payload.title))
</script>
