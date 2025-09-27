import { differenceBy, orderBy, take, throttle } from 'lodash'
import type { Ref } from 'vue'
import { computed, provide, reactive, ref } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { arrayify, defineAsyncComponent, getPlayableProp, provideReadonly } from '@/utils/helpers'
import { eventBus } from '@/utils/eventBus'
import { useFuzzySearch } from '@/composables/useFuzzySearch'
import { useRouter } from '@/composables/useRouter'
import { playback } from '@/services/playbackManager'

import {
  FilteredPlayablesKey,
  FilterKeywordsKey,
  PlayableListConfigKey,
  PlayableListContextKey,
  PlayableListSortFieldKey,
  PlayableListSortOrderKey,
  PlayablesKey,
  SelectedPlayablesKey,
} from '@/symbols'

const PlayableList = defineAsyncComponent(() => import('@/components/playable/playable-list/PlayableList.vue'))
const ThumbnailStack = defineAsyncComponent(() => import('@/components/ui/ThumbnailStack.vue'))

export const usePlayableList = (
  playables: Ref<Playable[]>,
  context: PlayableListContext = {},
  config: Partial<PlayableListConfig> = {},
) => {
  const defaultConfig: PlayableListConfig = {
    filterable: true,
    sortable: true,
    reorderable: false,
    collaborative: false,
    hasCustomOrderSort: false,
    hasHeader: true,
  }

  config = reactive({ ...defaultConfig, ...config })
  context = reactive(context)

  const filterKeywords = ref('')

  const { isCurrentScreen, go, url } = useRouter()

  const fuzzy = config.filterable
    ? useFuzzySearch(
        playables,
        [
          'title',
          'artist_name',
          'album_name',
          'podcast_title',
          'podcast_author',
          'episode_description',
        ],
      )
    : null

  const playableList = ref<InstanceType<typeof PlayableList>>()

  const selectedPlayables = ref<Playable[]>([])
  const headerLayout = ref<ScreenHeaderLayout>('expanded')

  const sortField = ref<MaybeArray<PlayableListSortField> | null>((() => {
    if (!config.sortable) {
      return null
    }

    if (isCurrentScreen('Artist', 'Album')) {
      return 'track'
    }

    if (isCurrentScreen('Search.Playables', 'Queue', 'RecentlyPlayed')) {
      return null
    }

    return 'title'
  })())

  /**
   * Extends the sort fields based on the current field(s) to cater to relevant fields.
   * For example, sorting by track should take into account the disc number and the title.
   * Similarly, sorting by album name should also include the artist name, disc number, track number, and title, etc.
   */
  const extendedSortFields = computed(() => {
    if (!sortField.value) {
      return null
    }

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

  const sortOrder = ref<SortOrder>('asc')

  const onSwipe = (direction: 'up' | 'down') => {
    headerLayout.value = direction === 'down' ? 'collapsed' : 'expanded'
  }

  const duration = computed(() => playableStore.getFormattedLength(playables.value))

  const downloadable = computed(() => {
    if (!commonStore.state.allows_download) {
      return false
    }

    if (playables.value.length === 0) {
      return false
    }

    return playables.value.length === 1 || commonStore.state.supports_batch_downloading
  })

  const thumbnails = computed(() => {
    const playablesWithCover = playables.value.filter(p => getPlayableProp(p, 'album_cover', 'episode_image'))

    const sampleCovers = playablesWithCover.slice(0, 100)
      .map(p => getPlayableProp(p, 'album_cover', 'episode_image'))

    return take(Array.from(new Set(sampleCovers)), 4)
  })

  const getPlayablesToPlay = () => playableList.value!.getAllPlayablesWithSort()

  const playAll = (shuffle: boolean) => {
    playback().queueAndPlay(getPlayablesToPlay(), shuffle)
    go(url('queue'))
  }

  const playSelected = (shuffle: boolean) => playback().queueAndPlay(selectedPlayables.value, shuffle)

  const applyFilter = throttle((keywords: string) => (filterKeywords.value = keywords), 200)

  const filteredPlayables = computed(() => {
    // This manually accesses playables.value, forcing Vue to properly track playables.value changes and re-compute.
    const items = playables.value
    const filtered = fuzzy && filterKeywords.value ? fuzzy.search(filterKeywords.value) : items

    const collectSortFields = () => {
      if (!sortField.value) {
        return null
      }

      const fields = extendedSortFields.value!

      if (
        fields[0] === 'disc'
        && fields.length > 1
        && new Set(filtered.map(p => (p as Song).disc ?? null)).size === 1
      ) {
        // If we're sorting by disc and there's only one disc, we remove `disc` from the sort fields.
        // Otherwise, the tracks will be sorted by disc number first, and since there's only one disc,
        // the track order will remain the same through alternating between asc and desc.
        fields.shift()
      }

      return fields
    }

    const sortFields = collectSortFields()

    return sortFields ? orderBy(filtered, sortFields, sortOrder.value) : filtered
  })

  const onPressEnter = async (event: KeyboardEvent) => {
    if (selectedPlayables.value.length === 1) {
      await playback().play(selectedPlayables.value[0])
      return
    }

    //  • Only Enter: Queue to bottom
    //  • Shift+Enter: Queues to top
    //  • Cmd/Ctrl+Enter: Queues to bottom and play the first selected item
    //  • Cmd/Ctrl+Shift+Enter: Queue to top and play the first queued item
    event.shiftKey ? queueStore.queueToTop(selectedPlayables.value) : queueStore.queue(selectedPlayables.value)

    if (event.ctrlKey || event.metaKey) {
      await playback().play(selectedPlayables.value[0])
    }

    go('queue')
  }

  const sort = (by: MaybeArray<PlayableListSortField> | null = sortField.value, order: SortOrder = sortOrder.value) => {
    // To sort a playable list, we simply set the sort field and order.
    // The list will be sorted automatically by the computed property.
    sortField.value = by
    sortOrder.value = order
  }

  eventBus.on('SONGS_DELETED', deletedSongs => (playables.value = differenceBy(playables.value, deletedSongs, 'id')))

  provideReadonly(PlayablesKey, playables, false)
  provideReadonly(FilteredPlayablesKey, filteredPlayables, false)
  provideReadonly(SelectedPlayablesKey, selectedPlayables, false)
  provideReadonly(PlayableListConfigKey, config)
  provideReadonly(PlayableListContextKey, context)
  provideReadonly(PlayableListSortFieldKey, sortField)
  provideReadonly(PlayableListSortOrderKey, sortOrder)

  provide(FilterKeywordsKey, filterKeywords)

  return {
    PlayableList,
    ThumbnailStack,
    playables,
    filteredPlayables,
    config,
    context,
    downloadable,
    headerLayout,
    sortField,
    sortOrder,
    duration,
    thumbnails,
    playableList,
    selectedPlayables,
    filterKeywords,
    onPressEnter,
    playAll,
    playSelected,
    applyFilter,
    onSwipe,
    sort,
  }
}
