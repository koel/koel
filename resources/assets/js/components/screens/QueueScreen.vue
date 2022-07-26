<template>
  <section id="queueWrapper">
    <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      Current Queue
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <ThumbnailStack :thumbnails="thumbnails"/>
      </template>

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
      @scroll-breakpoint="onScrollBreakpoint"
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
import { logger, pluralize, requireInjection } from '@/utils'
import { commonStore, queueStore } from '@/stores'
import { playbackService } from '@/services'
import { useSongList } from '@/composables'
import { DialogBoxKey } from '@/symbols'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'

const dialog = requireInjection(DialogBoxKey)
const controlConfig: Partial<SongListControlsConfig> = { clearQueue: true }

const {
  SongList,
  SongListControls,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  songs,
  songList,
  duration,
  thumbnails,
  selectedSongs,
  showingControls,
  isPhone,
  playSelected,
  toggleControls,
  onScrollBreakpoint
} = useSongList(toRef(queueStore.state, 'songs'), 'queue', { sortable: false })

const libraryNotEmpty = computed(() => commonStore.state.song_count > 0)

const playAll = (shuffle = true) => playbackService.queueAndPlay(songs.value, shuffle)

const shuffleSome = async () => {
  try {
    await queueStore.fetchRandom()
    await playbackService.playFirstInQueue()
  } catch (e) {
    dialog.value.error('Failed to fetch songs to play. Please try again.', 'Error')
    logger.error(e)
  }
}

const clearQueue = () => queueStore.clear()
const removeSelected = () => selectedSongs.value.length && queueStore.unqueue(selectedSongs.value)
const onPressEnter = () => selectedSongs.value.length && playbackService.play(selectedSongs.value[0])
const onReorder = (target: Song) => queueStore.move(selectedSongs.value, target)
</script>
