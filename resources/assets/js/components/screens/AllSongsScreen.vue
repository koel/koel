<template>
  <section id="songsWrapper">
    <ScreenHeader :layout="headerLayout">
      All Songs
      <ControlsToggle v-model="showingControls" />

      <template #thumbnail>
        <ThumbnailStack :thumbnails="thumbnails" />
      </template>

      <template v-if="totalSongCount" #meta>
        <span>{{ pluralize(totalSongCount, 'song') }}</span>
        <span>{{ totalDuration }}</span>
      </template>

      <template #controls>
        <div class="controls">
          <SongListControls
            v-if="totalSongCount && (!isPhone || showingControls)"
            :config="config"
            @play-all="playAll"
            @play-selected="playSelected"
          />
          <label class="own-songs-toggle text-secondary" v-if="isPlus">
            <CheckBox v-model="ownSongsOnly" />
            <span>Own songs only</span>
          </label>
        </div>
      </template>
    </ScreenHeader>

    <SongListSkeleton v-if="showSkeletons" />
    <template v-else>
      <SongList
        v-if="songs.length > 0"
        ref="songList"
        @sort="sort"
        @scroll-breakpoint="onScrollBreakpoint"
        @press:enter="onPressEnter"
        @scrolled-to-end="fetchSongs"
      />
      <ScreenEmptyState v-else>
        <template #icon>
          <Icon :icon="faVolumeOff" />
        </template>
        Your library is empty.
        <a
          role="button"
          class="d-block secondary"
          v-if="isPlus && ownSongsOnly"
          @click.prevent="showSongsFromOthers"
        >
          Show public songs from other users?
        </a>
      </ScreenEmptyState>
    </template>
  </section>
</template>

<script lang="ts" setup>
import { faVolumeOff } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRef, watch } from 'vue'
import { logger, pluralize, secondsToHumanReadable } from '@/utils'
import { commonStore, queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import { useMessageToaster, useKoelPlus, useRouter, useSongList, useSongListControls, useLocalStorage } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import CheckBox from '@/components/ui/CheckBox.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'

const totalSongCount = toRef(commonStore.state, 'song_count')
const totalDuration = computed(() => secondsToHumanReadable(commonStore.state.song_length))

const {
  SongList,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  thumbnails,
  songs,
  songList,
  duration,
  showingControls,
  isPhone,
  onPressEnter,
  playSelected,
  onScrollBreakpoint
} = useSongList(toRef(songStore.state, 'songs'), { sortable: true })

const { SongListControls, config } = useSongListControls('Songs')

const { toastError } = useMessageToaster()
const { go, onScreenActivated } = useRouter()
const { isPlus } = useKoelPlus()
const { get: lsGet, set: lsSet } = useLocalStorage()

let initialized = false
const loading = ref(false)
let sortField: SongListSortField = 'title' // @todo get from query string
let sortOrder: SortOrder = 'asc'

const page = ref<number | null>(1)
const moreSongsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && songs.value.length === 0)

const ownSongsOnly = ref(isPlus.value ? Boolean(lsGet('own-songs-only')) : false)

watch(ownSongsOnly, async value => {
  lsSet('own-songs-only', value)
  page.value = 1
  songStore.state.songs = []

  await fetchSongs()
})

const sort = async (field: SongListSortField, order: SortOrder) => {
  page.value = 1
  songStore.state.songs = []
  sortField = field
  sortOrder = order

  await fetchSongs()
}

const showSongsFromOthers = async () => {
  ownSongsOnly.value = false
  await fetchSongs()
}

const fetchSongs = async () => {
  if (!moreSongsAvailable.value || loading.value) return

  loading.value = true

  try {
    page.value = await songStore.paginate({
      sort: sortField,
      order: sortOrder,
      page: page.value!,
      own_songs_only: ownSongsOnly.value
    })
  } catch (error) {
    toastError('Failed to load songs.')
    logger.error(error)
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

  go('queue')
  await playbackService.playFirstInQueue()
}

onScreenActivated('Songs', async () => {
  if (!initialized) {
    initialized = true
    await fetchSongs()
  }
})
</script>

<style lang="scss" scoped>
.controls {
  width: 100%;
  min-height: 32px; // prevent shrinking causing the jumping effect
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;

  .collapsed & {
    width: auto;
  }

  .own-songs-toggle {
    display: inline-flex;
    align-items: center;
    font-size: 1rem;
  }
}
</style>
