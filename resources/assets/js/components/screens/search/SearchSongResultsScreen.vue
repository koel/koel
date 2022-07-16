<template>
  <section id="songResultsWrapper">
    <ScreenHeader :layout="headerLayout" has-thumbnail>
      Songs for <span class="text-thin">{{ decodedQ }}</span>
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
        />
      </template>
    </ScreenHeader>

    <SongList ref="songList" @sort="sort" @press:enter="onPressEnter" @scroll-breakpoint="onScrollBreakpoint"/>
  </section>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs } from 'vue'
import { searchStore } from '@/stores'
import { useSongList } from '@/composables'
import { pluralize } from '@/utils'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'

const props = defineProps<{ q: string }>()
const { q } = toRefs(props)

const {
  SongList,
  SongListControls,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  songs,
  songList,
  thumbnails,
  duration,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls,
  sort,
  onScrollBreakpoint
} = useSongList(toRef(searchStore.state, 'songs'), 'search-results')

const decodedQ = computed(() => decodeURIComponent(q.value))

searchStore.resetSongResultState()
searchStore.songSearch(decodedQ.value)
</script>
