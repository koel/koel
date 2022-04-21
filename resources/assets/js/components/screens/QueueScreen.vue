<template>
  <section id="queueWrapper">
    <ScreenHeader>
      Current Queue
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="meta.songCount" data-test="list-meta">
          {{ pluralize(meta.songCount, 'song') }} • {{ meta.totalLength }}
        </span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          @clearQueue="clearQueue"
          :songs="songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </ScreenHeader>

    <SongList
      v-if="songs.length"
      ref="songList"
      :config="{ sortable: false }"
      :items="songs"
      type="queue"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
    />

    <ScreenEmptyState v-else>
      <template v-slot:icon>
        <i class="fa fa-coffee"></i>
      </template>

      No songs queued.
      <span class="d-block secondary">
        How about
        <a v-if="showShuffleLibraryButton" class="start" @click.prevent="playAll(true)">shuffling the whole library</a>?
      </span>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, reactive, toRef } from 'vue'
import { pluralize } from '@/utils'
import { queueStore, songStore } from '@/stores'
import { playback } from '@/services'
import { useSongList } from '@/composables'

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
  playSelected,
  toggleControls
} = useSongList(toRef(queueStore.state, 'songs'), { clearQueue: true })

const songState = reactive(songStore.state)

const showShuffleLibraryButton = computed(() => songState.songs.length > 0)

const playAll = (shuffle: boolean) => playback.queueAndPlay(songs.value.length ? songs.value : songStore.all, shuffle)
const clearQueue = () => queueStore.clear()
const removeSelected = () => selectedSongs.value.length && queueStore.unqueue(selectedSongs.value)
const onPressEnter = () => selectedSongs.value.length && playback.play(selectedSongs.value[0])
</script>

<style lang="scss" scoped>
.start {
  color: var(--color-highlight);
}
</style>
