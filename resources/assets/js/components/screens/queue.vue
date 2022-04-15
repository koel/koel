<template>
  <section id="queueWrapper">
    <screen-header>
      Current Queue
      <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="meta.songCount" data-test="list-meta">
          {{ pluralize(meta.songCount, 'song') }} • {{ meta.totalLength }}
        </span>
      </template>

      <template v-slot:controls>
        <song-list-controls
          v-if="state.songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          @clearQueue="clearQueue"
          :songs="state.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </screen-header>

    <song-list
      v-if="state.songs.length"
      :items="state.songs"
      :config="{ sortable: false }"
      type="queue"
      ref="songList"
    />

    <screen-placeholder v-else>
      <template v-slot:icon>
        <i class="fa fa-coffee"></i>
      </template>

      No songs queued.
      <span class="secondary d-block" v-if="shouldShowShufflingAllLink">
        How about
        <a class="start" @click.prevent="shuffleAll">shuffling all songs</a>?
      </span>
    </screen-placeholder>
  </section>
</template>

<script lang="ts" setup>
import { pluralize } from '@/utils'
import { queueStore, songStore } from '@/stores'
import { playback } from '@/services'
import { useSongList } from '@/composables'
import { computed, defineAsyncComponent, reactive } from 'vue'

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
} = useSongList({
  clearQueue: true
})

const state = reactive(queueStore.state)
const songState = reactive(songStore.state)

const shouldShowShufflingAllLink = computed(() => songState.songs.length > 0)

const playAll = () => {
  // @ts-ignore
  playback.queueAndPlay(state.songs.length ? songList.value?.getAllSongsWithSort() : songStore.all)
}

const shuffleAll = async () => await playback.queueAndPlay(songStore.all, true)
const clearQueue = () => queueStore.clear()
</script>
