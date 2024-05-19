<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
        Results for <span class="font-thin">{{ decodedQ }}</span>
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
            @play-all="playAll"
            @play-selected="playSelected"
          />
        </template>
      </ScreenHeader>
    </template>

    <SongListSkeleton v-if="loading" class="-m-6" />
    <SongList
      v-else
      ref="songList"
      class="-m-6"
      @sort="sort"
      @press:enter="onPressEnter"
      @scroll-breakpoint="onScrollBreakpoint"
    />
  </ScreenBase>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRef } from 'vue'
import { searchStore } from '@/stores'
import { useRouter, useSongList, useSongListControls } from '@/composables'
import { pluralize } from '@/utils'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const { getRouteParam } = useRouter()
const q = ref('')

const {
  SongList,
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
  applyFilter,
  sort,
  onScrollBreakpoint
} = useSongList(toRef(searchStore.state, 'songs'), { type: 'Search.Songs' })

const { SongListControls, config } = useSongListControls('Search.Songs')
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
