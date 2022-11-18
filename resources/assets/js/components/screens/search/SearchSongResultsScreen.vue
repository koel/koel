<template>
  <section id="songResultsWrapper">
    <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      Songs for <span class="text-thin">{{ decodedQ }}</span>
      <ControlsToggle v-model="showingControls"/>

      <template v-slot:thumbnail>
        <ThumbnailStack :thumbnails="thumbnails"/>
      </template>

      <template v-if="songs.length" v-slot:meta>
        <span>{{ pluralize(songs, 'song') }}</span>
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
import { computed, onMounted, ref, toRef } from 'vue'
import { searchStore } from '@/stores'
import { useRouter, useSongList } from '@/composables'
import { pluralize } from '@/utils'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'

const { getRouteParam } = useRouter()
const q = ref('')

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
  sort,
  onScrollBreakpoint
} = useSongList(toRef(searchStore.state, 'songs'))

const decodedQ = computed(() => decodeURIComponent(q.value))
const loading = ref(false)

searchStore.resetSongResultState()

onMounted(async () => {
  q.value = getRouteParam('q') || ''
  if (!q.value) return

  loading.value = true
  await searchStore.songSearch(q.value)
  loading.value = false
})
</script>
