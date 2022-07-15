<template>
  <section id="queueWrapper">
    <ScreenHeader>
      Current Queue
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta v-if="songs.length">
        <span>{{ pluralize(songs.length, 'song') }}</span>
        <span>{{ duration }}</span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          @clearQueue="clearQueue"
          :config="controlConfig"
        />
      </template>
    </ScreenHeader>

    <SongList
      v-if="songs.length"
      ref="songList"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @reorder="onReorder"
    />

    <ScreenEmptyState v-else>
      <template v-slot:icon>
        <icon :icon="faCoffee"/>
      </template>

      No songs queued.
      <span class="d-block secondary" v-if="libraryNotEmpty">
        How about
        <a data-testid="shuffle-library" class="start" @click.prevent="shuffleSome">playing some random songs</a>?
      </span>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { faCoffee } from '@fortawesome/free-solid-svg-icons'
import { computed, toRef } from 'vue'
import { pluralize } from '@/utils'
import { commonStore, queueStore } from '@/stores'
import { playbackService } from '@/services'
import { useSongList } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'

const controlConfig: Partial<SongListControlsConfig> = { clearQueue: true }

const {
  SongList,
  SongListControls,
  ControlsToggle,
  songs,
  songList,
  duration,
  selectedSongs,
  showingControls,
  isPhone,
  playSelected,
  toggleControls
} = useSongList(toRef(queueStore.state, 'songs'), 'queue', { sortable: false })

const libraryNotEmpty = computed(() => commonStore.state.song_count > 0)

const playAll = (shuffle = true) => playbackService.queueAndPlay(songs.value, shuffle)

const shuffleSome = async () => {
  await queueStore.fetchRandom()
  await playbackService.playFirstInQueue()
}

const clearQueue = () => queueStore.clear()
const removeSelected = () => selectedSongs.value.length && queueStore.unqueue(selectedSongs.value)
const onPressEnter = () => selectedSongs.value.length && playbackService.play(selectedSongs.value[0])
const onReorder = (target: Song) => queueStore.move(selectedSongs.value, target)
</script>
