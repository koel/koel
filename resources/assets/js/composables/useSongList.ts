import { differenceBy, orderBy, sampleSize, take, throttle } from 'lodash'
import isMobile from 'ismobilejs'
import { computed, provide, reactive, Ref, ref, watch } from 'vue'
import { playbackService } from '@/services'
import { queueStore, songStore } from '@/stores'
import { arrayify, eventBus, getPlayableProp, provideReadonly } from '@/utils'
import { useRouter } from '@/composables'

import {
  SelectedPlayablesKey,
  PlayableListConfigKey,
  PlayableListContextKey,
  SongListFilterKeywordsKey,
  PlayableListSortFieldKey,
  SongListSortOrderKey,
  PlayablesKey
} from '@/symbols'

import ControlsToggle from '@/components/ui/ScreenControlsToggle.vue'
import SongList from '@/components/song/SongList.vue'
import ThumbnailStack from '@/components/ui/ThumbnailStack.vue'

export const useSongList = (
  playables: Ref<Playable[]>,
  context: PlayableListContext = {},
  config: Partial<PlayableListConfig> = {
    sortable: true,
    reorderable: false,
    collaborative: false,
    hasCustomSort: false
  }
) => {
  const filterKeywords = ref('')
  config = reactive(config)
  context = reactive(context)
  const { isCurrentScreen, go } = useRouter()

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
    if (!config.sortable) return
    if (!by) return

    sortField.value = by
    sortOrder.value = order

    let sortFields: PlayableListSortField[] = arrayify(by)

    if (by === 'track') {
      sortFields = ['disc', 'track', 'title']
    } else if (sortFields.includes('album_name') && !sortFields.includes('disc')) {
      sortFields.push('artist_name', 'disc', 'track', 'title')
    } else if (sortFields.includes('artist_name') && !sortFields.includes('disc')) {
      sortFields.push('album_name', 'disc', 'track', 'title')
    }

    playables.value = orderBy(playables.value, sortFields, order)
  }

  eventBus.on('SONGS_DELETED', deletedSongs => (playables.value = differenceBy(playables.value, deletedSongs, 'id')))

  provideReadonly(PlayablesKey, playables, false)
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
