<template>
  <SidebarSection>
    <template #header>
      <SidebarSectionHeader>Your Library</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem :href="url('songs.index')" :active="isCurrentScreen('Songs')">
        <template #icon>
          <Icon :icon="faMusic" fixed-width />
        </template>
        All Songs
      </SidebarItem>
      <SidebarItem :href="url('albums.index')" :active="isCurrentScreen('Albums', 'Album')">
        <template #icon>
          <Icon :icon="faCompactDisc" fixed-width />
        </template>
        Albums
      </SidebarItem>
      <SidebarItem :href="url('artists.index')" :active="isCurrentScreen('Artists', 'Artist')">
        <template #icon>
          <MicVocalIcon :size="16" />
        </template>
        Artists
      </SidebarItem>
      <SidebarItem :href="url('genres.index')" :active="isCurrentScreen('Genres', 'Genre')">
        <template #icon>
          <GuitarIcon :size="16" />
        </template>
        Genres
      </SidebarItem>
      <YouTubeSidebarItem v-if="youtubeVideoTitle" data-testid="youtube" :active="isCurrentScreen('YouTube')">
        {{ youtubeVideoTitle }}
      </YouTubeSidebarItem>
      <SidebarItem
        v-if="!isDemo"
        :href="url('podcasts.index')"
        :active="isCurrentScreen('Podcasts', 'Podcast', 'Episode')"
      >
        <template #icon>
          <Icon :icon="faPodcast" fixed-width />
        </template>
        Podcasts
      </SidebarItem>
      <SidebarItem :href="url('radio-stations.index')" :active="isCurrentScreen('Radio.Stations')">
        <template #icon>
          <RadioIcon :size="16" />
        </template>
        Radio
      </SidebarItem>
      <SidebarItem v-if="supportsOffline" :href="url('offline-songs')" :active="isCurrentScreen('OfflineSongs')">
        <template #icon>
          <Icon :icon="faCloudArrowDown" fixed-width />
        </template>
        Available Offline
      </SidebarItem>
      <MediaBrowserMenuItem v-if="usesMediaBrowser" :active="isCurrentScreen('MediaBrowser')" />
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { faCloudArrowDown, faCompactDisc, faMusic, faPodcast } from '@fortawesome/free-solid-svg-icons'
import { GuitarIcon, MicVocalIcon, RadioIcon } from 'lucide-vue-next'
import { unescape } from 'lodash'
import { computed, ref, toRef } from 'vue'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { eventBus } from '@/utils/eventBus'
import { useRouter } from '@/composables/useRouter'
import { commonStore } from '@/stores/commonStore'

import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'
import YouTubeSidebarItem from '@/components/layout/main-wrapper/sidebar/YouTubeSidebarItem.vue'
import MediaBrowserMenuItem from '@/components/layout/main-wrapper/sidebar/MediaBrowserMenuItem.vue'

const youtubeVideoTitle = ref<string | null>(null)
const { url, isCurrentScreen } = useRouter()

const usesMediaBrowser = toRef(commonStore.state, 'uses_media_browser')
const { swReady, cachedSongCount } = useOfflinePlayback()
const supportsOffline = computed(() => swReady.value && cachedSongCount.value > 0)
const isDemo = window.IS_DEMO

eventBus.on('PLAY_YOUTUBE_VIDEO', payload => (youtubeVideoTitle.value = unescape(payload.title)))
</script>
