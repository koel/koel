<template>
  <SidebarSection>
    <template #header>
      <SidebarSectionHeader>Your Music</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem screen="Home" href="#/home">
        <template #icon>
          <Icon :icon="faHome" fixed-width />
        </template>
        Home
      </SidebarItem>
      <QueueSidebarItem />
      <SidebarItem screen="Songs" href="#/songs">
        <template #icon>
          <Icon :icon="faMusic" fixed-width />
        </template>
        All Songs
      </SidebarItem>
      <SidebarItem screen="Albums" href="#/albums">
        <template #icon>
          <Icon :icon="faCompactDisc" fixed-width />
        </template>
        Albums
      </SidebarItem>
      <SidebarItem screen="Artists" href="#/artists">
        <template #icon>
          <Icon :icon="faMicrophone" fixed-width />
        </template>
        Artists
      </SidebarItem>
      <SidebarItem screen="Genres" href="#/genres">
        <template #icon>
          <Icon :icon="faTags" fixed-width />
        </template>
        Genres
      </SidebarItem>
      <YouTubeSidebarItem v-show="showYouTube" />
    </ul>
  </SidebarSection>
</template>

<script setup lang="ts">
import { faCompactDisc, faHome, faMicrophone, faMusic, faTags } from '@fortawesome/free-solid-svg-icons'
import { computed, ref } from 'vue'
import { eventBus } from '@/utils'
import { useThirdPartyServices } from '@/composables'

import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'
import QueueSidebarItem from '@/components/layout/main-wrapper/sidebar/QueueSidebarItem.vue'
import YouTubeSidebarItem from '@/components/layout/main-wrapper/sidebar/YouTubeSidebarItem.vue'

const { useYouTube } = useThirdPartyServices()

const youTubePlaying = ref(false)
const showYouTube = computed(() => useYouTube.value && youTubePlaying.value)

eventBus.on('PLAY_YOUTUBE_VIDEO', () => (youTubePlaying.value = true))
</script>
