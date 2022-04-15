<template>
  <section id="songsWrapper">
    <ScreenHeader>
      All Songs
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

    <SongList :items="state.songs" type="all-songs" ref="songList"/>
  </section>
</template>

<script lang="ts" setup>
import { pluralize } from '@/utils'
import { songStore } from '@/stores'
import { useSongList } from '@/composables'
import { defineAsyncComponent, reactive } from 'vue'

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
  playAll,
  playSelected,
  toggleControls
} = useSongList()

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const state = reactive(songStore.state)
</script>
