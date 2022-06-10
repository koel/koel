<template>
  <section id="songResultsWrapper">
    <ScreenHeader>
      Showing Songs for <strong>{{ decodedQ }}</strong>
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="songs.length">{{ pluralize(songs.length, 'song') }} • {{ duration }}</span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList ref="songList" @press:enter="onPressEnter" @sort="sort"/>
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
  ControlsToggle,
  songs,
  songList,
  duration,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls,
  sort
} = useSongList(toRef(searchStore.state, 'songs'), 'search-results')

const decodedQ = computed(() => decodeURIComponent(q.value))

searchStore.resetSongResultState()
searchStore.songSearch(decodedQ.value)
</script>
