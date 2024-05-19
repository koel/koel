<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
        Current Queue
        <ControlsToggle v-model="showingControls" />

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="songs.length" #meta>
          <span>{{ pluralize(songs, 'item') }}</span>
          <span>{{ duration }}</span>
        </template>

        <template #controls>
          <SongListControls
            v-if="songs.length && (!isPhone || showingControls)"
            :config="config"
            @filter="applyFilter"
            @clear-queue="clearQueue"
            @play-all="playAll"
            @play-selected="playSelected"
          />
        </template>
      </ScreenHeader>
    </template>

    <SongListSkeleton v-if="loading" class="-m-6" />
    <SongList
      v-if="songs.length"
      ref="songList"
      class="-m-6"
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
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faCoffee } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRef } from 'vue'
import { pluralize } from '@/utils'
import { commonStore, queueStore, songStore } from '@/stores'
import { cache, playbackService } from '@/services'
import { useErrorHandler, useRouter, useSongList, useSongListControls } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const { go, onScreenActivated } = useRouter()

const {
  SongList,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  songs,
  songList,
  duration,
  thumbnails,
  selectedPlayables,
  showingControls,
  isPhone,
  playSelected,
  applyFilter,
  onScrollBreakpoint
} = useSongList(toRef(queueStore.state, 'playables'), { type: 'Queue' }, { reorderable: true, sortable: false })

const { SongListControls, config } = useSongListControls('Queue')

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
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const clearQueue = () => {
  playbackService.stop()
  queueStore.clear()
}

const removeSelected = () => {
  if (!selectedPlayables.value.length) return

  const currentSongId = queueStore.current?.id
  queueStore.unqueue(selectedPlayables.value)

  if (currentSongId && selectedPlayables.value.find(({ id }) => id === currentSongId)) {
    playbackService.playNext()
  }
}

const onPressEnter = () => selectedPlayables.value.length && playbackService.play(selectedPlayables.value[0])
const onReorder = (target: Playable, type: MoveType) => queueStore.move(selectedPlayables.value, target, type)

onScreenActivated('Queue', async () => {
  if (!cache.get('song-to-queue')) {
    return
  }

  let song: Playable | undefined

  try {
    loading.value = true
    song = await songStore.resolve(cache.get('song-to-queue')!)

    if (!song) {
      throw 'Song not found'
    }
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
    return
  } finally {
    cache.remove('song-to-queue')
    loading.value = false
  }

  queueStore.clearSilently()
  queueStore.queue(song!)
})
</script>
