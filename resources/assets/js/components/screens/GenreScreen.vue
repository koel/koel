<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader v-if="genre" :layout="headerLayout">
        Genre: <span class="text-thin">{{ genre.name }}</span>
        <ControlsToggle v-if="displayedSongs.length" v-model="showingControls" />

        <template #thumbnail>
          <ThumbnailStack :thumbnails />
        </template>

        <template v-if="genre" #meta>
          <span>{{ pluralize(genre.song_count, 'song') }}</span>
          <span>{{ duration }}</span>
        </template>

        <template #controls>
          <SongListControls
            v-if="!isPhone || showingControls"
            :config
            @play-all="playAll"
            @play-selected="playSelected"
          />
        </template>
      </ScreenHeader>
      <ScreenHeaderSkeleton v-else />
    </template>

    <PlayableListSkeleton v-if="showSkeletons" class="-m-6" />
    <SongList
      v-else
      ref="songList"
      class="-m-6"
      @sort="fetchWithSort"
      @press:enter="onPressEnter"
      @scroll-breakpoint="onScrollBreakpoint"
      @scrolled-to-end="fetch"
    />

    <ScreenEmptyState v-if="!songs.length && !loading">
      <template #icon>
        <GuitarIcon :size="96" />
      </template>

      No songs in this genre.
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, watch } from 'vue'
import { GuitarIcon } from 'lucide-vue-next'
import { pluralize, secondsToHumanReadable } from '@/utils/formatters'
import { eventBus } from '@/utils/eventBus'
import { genreStore } from '@/stores/genreStore'
import { playableStore } from '@/stores/playableStore'
import { playback } from '@/services/playbackManager'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import PlayableListSkeleton from '@/components/ui/skeletons/PlayableListSkeleton.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const songs = ref<Song[]>([])

const {
  PlayableList: SongList,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  playables: displayedSongs,
  playableList: songList,
  thumbnails,
  showingControls,
  isPhone,
  onPressEnter,
  playSelected,
  onScrollBreakpoint,
} = usePlayableList(songs, { type: 'Genre' }, { sortable: true, filterable: false })

const { PlayableListControls: SongListControls, config } = usePlayableListControls('Genre')

const { getRouteParam, isCurrentScreen, go, onRouteChanged, url } = useRouter()

let sortField: MaybeArray<PlayableListSortField> = 'title'
let sortOrder: SortOrder = 'asc'

const randomSongCount = 500
const id = ref<string | null>(null)
const genre = ref<Genre | null>(null)
const loading = ref(false)
const page = ref<number | null>(1)

const moreSongsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && songs.value.length === 0)
const duration = computed(() => genre.value ? secondsToHumanReadable(genre.value.length) : '')

const fetch = async () => {
  if (!moreSongsAvailable.value || loading.value) {
    return
  }

  loading.value = true

  try {
    let fetched: { songs: Song[], nextPage: number | null }

    [genre.value, fetched] = await Promise.all([
      genreStore.fetchOne(id.value!),
      playableStore.paginateSongsForGenre(id.value!, {
        sort: sortField,
        order: sortOrder,
        page: page.value!,
      }),
    ])

    page.value = fetched.nextPage
    songs.value.push(...fetched.songs)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
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

const fetchWithSort = async (field: MaybeArray<PlayableListSortField>, order: SortOrder) => {
  page.value = 1
  songs.value = []
  sortField = field
  sortOrder = order

  await fetch()
}

const getIdFromRoute = () => getRouteParam('id') ?? null

onRouteChanged(route => {
  if (route.screen === 'Genre') {
    id.value = getIdFromRoute()
  }
})

const playAll = async () => {
  if (!genre.value) {
    return
  }

  // we ignore the queueAndPlay's await to avoid blocking the UI
  if (genre.value!.song_count <= randomSongCount) {
    playback().queueAndPlay(songs.value, true)
  } else {
    playback().queueAndPlay(await playableStore.fetchRandomSongsByGenre(genre.value!, randomSongCount))
  }

  go(url('queue'))
}

onMounted(() => {
  if (isCurrentScreen('Genre')) {
    id.value = getIdFromRoute()
  }
})

watch(id, async () => id.value && await refresh())

// We can't really tell how/if the genres have been updated, so we just refresh the list
eventBus.on('SONGS_UPDATED', async () => genre.value && await refresh())
</script>
