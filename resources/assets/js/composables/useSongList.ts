import { differenceBy, orderBy, sampleSize, take, throttle } from 'lodash'
import isMobile from 'ismobilejs'
import { computed, provide, reactive, Ref, ref } from 'vue'
import { playbackService } from '@/services'
import { queueStore, songStore } from '@/stores'
import { arrayify, eventBus, getPlayableProp, provideReadonly } from '@/utils'
import { useFuzzySearch, useRouter } from '@/composables'

import {
  PlayableListConfigKey,
  PlayableListContextKey,
  PlayableListSortFieldKey,
  PlayablesKey,
  SelectedPlayablesKey,
  SongListFilterKeywordsKey,
  SongListSortOrderKey
} from '@/symbols'

import ControlsToggle from '@/components/ui/ScreenControlsToggle.vue'
import SongList from '@/components/song/SongList.vue'
import ThumbnailStack from '@/components/ui/ThumbnailStack.vue'

export const useSongList = (
  playables: Ref<Playable[]>,
  context: PlayableListContext = {},
  config: Partial<PlayableListConfig> = {
    filterable: true,
    sortable: true,
    reorderable: false,
    collaborative: false,
    hasCustomOrderSort: false
  }
) => {
  const filterKeywords = ref('')
  config = reactive(config)
  context = reactive(context)

  const { isCurrentScreen, go } = useRouter()

  const fuzzy = config.filterable ? useFuzzySearch(playables, [
      'title',
      'artist_name',
      'album_name',
      'podcast_title',
      'podcast_author',
      'episode_description'
    ]) : null

  const songList = ref<InstanceType<typeof SongList>>()

  const isPhone = isMobile.phone
  const selectedPlayables = ref<Playable[]>([])
  const showingControls = ref(false)
  const headerLayout = ref<ScreenHeaderLayout>('expanded')

  const onScrollBreakpoint = (direction: 'up' | 'down') => {
    headerLayout.value = direction === 'down' ? 'collapsed' : 'expanded'
  }

  const duration = computed(() => songStore.getFormattedLength(playables.value))

  const thumbnails = computed(() => {
    const playablesWithCover = playables.value.filter(p => getPlayableProp<string>(p, 'album_cover', 'episode_image'))

    const sampleCovers = sampleSize(playablesWithCover, 20)
      .map(p => getPlayableProp<string>(p, 'album_cover', 'episode_image'))

    return take(Array.from(new Set(sampleCovers)), 4)
  })

  const getPlayablesToPlay = () => songList.value!.getAllPlayablesWithSort()

  const playAll = (shuffle: boolean) => {
    playbackService.queueAndPlay(getPlayablesToPlay(), shuffle)
    go('queue')
  }

  const playSelected = (shuffle: boolean) => playbackService.queueAndPlay(selectedPlayables.value, shuffle)

  const applyFilter = throttle((keywords: string) => (filterKeywords.value = keywords), 200)

  const filteredPlayables = computed(() => {
    if (!fuzzy) return playables.value

    return sortField.value
      ? orderBy(fuzzy.search(filterKeywords.value), extendedSortFields.value!, sortOrder.value)
      : fuzzy.search(filterKeywords.value)
  })

  /**
   * Extends the sort fields based on the current field(s) to cater to relevant fields.
   * For example, sorting by track should take into account the disc number and the title.
   * Similarly, sorting by album name should also include the artist name, disc number, track number, and title, etc.
   */
  const extendedSortFields = computed(() => {
    if (!sortField.value) return null

    let extended: PlayableListSortField[] = arrayify(sortField.value)

    if (sortField.value === 'track') {
      extended = ['disc', 'track', 'title']
    } else if (sortField.value.includes('album_name') && !sortField.value.includes('disc')) {
      extended.push('artist_name', 'disc', 'track', 'title')
    } else if (sortField.value.includes('artist_name') && !sortField.value.includes('disc')) {
      extended.push('album_name', 'disc', 'track', 'title')
    }

    return extended
  })

  const onPressEnter = async (event: KeyboardEvent) => {
    if (selectedPlayables.value.length === 1) {
      await playbackService.play(selectedPlayables.value[0])
      return
    }

    //  • Only Enter: Queue to bottom
    //  • Shift+Enter: Queues to top
    //  • Cmd/Ctrl+Enter: Queues to bottom and play the first selected item
    //  • Cmd/Ctrl+Shift+Enter: Queue to top and play the first queued item
    event.shiftKey ? queueStore.queueToTop(selectedPlayables.value) : queueStore.queue(selectedPlayables.value)

    if (event.ctrlKey || event.metaKey) {
      await playbackService.play(selectedPlayables.value[0])
    }

    go('queue')
  }

  const sortField = ref<MaybeArray<PlayableListSortField> | null>((() => {
    if (!config.sortable) return null
    if (isCurrentScreen('Artist', 'Album')) return 'track'
    if (isCurrentScreen('Search.Songs', 'Queue', 'RecentlyPlayed')) return null
    return 'title'
  })())

  const sortOrder = ref<SortOrder>('asc')

  const sort = (by: MaybeArray<PlayableListSortField> | null = sortField.value, order: SortOrder = sortOrder.value) => {
    // To sort a song list, we simply set the sort field and order.
    // The list will be sorted automatically by the computed property.
    sortField.value = by
    sortOrder.value = order
  }

  eventBus.on('SONGS_DELETED', deletedSongs => (playables.value = differenceBy(playables.value, deletedSongs, 'id')))

  provideReadonly(PlayablesKey, filteredPlayables, false)
  provideReadonly(SelectedPlayablesKey, selectedPlayables, false)
  provideReadonly(PlayableListConfigKey, config)
  provideReadonly(PlayableListContextKey, context)
  provideReadonly(PlayableListSortFieldKey, sortField)
  provideReadonly(SongListSortOrderKey, sortOrder)

  provide(SongListFilterKeywordsKey, filterKeywords)

  return {
    SongList,
    ControlsToggle,
    ThumbnailStack,
    songs: playables,
    config,
    context,
    headerLayout,
    sortField,
    sortOrder,
    duration,
    thumbnails,
    songList,
    selectedPlayables,
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
