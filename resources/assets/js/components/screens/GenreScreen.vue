<template>
  <section id="genreWrapper">
    <ScreenHeader :layout="headerLayout" v-if="genre">
      Genre: <span class="text-thin">{{ decodeURIComponent(name) }}</span>
      <ControlsToggle v-if="songs.length" v-model="showingControls"/>

      <template v-slot:thumbnail>
        <ThumbnailStack :thumbnails="thumbnails"/>
      </template>

      <template v-if="genre" v-slot:meta>
        <span>{{ pluralize(genre.song_count, 'song') }}</span>
        <span>{{ duration }}</span>
      </template>

      <template v-slot:controls>
        <SongListControls v-if="!isPhone || showingControls" @playAll="playAll" @playSelected="playSelected"/>
      </template>
    </ScreenHeader>
    <ScreenHeaderSkeleton v-else/>

    <SongListSkeleton v-if="showSkeletons"/>
    <SongList
      v-else
      ref="songList"
      @sort="sort"
      @press:enter="onPressEnter"
      @scroll-breakpoint="onScrollBreakpoint"
      @scrolled-to-end="fetch"
    />

    <ScreenEmptyState v-if="!songs.length && !loading">
      <template v-slot:icon>
        <icon :icon="faTags"/>
      </template>

      No songs in this genre.
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, watch } from 'vue'
import { faTags } from '@fortawesome/free-solid-svg-icons'
import { eventBus, logger, pluralize, requireInjection, secondsToHumanReadable } from '@/utils'
import { DialogBoxKey, RouterKey } from '@/symbols'
import { useSongList } from '@/composables'
import { genreStore, songStore } from '@/stores'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import { playbackService } from '@/services'

const {
  SongList,
  SongListControls,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  songs,
  songList,
  thumbnails,
  showingControls,
  isPhone,
  onPressEnter,
  playSelected,
  onScrollBreakpoint
} = useSongList(ref<Song[]>([]))

const router = requireInjection(RouterKey)
const dialog = requireInjection(DialogBoxKey)

let sortField: SongListSortField = 'title'
let sortOrder: SortOrder = 'asc'

const randomSongCount = 500
const name = ref<string | null>(null)
const genre = ref<Genre | null>(null)
const loading = ref(false)
const page = ref<number | null>(1)

const moreSongsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && songs.value.length === 0)
const duration = computed(() => secondsToHumanReadable(genre.value?.length ?? 0))

const sort = async (field: SongListSortField, order: SortOrder) => {
  page.value = 1
  songs.value = []
  sortField = field
  sortOrder = order

  await fetch()
}

const fetch = async () => {
  if (!moreSongsAvailable.value || loading.value) return

  loading.value = true

  try {
    let fetched

    [genre.value, fetched] = await Promise.all([
      genreStore.fetchOne(name.value!),
      songStore.paginateForGenre(name.value!, sortField, sortOrder, page.value!)
    ])

    page.value = fetched.nextPage
    songs.value.push(...fetched.songs)
  } catch (e) {
    dialog.value.error('Failed to fetch genre details or genre was not found.')
    logger.error(e)
  } finally {
    loading.value = false
  }
}

const refresh = async () => {
  genre.value = null
  page.value = 1
  songs.value = []

  await fetch()
}

const getNameFromRoute = () => router.$currentRoute.value.params?.name || null

router.onRouteChanged(route => {
  if (route.screen !== 'Genre') return
  name.value = getNameFromRoute()
})

const playAll = async () => {
  if (!genre.value) return

  // we ignore the queueAndPlay's await to avoid blocking the UI
  if (genre.value!.song_count <= randomSongCount) {
    playbackService.queueAndPlay(songs.value, true)
  } else {
    playbackService.queueAndPlay(await songStore.fetchRandomForGenre(genre.value!, randomSongCount))
  }

  router.go('queue')
}

onMounted(() => (name.value = getNameFromRoute()))

watch(name, async () => name.value && await refresh())

// We can't really tell how/if the genres have been updated, so we just refresh the list
eventBus.on('SONGS_UPDATED', async () => genre.value && await refresh())
</script>
