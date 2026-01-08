<template>
  <SidebarSection>
    <template #header>
      <SidebarSectionHeader>{{ $t('sidebar.yourLibrary') }}</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem :href="url('songs.index')" :active="isCurrentScreen('Songs')">
        <template #icon>
          <Icon :icon="faMusic" fixed-width />
        </template>
        {{ $t('sidebar.allSongs') }}
      </SidebarItem>
      <SidebarItem :href="url('albums.index')" :active="isCurrentScreen('Albums', 'Album')">
        <template #icon>
          <Icon :icon="faCompactDisc" fixed-width />
        </template>
        {{ $t('sidebar.albums') }}
      </SidebarItem>
      <SidebarItem :href="url('artists.index')" :active="isCurrentScreen('Artists', 'Artist')">
        <template #icon>
          <MicVocalIcon :size="16" />
        </template>
        {{ $t('sidebar.artists') }}
      </SidebarItem>
      <SidebarItem :href="url('genres.index')" :active="isCurrentScreen('Genres', 'Genre')">
        <template #icon>
          <GuitarIcon :size="16" />
        </template>
        {{ $t('sidebar.genres') }}
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
        {{ $t('sidebar.podcasts') }}
      </SidebarItem>
      <SidebarItem :href="url('radio-stations.index')" :active="isCurrentScreen('Radio.Stations')">
        <template #icon>
          <RadioIcon :size="16" />
        </template>
        {{ $t('sidebar.radio') }}
      </SidebarItem>
      <MediaBrowserMenuItem v-if="usesMediaBrowser" :active="isCurrentScreen('MediaBrowser')" />
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { faCompactDisc, faMusic, faPodcast } from '@fortawesome/free-solid-svg-icons'
import { GuitarIcon, MicVocalIcon, RadioIcon } from 'lucide-vue-next'
import { unescape } from 'lodash'
import { ref, toRef } from 'vue'
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
const isDemo = window.IS_DEMO

eventBus.on('PLAY_YOUTUBE_VIDEO', payload => (youtubeVideoTitle.value = unescape(payload.title)))
</script>
