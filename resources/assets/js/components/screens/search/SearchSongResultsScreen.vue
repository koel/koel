<template>
  <section id="songResultsWrapper">
    <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
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

    <SongListSkeleton v-if="loading"/>
    <SongList v-else ref="songList" @sort="sort" @press:enter="onPressEnter" @scroll-breakpoint="onScrollBreakpoint"/>
  </section>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRef, toRefs } from 'vue'
import { searchStore } from '@/stores'
import { useSongList } from '@/composables'
import { pluralize } from '@/utils'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'

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
const loading = ref(false)

searchStore.resetSongResultState()

onMounted(async () => {
  loading.value = true
  await searchStore.songSearch(q.value)
  loading.value = false
})
</script>
