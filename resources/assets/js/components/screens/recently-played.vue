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
          v-if="state.songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          :songs="state.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </ScreenHeader>

    <SongList v-if="state.songs.length" :items="state.songs" type="recently-played" :sortable="false"/>

    <ScreenPlaceholder v-else>
      <template v-slot:icon>
        <i class="fa fa-clock-o"></i>
      </template>
      No songs recently played.
      <span class="secondary d-block">
        Start playing to populate this playlist.
      </span>
    </ScreenPlaceholder>
  </section>
</template>

<script lang="ts" setup>
import { eventBus, pluralize } from '@/utils'
import { recentlyPlayedStore } from '@/stores'
import { useSongList } from '@/composables'
import { defineAsyncComponent, reactive } from 'vue'
import { playback } from '@/services'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const ScreenPlaceholder = defineAsyncComponent(() => import('@/components/ui/screen-placeholder.vue'))

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songList,
  meta,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  playSelected,
  toggleControls
} = useSongList()

const state = reactive(recentlyPlayedStore.state)

const playAll = () => playback.queueAndPlay(state.songs)

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
