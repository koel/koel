<template>
  <section id="recentlyPlayedWrapper">
    <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      Recently Played
      <ControlsToggle v-model="showingControls"/>

      <template v-slot:thumbnail>
        <ThumbnailStack :thumbnails="thumbnails"/>
      </template>

      <template v-slot:meta v-if="songs.length">
        <span>{{ pluralize(songs, 'song') }}</span>
        <span>{{ duration }}</span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongListSkeleton v-if="loading"/>
    <SongList v-if="songs.length" ref="songList" @press:enter="onPressEnter" @scroll-breakpoint="onScrollBreakpoint"/>

    <ScreenEmptyState v-else>
      <template v-slot:icon>
        <icon :icon="faClock"/>
      </template>
      No songs recently played.
      <span class="secondary d-block">Start playing to populate this playlist.</span>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { faClock } from '@fortawesome/free-regular-svg-icons'
import { pluralize } from '@/utils'
import { recentlyPlayedStore } from '@/stores'
import { useScreen, useSongList } from '@/composables'
import { ref, toRef } from 'vue'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'

const recentlyPlayedSongs = toRef(recentlyPlayedStore.state, 'songs')

const {
  SongList,
  SongListControls,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  songs,
  songList,
  thumbnails,
  duration,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  onScrollBreakpoint
} = useSongList(recentlyPlayedSongs, 'RecentlyPlayed', { sortable: false })

let initialized = false
let loading = ref(false)

useScreen('RecentlyPlayed').onScreenActivated(async () => {
  if (!initialized) {
    loading.value = true
    initialized = true
    await recentlyPlayedStore.fetch()
    loading.value = false
  }
})
</script>
