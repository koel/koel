<template>
  <section id="songsWrapper">
    <ScreenHeader>
      All Songs
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="totalSongCount">{{ pluralize(totalSongCount, 'song') }} • {{ totalDuration }}</span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList ref="songList" @press:enter="onPressEnter" @scrolled-to-end="fetchSongs" @sort="sort"/>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, toRef } from 'vue'
import { eventBus, pluralize, secondsToHis } from '@/utils'
import { commonStore, queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import { useSongList } from '@/composables'
import router from '@/router'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))

const totalSongCount = toRef(commonStore.state, 'song_count')
const totalDuration = computed(() => secondsToHis(commonStore.state.song_length))

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
  playSelected,
  toggleControls
} = useSongList(toRef(songStore.state, 'songs'), 'all-songs')

let initialized = false
let loading = false
let sortField: SongListSortField = 'title' // @todo get from query string
let sortOrder: SortOrder = 'asc'

const page = ref<number | null>(1)
const moreSongsAvailable = computed(() => page.value !== null)

const sort = async (field: SongListSortField, order: SortOrder) => {
  page.value = 1
  songStore.state.songs = []
  sortField = field
  sortOrder = order

  await fetchSongs()
}

const fetchSongs = async () => {
  if (!moreSongsAvailable.value || loading) return

  loading = true
  page.value = await songStore.fetch(sortField, sortOrder, page.value!)
  loading = false
}

const playAll = async (shuffle: boolean) => {
  if (shuffle) {
    await queueStore.fetchRandom()
  } else {
    await queueStore.fetchInOrder(sortField, sortOrder)
  }

  await playbackService.playFirstInQueue()
  await router.go('/queue')
}

eventBus.on('LOAD_MAIN_CONTENT', async (view: MainViewName) => {
  if (view === 'Songs' && !initialized) {
    await fetchSongs()
    initialized = true
  }
})
</script>
