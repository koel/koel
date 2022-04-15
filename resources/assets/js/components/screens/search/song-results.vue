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
          v-if="state.songs.length && (!isPhone || showingControls)"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
          :songs="state.songs"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList ref="songList" :items="state.songs" type="search-results"/>
  </section>
</template>

<script lang="ts" setup>
import { searchStore } from '@/stores'
import { computed, defineAsyncComponent, reactive, toRefs } from 'vue'
import { useSongList } from '@/composables'
import { pluralize } from '@/utils'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songList,
  state: songListState,
  meta,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  playAll,
  playSelected,
  toggleControls
} = useSongList()

const props = defineProps<{ q: string }>()
const { q } = toRefs(props)

const state = reactive(searchStore.state)

const decodedQ = computed(() => decodeURIComponent(q.value))

searchStore.resetSongResultState()
searchStore.songSearch(decodedQ.value)
</script>
