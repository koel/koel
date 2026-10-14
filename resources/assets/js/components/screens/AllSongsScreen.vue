<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :disabled="loading" :layout="songs.length ? 'expanded' : 'collapsed'">
        All Songs

        <template #thumbnail>
          <ThumbnailStack :thumbnails />
        </template>

        <template v-if="totalSongCount" #meta>
          <span>{{ pluralize(totalSongCount, 'song') }}</span>
          <span>{{ totalDuration }}</span>
        </template>

        <template #controls>
          <div class="controls w-full flex justify-between items-center gap-4">
            <SongListControls v-if="totalSongCount" :config @play-all="playAll" @play-selected="playSelected" />
          </div>
        </template>
      </ScreenHeader>
    </template>

    <SongListSkeleton v-if="showSkeletons" class="-m-6" role="status" aria-busy="true" aria-label="Loading" />
    <template v-else>
      <SongList
        v-if="songs?.length > 0"
        ref="songList"
        class="-m-6"
        @sort="sort"
        @press:enter="onPressEnter"
        @scrolled-to-end="fetchSongs"
      />
      <ScreenEmptyState v-else>
        <template #icon>
          <Icon :icon="faVolumeOff" />
        </template>
        Your library is empty.
      </ScreenEmptyState>
    </template>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faVolumeOff } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref, toRef } from 'vue'
import { pluralize, secondsToHumanReadable } from '@/utils/formatters'
import { commonStore } from '@/stores/commonStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { usePersistedSort } from '@/composables/usePersistedSort'
import { playback } from '@/services/playbackManager'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import SongListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const totalSongCount = toRef(commonStore.state, 'song_count')
const totalDuration = computed(() => secondsToHumanReadable(commonStore.state.song_length))

const {
  PlayableList: SongList,
  ThumbnailStack,
  thumbnails,
  playables: songs,
  playableList: songList,
  onPressEnter,
  playSelected,
  sort: composableSort,
} = usePlayableList(
  toRef(playableStore.state, 'playables'),
  { type: 'Songs' },
  { filterable: false, sortable: true, sortedOnServer: true },
)

const { PlayableListControls: SongListControls, config } = usePlayableListControls('Songs')
const { go, url } = useRouter()
const { getSavedSort, saveSort } = usePersistedSort('all-songs', 'title')

const loading = ref(false)
let { sortField, sortOrder } = getSavedSort()

const cursor = ref<string | null>('')
const moreSongsAvailable = computed(() => cursor.value !== null)
const showSkeletons = computed(() => loading.value && songs.value.length === 0)

let listVersion = 0

const fetchSongs = async () => {
  if (!moreSongsAvailable.value || loading.value) {
    return
  }

  const requestedListVersion = listVersion
  loading.value = true

  try {
    cursor.value = await playableStore.paginateSongs({
      sort: sortField,
      order: sortOrder,
      cursor: cursor.value,
    })
  } catch (error: any) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }

  if (requestedListVersion !== listVersion) {
    await refetchFromStart()
  }
}

const refetchFromStart = async () => {
  listVersion++
  cursor.value = ''
  playableStore.state.playables = []

  await fetchSongs()
}

const playAll = async (shuffle: boolean) => {
  if (shuffle) {
    await queueStore.fetchRandom()
  } else {
    await queueStore.fetchInOrder(Array.isArray(sortField) ? sortField[0] : sortField, sortOrder)
  }

  go(url('queue'))
  await playback().playFirstInQueue()
}

const sort = async (field: MaybeArray<PlayableListSortField>, order: SortOrder) => {
  sortField = field
  sortOrder = order

  saveSort(field, order)

  await refetchFromStart()
}

onMounted(async () => {
  composableSort(sortField, sortOrder)
  await fetchSongs()
})
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.collapsed .controls {
  @apply w-auto;
}
</style>
