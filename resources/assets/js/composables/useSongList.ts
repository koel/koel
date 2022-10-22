import { differenceBy, orderBy, sampleSize, take } from 'lodash'
import isMobile from 'ismobilejs'
import { computed, reactive, Ref, ref } from 'vue'
import { playbackService } from '@/services'
import { queueStore, songStore } from '@/stores'
import { eventBus, provideReadonly, requireInjection } from '@/utils'

import {
  RouterKey,
  ScreenNameKey,
  SelectedSongsKey,
  SongListConfigKey,
  SongListSortFieldKey,
  SongListSortOrderKey,
  SongsKey
} from '@/symbols'

import ControlsToggle from '@/components/ui/ScreenControlsToggle.vue'
import SongList from '@/components/song/SongList.vue'
import SongListControls from '@/components/song/SongListControls.vue'
import ThumbnailStack from '@/components/ui/ThumbnailStack.vue'

export const useSongList = (songs: Ref<Song[]>, screen: ScreenName, config: Partial<SongListConfig> = {}) => {
  const router = requireInjection(RouterKey)

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
    const songsWithCover = songs.value.filter(song => song.album_cover)
    const sampleCovers = sampleSize(songsWithCover, 20).map(song => song.album_cover)
    return take(Array.from(new Set(sampleCovers)), 4)
  })

  const getSongsToPlay = (): Song[] => songList.value.getAllSongsWithSort()

  const playAll = (shuffle: boolean) => {
    playbackService.queueAndPlay(getSongsToPlay(), shuffle)
    router.go('queue')
  }

  const playSelected = (shuffle: boolean) => playbackService.queueAndPlay(selectedSongs.value, shuffle)

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

    router.go('queue')
  }

  const sortField = ref<SongListSortField | null>(((): SongListSortField | null => {
    if (screen === 'Album' || screen === 'Artist') return 'track'
    if (screen === 'Search.Songs') return null
    return config.sortable ? 'title' : null
  })())

  const sortOrder = ref<SortOrder>('asc')

  const sort = (by: SongListSortField | null = sortField.value, order: SortOrder = sortOrder.value) => {
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

  eventBus.on('SONGS_DELETED', (deletedSongs: Song[]) => {
    songs.value = differenceBy(songs.value, deletedSongs, 'id')
  })

  provideReadonly(ScreenNameKey, screen)
  provideReadonly(SongsKey, songs, false)
  provideReadonly(SelectedSongsKey, selectedSongs, false)
  provideReadonly(SongListConfigKey, reactive(config))
  provideReadonly(SongListSortFieldKey, sortField)
  provideReadonly(SongListSortOrderKey, sortOrder)

  return {
    SongList,
    SongListControls,
    ControlsToggle,
    ThumbnailStack,
    songs,
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
    onScrollBreakpoint,
    sort
  }
}
