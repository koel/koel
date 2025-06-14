<template>
  <ScreenBase v-if="playlistId">
    <template #header>
      <ScreenHeader
        v-if="playlist" :disabled="loading"
        :layout="allPlayables.length === 0 ? 'collapsed' : headerLayout"
      >
        {{ playlist.name }}
        <ControlsToggle v-if="filteredPlayables.length" v-model="showingControls" />

        <template #thumbnail>
          <PlaylistThumbnail :playlist="playlist">
            <ThumbnailStack v-if="!playlist.cover" :thumbnails="thumbnails" />
          </PlaylistThumbnail>
        </template>

        <template v-if="filteredPlayables.length || playlist.is_collaborative" #meta>
          <CollaboratorsBadge v-if="collaborators.length" :collaborators="collaborators" />
          <span>{{ pluralize(filteredPlayables, 'item') }}</span>
          <span>{{ duration }}</span>
          <a
            v-if="downloadable"
            role="button"
            title="Download all items in playlist"
            @click.prevent="download"
          >
            Download All
          </a>
        </template>

        <template #controls>
          <SongListControls
            v-if="!isPhone || showingControls"
            :config="controlsConfig"
            @refresh="fetchDetails(true)"
            @delete-playlist="destroy"
            @play-all="playAll"
            @play-selected="playSelected"
          />
        </template>
      </ScreenHeader>
      <ScreenHeaderSkeleton v-else />
    </template>

    <SongListSkeleton v-if="loading" class="-m-6" />
    <template v-else>
      <SongList
        v-if="filteredPlayables.length"
        ref="songList"
        class="-m-6"
        @reorder="onReorder"
        @sort="sort"
        @press:delete="removeSelected"
        @press:enter="onPressEnter"
        @scroll-breakpoint="onScrollBreakpoint"
      />

      <ScreenEmptyState v-else>
        <template #icon>
          <Icon :icon="faFile" />
        </template>

        <template v-if="playlist?.is_smart">
          <p>
            No songs match the playlist's
            <a class="inline" @click.prevent="editPlaylist">criteria</a>.
          </p>
        </template>
        <template v-else>
          The playlist is currently empty.
          <span class="block secondary">
            Drag content into its name in the sidebar or use the &quot;Add To…&quot; button to fill it up.
          </span>
        </template>
      </ScreenEmptyState>
    </template>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faFile } from '@fortawesome/free-regular-svg-icons'
import { differenceBy } from 'lodash'
import { ref, watch } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { pluralize } from '@/utils/formatters'
import { playlistStore } from '@/stores/playlistStore'
import { songStore } from '@/stores/songStore'
import { downloadService } from '@/services/downloadService'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import { useRouter } from '@/composables/useRouter'
import { useAuthorization } from '@/composables/useAuthorization'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlaylistManagement } from '@/composables/usePlaylistManagement'
import { useSongList } from '@/composables/useSongList'
import { useSongListControls } from '@/composables/useSongListControls'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import CollaboratorsBadge from '@/components/playlist/PlaylistCollaboratorsBadge.vue'
import PlaylistThumbnail from '@/components/ui/PlaylistThumbnail.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'

type PlaylistPlayable = Playable | CollaborativeSong

// Since this component is responsible for all playlists, we keep track of the state for each,
// so that filter and sort settings are preserved when switching between them.
// Playlists and content are (re)fetched (and cached) by the stores on demand, so we don't need to keep them in state.
interface PlaylistScreenState {
  filterKeywords: string
  sortField: MaybeArray<PlayableListSortField> | null
  sortOrder: SortOrder | null
}

const { currentUser } = useAuthorization()
const { triggerNotFound, getRouteParam, onScreenActivated } = useRouter()

const states = new Map<Playlist['id'], PlaylistScreenState>()

const blankState = (): PlaylistScreenState => {
  return {
    filterKeywords: '',
    sortField: null,
    sortOrder: 'asc',
  }
}

const getState = (id: Playlist['id']) => {
  if (!states.has(id)) {
    states.set(id, blankState())
  }

  return states.get(id)!
}

