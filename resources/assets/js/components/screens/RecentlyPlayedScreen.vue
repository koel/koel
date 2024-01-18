<template>
  <section id="recentlyPlayedWrapper">
    <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      Recently Played
      <ControlsToggle v-model="showingControls" />

      <template #thumbnail>
        <ThumbnailStack :thumbnails="thumbnails" />
      </template>

      <template v-if="songs.length" #meta>
        <span>{{ pluralize(songs, 'song') }}</span>
        <span>{{ duration }}</span>
      </template>

      <template #controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          :config="config"
          @filter="applyFilter"
          @play-all="playAll"
          @play-selected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongListSkeleton v-if="loading" />

    <SongList v-if="songs.length" ref="songList" @press:enter="onPressEnter" @scroll-breakpoint="onScrollBreakpoint" />

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faClock" />
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
import { useRouter, useSongList, useSongListControls } from '@/composables'
import { ref, toRef } from 'vue'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'

const recentlyPlayedSongs = toRef(recentlyPlayedStore.state, 'songs')

const {
  SongList,
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
  applyFilter,
  onScrollBreakpoint
} = useSongList(recentlyPlayedSongs)

const { SongListControls, config } = useSongListControls('RecentlyPlayed')

let initialized = false
let loading = ref(false)

useRouter().onScreenActivated('RecentlyPlayed', async () => {
  if (!initialized) {
    loading.value = true
    initialized = true
    await recentlyPlayedStore.fetch()
    loading.value = false
  }
})
</script>
