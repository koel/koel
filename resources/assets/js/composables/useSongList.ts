import { differenceBy, orderBy, sampleSize, take, throttle } from 'lodash'
import isMobile from 'ismobilejs'
import { computed, provide, reactive, Ref, ref } from 'vue'
import { playbackService } from '@/services'
import { queueStore, songStore } from '@/stores'
import { eventBus, provideReadonly } from '@/utils'
import { useRouter } from '@/composables'

import {
  SelectedSongsKey,
  SongListConfigKey,
  SongListFilterKeywordsKey,
  SongListSortFieldKey,
  SongListSortOrderKey,
  SongsKey
} from '@/symbols'

import ControlsToggle from '@/components/ui/ScreenControlsToggle.vue'
import SongList from '@/components/song/SongList.vue'
import ThumbnailStack from '@/components/ui/ThumbnailStack.vue'

export const useSongList = (
  songs: Ref<Song[]>,
  config: Partial<SongListConfig> = { sortable: true, reorderable: false, collaborative: false }
) => {
  const filterKeywords = ref('')
  config = reactive(config)
  const { isCurrentScreen, go } = useRouter()

  const songList = ref<InstanceType<typeof SongList>>()

  const isPhone = isMobile.phone
  const selectedSongs = ref<Song[]>([])
  const showingControls = ref(false)
  const headerLayout = ref<ScreenHeaderLayout>('expanded')

  const onScrollBreakpoint = (direction: 'up' | 'down') => {
    headerLayout.value = direction === 'down' ? 'collapsed' : 'expanded'
  }

  const duration = computed(() => songStore.getFormattedLength(songs.value))

  const thumbnails = computed(() => {
    const songsWithCover = songs.value.filter(({ album_cover }) => album_cover)
    const sampleCovers = sampleSize(songsWithCover, 20).map(({ album_cover }) => album_cover)
    return take(Array.from(new Set(sampleCovers)), 4)
  })

  const getSongsToPlay = (): Song[] => songList.value!.getAllSongsWithSort()

  const playAll = (shuffle: boolean) => {
    playbackService.queueAndPlay(getSongsToPlay(), shuffle)
    go('queue')
  }

  const playSelected = (shuffle: boolean) => playbackService.queueAndPlay(selectedSongs.value, shuffle)

  const applyFilter = throttle((keywords: string) => (filterKeywords.value = keywords), 200)

  const onPressEnter = async (event: KeyboardEvent) => {
    if (selectedSongs.value.length === 1) {
      queueStore.queueIfNotQueued(selectedSongs.value[0])
      await playbackService.play(selectedSongs.value[0])
      return
    }

    //  • Only Enter: Queue songs to bottom
    //  • Shift+Enter: Queues song to top
    //  • Cmd/Ctrl+Enter: Queues song to bottom and play the first selected song
    //  • Cmd/Ctrl+Shift+Enter: Queue songs to top and play the first queued song
    event.shiftKey ? queueStore.queueToTop(selectedSongs.value) : queueStore.queue(selectedSongs.value)

    if (event.ctrlKey || event.metaKey) {
      await playbackService.play(selectedSongs.value[0])
    }

    go('queue')
  }

  const sortField = ref<SongListSortField | null>(((): SongListSortField | null => {
    if (!config.sortable) return null
    if (isCurrentScreen('Artist', 'Album')) return 'track'
    if (isCurrentScreen('Search.Songs', 'Queue', 'RecentlyPlayed')) return null
    return 'title'
  })())

  const sortOrder = ref<SortOrder>('asc')

  const sort = (by: SongListSortField | null = sortField.value, order: SortOrder = sortOrder.value) => {
    if (!config.sortable) return
    if (!by) return

    sortField.value = by
    sortOrder.value = order

    let sortFields: SongListSortField[] = [by]

    if (by === 'track') {
      sortFields = ['disc', 'track', 'title']
    } else if (by === 'album_name') {
      sortFields.push('artist_name', 'disc', 'track', 'title')
    } else if (by === 'artist_name') {
      sortFields.push('album_name', 'disc', 'track', 'title')
    }

    songs.value = orderBy(songs.value, sortFields, order)
  }

  eventBus.on('SONGS_DELETED', deletedSongs => (songs.value = differenceBy(songs.value, deletedSongs, 'id')))

  provideReadonly(SongsKey, songs, false)
  provideReadonly(SelectedSongsKey, selectedSongs, false)
  provideReadonly(SongListConfigKey, config)
  provideReadonly(SongListSortFieldKey, sortField)
  provideReadonly(SongListSortOrderKey, sortOrder)

  provide(SongListFilterKeywordsKey, filterKeywords)

  return {
    SongList,
    ControlsToggle,
    ThumbnailStack,
    songs,
    config,
    headerLayout,
    sortField,
    sortOrder,
    duration,
    thumbnails,
    songList,
    selectedSongs,
    showingControls,
    isPhone,
    onPressEnter,
    playAll,
    playSelected,
    applyFilter,
    onScrollBreakpoint,
    sort
  }
}
