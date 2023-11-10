<template>
  <section id="queueWrapper">
    <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      Current Queue
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
          @filter="applyFilter"
          @clear-queue="clearQueue"
          @play-all="playAll"
          @play-selected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongListSkeleton v-if="loading" />
    <SongList
      v-if="songs.length"
      ref="songList"
      @reorder="onReorder"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @scroll-breakpoint="onScrollBreakpoint"
    />

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faCoffee" />
      </template>

      No songs queued.
      <span v-if="libraryNotEmpty" class="d-block secondary">
        How about
        <a class="start" @click.prevent="shuffleSome">playing some random songs</a>?
      </span>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { faCoffee } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRef } from 'vue'
import { eventBus, logger, pluralize } from '@/utils'
import { commonStore, queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import { useDialogBox, useRouter, useSongList } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'

const { go } = useRouter()
const { showErrorDialog } = useDialogBox()

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
  applyFilter,
  onScrollBreakpoint
} = useSongList(toRef(queueStore.state, 'songs'))

const loading = ref(false)
const libraryNotEmpty = computed(() => commonStore.state.song_count > 0)

const playAll = async (shuffle = true) => {
  playbackService.queueAndPlay(songs.value, shuffle)
  go('queue')
}

const shuffleSome = async () => {
  try {
    loading.value = true
    await queueStore.fetchRandom()
    await playbackService.playFirstInQueue()
  } catch (e) {
    showErrorDialog('Failed to fetch songs to play. Please try again.', 'Error')
    logger.error(e)
  } finally {
    loading.value = false
  }
}

const clearQueue = () => {
  playbackService.stop()
  queueStore.clear()
}

const removeSelected = () => {
  if (!selectedSongs.value.length) return

  const currentSongId = queueStore.current?.id

  queueStore.unqueue(selectedSongs.value)

  if (currentSongId && selectedSongs.value.find(song => song.id === currentSongId)) {
    playbackService.playNext()
  }
}

const onPressEnter = () => selectedSongs.value.length && playbackService.play(selectedSongs.value[0])
const onReorder = (target: Song) => queueStore.move(selectedSongs.value, target)

eventBus.on('SONG_QUEUED_FROM_ROUTE', async id => {
  let song: Song | undefined

  try {
    loading.value = true
    song = await songStore.resolve(id)

    if (!song) {
      throw new Error('Song not found')
    }
  } catch (e) {
    showErrorDialog('Song not found. Please double check and try again.', 'Error')
    logger.error(e)
    return
  } finally {
    loading.value = false
  }

  queueStore.queueIfNotQueued(song!)
  await playbackService.play(song!)
})
</script>