let currentState = blankState()
const allPlayables = ref<PlaylistPlayable[]>([])
const collaborators = ref<PlaylistCollaborator[]>([])

const playlistId = ref<Playlist['id']>()
const playlist = ref<Playlist>()
const loading = ref(false)

const {
  SongList,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  songs: filteredPlayables,
  songList,
  duration,
  downloadable,
  thumbnails,
  selectedPlayables,
  showingControls,
  isPhone,
  context,
  filterKeywords,
  onPressEnter,
  playAll,
  playSelected,
  onScrollBreakpoint,
  sort: baseSort,
  config: listConfig,
} = useSongList(allPlayables, { type: 'Playlist' })

const { SongListControls, config: controlsConfig } = useSongListControls('Playlist')
const { removeFromPlaylist } = usePlaylistManagement()

watch(filterKeywords, keywords => {
  // keep track of the keywords in the state
  currentState.filterKeywords = keywords
})

const sort = (field: MaybeArray<PlayableListSortField> | null, order: SortOrder) => {
  currentState.sortField = field
  currentState.sortOrder = order

  // We always call the base sort function, which will handle the actual sorting logic.
  // For the 'position' field, which actually doesn't use the base sort function, we call it anyway
  // to properly keep track of sortField and sortOrder in useSongList, ensuring the UI reflects these correctly.
  baseSort(field, order)

  if (field === 'position') {
    // To sort by position, we simply re-assign the songs array from the playlist, which maintains the original order.
    allPlayables.value = playlist.value!.playables!
  }
}

const destroy = () => eventBus.emit('PLAYLIST_DELETE', playlist.value!)
const download = () => downloadService.fromPlaylist(playlist.value!)
const editPlaylist = () => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist.value!)

const removeSelected = async () => await removeFromPlaylist(playlist.value!, selectedPlayables.value)

const fetchDetails = async (refresh = false) => {
  if (loading.value) {
    return
  }

  try {
    loading.value = true

    ;[allPlayables.value, collaborators.value] = await Promise.all([
      songStore.fetchForPlaylist(playlist.value!, refresh),
      playlist.value!.is_collaborative
        ? playlistCollaborationService.fetchCollaborators(playlist.value!)
        : Promise.resolve<PlaylistCollaborator[]>([]),
    ])
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const onReorder = (target: Playable, type: MoveType) => {
  playlistStore.moveItemsInPlaylist(playlist.value!, selectedPlayables.value, target, type)
}

watch(playlistId, async id => {
  if (!id) {
    return
  }

  playlist.value = playlistStore.byId(id)

  if (!playlist.value) {
    return await triggerNotFound()
  }

  context.entity = playlist.value

  // reset this config value to its default to not cause rows to be mal-rendered
  listConfig.collaborative = false

  // Make sure this value isn't shared among different playlists.
  selectedPlayables.value = []

  currentState = getState(id)

  // (re)apply the filter based on the current state's keywords
  filterKeywords.value = currentState.filterKeywords

  await fetchDetails()

  listConfig.reorderable = currentState.sortField === 'position'
  listConfig.collaborative = playlist.value.is_collaborative
  listConfig.hasCustomOrderSort = !playlist.value.is_smart
  controlsConfig.deletePlaylist = playlist.value.owner_id === currentUser.value?.id

  currentState.sortField ??= (playlist.value?.is_smart ? 'title' : 'position')
  currentState.sortOrder ??= 'asc'

  sort(currentState.sortField, currentState.sortOrder)
})

onScreenActivated('Playlist', () => (playlistId.value = getRouteParam('id')!))

eventBus
  .on('PLAYLIST_UPDATED', async ({ id }) => id === playlistId.value && await fetchDetails())
  .on('PLAYLIST_COLLABORATOR_REMOVED', async ({ id }) => id === playlistId.value && await fetchDetails())
  .on('PLAYLIST_CONTENT_REMOVED', async ({ id }, removed) => {
    if (id !== playlistId.value) {
      return
    }
    allPlayables.value = differenceBy(allPlayables.value, removed, 'id')
  })
</script>
