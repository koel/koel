<template>
  <section id="songsWrapper">
    <ScreenHeader>
      All Songs
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="songs.length">{{ pluralize(songs.length, 'song') }} • {{ duration }}</span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          :songs="songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </ScreenHeader>

    <SongList ref="songList" :items="songs" type="all-songs" @press:enter="onPressEnter"/>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, toRef } from 'vue'
import { pluralize } from '@/utils'
import { songStore } from '@/stores'
import { useSongList } from '@/composables'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songs,
  songList,
  duration,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(toRef(songStore.state, 'songs'))
</script>
