<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :disabled="loading" :layout="songs.length ? headerLayout : 'collapsed'">
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
            <SongListControls
              v-if="totalSongCount"
              :config
              @play-all="playAll"
              @play-selected="playSelected"
            />
          </div>
        </template>
      </ScreenHeader>
    </template>

    <SongListSkeleton v-if="showSkeletons" class="-m-6" />
    <template v-else>
      <SongList
        v-if="songs?.length > 0"
        ref="songList"
        class="-m-6"
        @sort="sort"
        @swipe="onSwipe"
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
  headerLayout,
  thumbnails,
  playables: songs,
  playableList: songList,
  onPressEnter,
  playSelected,
  onSwipe,
} = usePlayableList(toRef(playableStore.state, 'playables'), { type: 'Songs' }, { filterable: false, sortable: true })

const { PlayableListControls: SongListControls, config } = usePlayableListControls('Songs')
const { go, url } = useRouter()

const loading = ref(false)
let sortField: MaybeArray<PlayableListSortField> = 'title' // @todo get from query string
let sortOrder: SortOrder = 'asc'

const page = ref<number | null>(1)
const moreSongsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && songs.value.length === 0)

const fetchSongs = async () => {
  if (!moreSongsAvailable.value || loading.value) {
    return
  }

  loading.value = true

  try {
    page.value = await playableStore.paginateSongs({
      sort: sortField,
      order: sortOrder,
      page: page.value!,
    })
  } catch (error: any) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const playAll = async (shuffle: boolean) => {
  if (shuffle) {
    await queueStore.fetchRandom()
  } else {
    await queueStore.fetchInOrder(sortField, sortOrder)
  }

  go(url('queue'))
  await playback().playFirstInQueue()
}

const sort = async (field: MaybeArray<PlayableListSortField>, order: SortOrder) => {
  page.value = 1
  playableStore.state.playables = []
  sortField = field
  sortOrder = order

  await fetchSongs()
}

onMounted(async () => await fetchSongs())
</script>

<style lang="postcss" scoped>
.collapsed .controls {
  @apply w-auto;
}
</style>
