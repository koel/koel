<template>
  <section id="queueWrapper">
    <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      Current Queue
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
          @clearQueue="clearQueue"
          :config="controlConfig"
        />
      </template>
    </ScreenHeader>

    <SongListSkeleton v-if="loading"/>
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
import { computed, ref, toRef } from 'vue'
import { eventBus, logger, pluralize, requireInjection } from '@/utils'
import { commonStore, queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import { useSongList } from '@/composables'
import { DialogBoxKey } from '@/symbols'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'

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
  onScrollBreakpoint
} = useSongList(toRef(queueStore.state, 'songs'), 'queue', { sortable: false })

const loading = ref(false)
const libraryNotEmpty = computed(() => commonStore.state.song_count > 0)

const playAll = (shuffle = true) => playbackService.queueAndPlay(songs.value, shuffle)

const shuffleSome = async () => {
  try {
    loading.value = true
    await queueStore.fetchRandom()
    await playbackService.playFirstInQueue()
  } catch (e) {
    dialog.value.error('Failed to fetch songs to play. Please try again.', 'Error')
    logger.error(e)
  } finally {
    loading.value = false
  }
}

const clearQueue = () => queueStore.clear()
const removeSelected = () => selectedSongs.value.length && queueStore.unqueue(selectedSongs.value)
const onPressEnter = () => selectedSongs.value.length && playbackService.play(selectedSongs.value[0])
const onReorder = (target: Song) => queueStore.move(selectedSongs.value, target)

eventBus.on('SONG_QUEUED_FROM_ROUTE', async (id: string) => {
  let song: Song | undefined

  try {
    loading.value = true
    song = await songStore.resolve(id)

    if (!song) {
      throw new Error('Song not found')
    }
  } catch (e) {
    dialog.value.error('Song not found. Please double check and try again.', 'Error')
    logger.error(e)
    return
  } finally {
    loading.value = false
  }

  queueStore.queueIfNotQueued(song)
  await playbackService.play(song)
})
</script>
