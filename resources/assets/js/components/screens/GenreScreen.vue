<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader v-if="genre">
        <template v-if="genre.name">
          <span class="font-thin">Genre:</span>
          {{ genre.name }}
        </template>
        <span v-else class="font-thin italic">No Genre</span>

        <template #thumbnail>
          <ThumbnailStack :thumbnails />
        </template>

        <template v-if="genre" #meta>
          <span>{{ pluralize(genre.song_count, 'song') }}</span>
          <span>{{ duration }}</span>
        </template>

        <template #controls>
          <SongListControls :config @play-all="playAll" @play-selected="playSelected">
            <Btn variant="ghost" @click="requestContextMenu">
              <Icon :icon="faEllipsis" fixed-width />
              <span class="sr-only">More Actions</span>
            </Btn>
          </SongListControls>
        </template>
      </ScreenHeader>
      <ScreenHeaderSkeleton v-else role="status" aria-busy="true" aria-label="Loading" />
    </template>

    <PlayableListSkeleton v-if="showSkeletons" class="-m-6" role="status" aria-busy="true" aria-label="Loading" />
    <SongList
      v-else
      ref="songList"
      class="-m-6"
      @sort="fetchWithSort"
      @press:enter="onPressEnter"
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
import { faEllipsis } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref, watch } from 'vue'
import { GuitarIcon } from 'lucide-vue-next'
import { pluralize, secondsToHumanReadable } from '@/utils/formatters'
import { eventBus } from '@/utils/eventBus'
import { defineAsyncComponent } from '@/utils/helpers'
import { genreStore } from '@/stores/genreStore'
import { playableStore } from '@/stores/playableStore'
import { useRouter } from '@/composables/useRouter'
import { usePageTitle } from '@/composables/usePageTitle'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { usePersistedSort } from '@/composables/usePersistedSort'
import { useContextMenu } from '@/composables/useContextMenu'
import { usePlayCollection } from '@/composables/usePlayCollection'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'
import ScreenHeaderSkeleton from '@/components/ui/ScreenHeaderSkeleton.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import Btn from '@/components/ui/form/Btn.vue'

const ContextMenu = defineAsyncComponent(() => import('@/components/genre/GenreContextMenu.vue'))

const songs = ref<Song[]>([])

const {
  PlayableList: SongList,
  ThumbnailStack,
  playableList: songList,
  thumbnails,
  onPressEnter,
  playSelected,
  sort: composableSort,
} = usePlayableList(songs, { type: 'Genre' }, { sortable: true, filterable: false, sortedOnServer: true })

const { PlayableListControls: SongListControls, config } = usePlayableListControls('Genre')
const { getRouteParam, isCurrentScreen, onRouteChanged } = useRouter()
const { openContextMenu } = useContextMenu()
const { playCollection } = usePlayCollection()
const { getSavedSort, saveSort } = usePersistedSort('genre', 'title')

let { sortField, sortOrder } = getSavedSort()

const id = ref<Genre['id'] | null>(null)
const genre = ref<Genre | null>(null)
const loading = ref(false)
const cursor = ref<string | null>('')

usePageTitle().useScreenTitle('Genre', () => (genre.value ? genre.value.name || 'No Genre' : null))

const moreSongsAvailable = computed(() => cursor.value !== null)
const showSkeletons = computed(() => loading.value && songs.value.length === 0)
const duration = computed(() => (genre.value ? secondsToHumanReadable(genre.value.length) : ''))

let songListVersion = 0

const fetch = async () => {
  if (!moreSongsAvailable.value || loading.value) {
    return
  }

  const requestedSongListVersion = songListVersion
  loading.value = true

  try {
    const fetched = await playableStore.paginateSongsByGenre(id.value!, {
      sort: sortField,
      order: sortOrder,
      cursor: cursor.value,
    })

    if (requestedSongListVersion === songListVersion) {
      cursor.value = fetched.nextCursor
      songs.value.push(...fetched.songs)
    }
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    loading.value = false
  }

  if (requestedSongListVersion !== songListVersion) {
    await refetchSongsFromStart()
  }
}

const refetchSongsFromStart = async () => {
  songListVersion++
  cursor.value = ''
  songs.value = []

  await fetch()
}

const fetchGenre = async () => {
  const requestedId = id.value!

  try {
    const fetchedGenre = await genreStore.fetchOne(requestedId)

    if (id.value === requestedId) {
      genre.value = fetchedGenre
    }
  } catch (error: unknown) {
    if (id.value === requestedId) {
      useErrorHandler('dialog').handleHttpError(error)
    }
  }
}

const refresh = async () => {
  genre.value = null

  await Promise.all([fetchGenre(), refetchSongsFromStart()])
}

const fetchWithSort = async (field: MaybeArray<PlayableListSortField>, order: SortOrder) => {
  sortField = field
  sortOrder = order

  saveSort(field, order)

  await refetchSongsFromStart()
}

const getIdFromRoute = () => getRouteParam('id') ?? null

onRouteChanged(route => {
  if (route.screen === 'Genre') {
    id.value = getIdFromRoute()
  }
})

const playAll = async (random = false) => {
  const currentGenre = genre.value

  if (!currentGenre) {
    return
  }

  await playCollection(() => playableStore.fetchSongsByGenre(currentGenre, random), {
    emptyMessage: 'The genre is empty.',
  })
}

const requestContextMenu = (event: MouseEvent) =>
  openContextMenu<'GENRE'>(ContextMenu, event, {
    genre: genre.value!,
  })

onMounted(() => {
  composableSort(sortField, sortOrder)

  if (isCurrentScreen('Genre')) {
    id.value = getIdFromRoute()
  }
})

watch(id, async () => id.value && (await refresh()))

// We can't really tell how/if the genres have been updated, so we just refresh the list
eventBus.on('SONGS_UPDATED', async () => genre.value && (await refresh()))
</script>
