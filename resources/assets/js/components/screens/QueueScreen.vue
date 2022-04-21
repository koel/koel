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
      :items="songs"
      :config="{ sortable: false }"
      type="queue"
      ref="songList"
    />

    <ScreenPlaceholder v-else>
      <template v-slot:icon>
        <i class="fa fa-coffee"></i>
      </template>

      No songs queued.
      <a v-if="showShuffleLibraryButton" class="start" @click.prevent="playAll(true)">Shuffle the whole library</a>?
    </ScreenPlaceholder>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, reactive, toRef } from 'vue'
import { pluralize } from '@/utils'
import { queueStore, songStore } from '@/stores'
import { playback } from '@/services'
import { useSongList } from '@/composables'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ScreenPlaceholder = defineAsyncComponent(() => import('@/components/ui/screen-placeholder.vue'))

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
</script>

<style lang="scss" scoped>
.start {
  color: var(--color-highlight);
}
</style>
