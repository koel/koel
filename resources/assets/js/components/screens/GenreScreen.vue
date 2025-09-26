<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader v-if="genre" :layout="headerLayout">
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
            <Btn gray @click="requestContextMenu">
              <Icon :icon="faEllipsis" fixed-width />
              <span class="sr-only">More Actions</span>
            </Btn>
          </SongListControls>
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
      @swipe="onSwipe"
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
import { playback } from '@/services/playbackManager'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { useContextMenu } from '@/composables/useContextMenu'

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
  headerLayout,
  playableList: songList,
  thumbnails,
  onPressEnter,
  playSelected,
  onSwipe,
} = usePlayableList(songs, { type: 'Genre' }, { sortable: true, filterable: false })

const { PlayableListControls: SongListControls, config } = usePlayableListControls('Genre')
const { getRouteParam, isCurrentScreen, go, onRouteChanged, url } = useRouter()
const { openContextMenu } = useContextMenu()

let sortField: MaybeArray<PlayableListSortField> = 'title'
let sortOrder: SortOrder = 'asc'

const id = ref<Genre['id'] | null>(null)
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
      playableStore.paginateSongsByGenre(id.value!, {
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

const playAll = async (shuffle = false) => {
  if (!genre.value) {
    return
  }

  go(url('queue'))

  if (shuffle) {
    await playback().queueAndPlay(await playableStore.fetchSongsByGenre(genre.value!, true))
  } else {
    await playback().queueAndPlay(await playableStore.fetchSongsByGenre(genre.value!, false))
  }
}

const requestContextMenu = (event: MouseEvent) => openContextMenu<'GENRE'>(ContextMenu, event, {
  genre: genre.value!,
})

onMounted(() => {
  if (isCurrentScreen('Genre')) {
    id.value = getIdFromRoute()
  }
})

watch(id, async () => id.value && await refresh())

// We can't really tell how/if the genres have been updated, so we just refresh the list
eventBus.on('SONGS_UPDATED', async () => genre.value && await refresh())
</script>
