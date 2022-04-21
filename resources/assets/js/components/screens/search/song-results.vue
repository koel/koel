<template>
  <section id="songResultsWrapper">
    <ScreenHeader>
      Showing Songs for <strong>{{ decodedQ }}</strong>
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="meta.songCount">{{ pluralize(meta.songCount, 'song') }} • {{ meta.totalLength }}</span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
          :songs="songs"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList ref="songList" :items="songs" type="search-results" @press:enter="onPressEnter"/>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, toRef, toRefs } from 'vue'
import { searchStore } from '@/stores'
import { useSongList } from '@/composables'
import { pluralize } from '@/utils'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))

const props = defineProps<{ q: string }>()
const { q } = toRefs(props)

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
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(toRef(searchStore.state, 'songs'))

const decodedQ = computed(() => decodeURIComponent(q.value))

searchStore.resetSongResultState()
searchStore.songSearch(decodedQ.value)
</script>
