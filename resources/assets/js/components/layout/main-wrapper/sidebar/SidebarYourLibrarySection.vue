<template>
  <SidebarSection>
    <template #header>
      <SidebarSectionHeader>Your Library</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem :href="url('songs.index')" screen="Songs">
        <template #icon>
          <Icon :icon="faMusic" fixed-width />
        </template>
        All Songs
      </SidebarItem>
      <SidebarItem :href="url('albums.index')" screen="Albums">
        <template #icon>
          <Icon :icon="faCompactDisc" fixed-width />
        </template>
        Albums
      </SidebarItem>
      <SidebarItem :href="url('artists.index')" screen="Artists">
        <template #icon>
          <MicVocalIcon size="16" />
        </template>
        Artists
      </SidebarItem>
      <SidebarItem :href="url('genres.index')" screen="Genres">
        <template #icon>
          <GuitarIcon size="16" />
        </template>
        Genres
      </SidebarItem>
      <YouTubeSidebarItem v-if="youtubeVideoTitle" data-testid="youtube">
        {{ youtubeVideoTitle }}
      </YouTubeSidebarItem>
      <SidebarItem :href="url('podcasts.index')" screen="Podcasts">
        <template #icon>
          <Icon :icon="faPodcast" fixed-width />
        </template>
        Podcasts
      </SidebarItem>
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { faCompactDisc, faMusic, faPodcast } from '@fortawesome/free-solid-svg-icons'
import { GuitarIcon, MicVocalIcon } from 'lucide-vue-next'
import { unescape } from 'lodash'
import { ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { useRouter } from '@/composables/useRouter'

import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'
import YouTubeSidebarItem from '@/components/layout/main-wrapper/sidebar/YouTubeSidebarItem.vue'

const youtubeVideoTitle = ref<string | null>(null)
const { url } = useRouter()

eventBus.on('PLAY_YOUTUBE_VIDEO', payload => (youtubeVideoTitle.value = unescape(payload.title)))
</script>
