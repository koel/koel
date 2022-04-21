<template>
  <section id="recentlyPlayedWrapper">
    <ScreenHeader>
      Recently Played
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="meta.songCount">{{ pluralize(meta.songCount, 'song') }} • {{ meta.totalLength }}</span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
          :songs="songs"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList
      v-if="songs.length"
      ref="songList"
      :items="songs"
      :sortable="false"
      type="recently-played"
      @press:enter="onPressEnter"
    />

    <ScreenEmptyState v-else>
      <template v-slot:icon>
        <i class="fa fa-clock-o"></i>
      </template>
      No songs recently played.
      <span class="secondary d-block">Start playing to populate this playlist.</span>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { eventBus, pluralize } from '@/utils'
import { recentlyPlayedStore } from '@/stores'
import { useSongList } from '@/composables'
import { defineAsyncComponent, toRef } from 'vue'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ScreenEmptyState = defineAsyncComponent(() => import('@/components/ui/ScreenEmptyState.vue'))

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songs,
  songList,
  meta,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(toRef(recentlyPlayedStore.state, 'songs'))

eventBus.on({
  'LOAD_MAIN_CONTENT': (view: MainViewName) => view === 'RecentlyPlayed' && recentlyPlayedStore.fetchAll()
})
</script>

<style lang="scss">
#recentlyPlayedWrapper {
  .none {
    padding: 16px 24px;
  }
}
</style>
