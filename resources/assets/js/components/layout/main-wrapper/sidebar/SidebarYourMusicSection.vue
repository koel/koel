<template>
  <SidebarSection>
    <template #header>
      <SidebarSectionHeader>Your Music</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem href="#/home" screen="Home">
        <template #icon>
          <Icon :icon="faHome" fixed-width />
        </template>
        Home
      </SidebarItem>
      <QueueSidebarItem />
      <SidebarItem href="#/songs" screen="Songs">
        <template #icon>
          <Icon :icon="faMusic" fixed-width />
        </template>
        All Songs
      </SidebarItem>
      <SidebarItem href="#/albums" screen="Albums">
        <template #icon>
          <Icon :icon="faCompactDisc" fixed-width />
        </template>
        Albums
      </SidebarItem>
      <SidebarItem href="#/artists" screen="Artists">
        <template #icon>
          <Icon :icon="faMicrophone" fixed-width />
        </template>
        Artists
      </SidebarItem>
      <SidebarItem href="#/genres" screen="Genres">
        <template #icon>
          <Icon :icon="faTags" fixed-width />
        </template>
        Genres
      </SidebarItem>
      <YouTubeSidebarItem v-if="youtubeVideoTitle" data-testid="youtube">
        {{ youtubeVideoTitle }}
      </YouTubeSidebarItem>
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { faCompactDisc, faHome, faMicrophone, faMusic, faTags } from '@fortawesome/free-solid-svg-icons'
import { unescape } from 'lodash'
import { ref } from 'vue'
import { eventBus } from '@/utils'

import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'
import QueueSidebarItem from '@/components/layout/main-wrapper/sidebar/QueueSidebarItem.vue'
import YouTubeSidebarItem from '@/components/layout/main-wrapper/sidebar/YouTubeSidebarItem.vue'

const youtubeVideoTitle = ref<string | null>(null)

eventBus.on('PLAY_YOUTUBE_VIDEO', payload => (youtubeVideoTitle.value = unescape(payload.title)))
</script>
