<template>
  <ScreenBase v-if="playlistId">
    <template #header>
      <ScreenHeader
        v-if="playlist"
        :disabled="loading"
        :layout="allPlayables.length ? headerLayout : 'collapsed'"
      >
        {{ playlist.name }}
        <p v-if="playlist.description" class="text-base text-k-text-secondary font-light">
          {{ playlist.description }}
        </p>

        <template #thumbnail>
          <PlaylistThumbnail :playlist>
            <ThumbnailStack v-if="!playlist.cover" :thumbnails />
          </PlaylistThumbnail>
        </template>

        <template v-if="filteredPlayables.length || playlist.is_collaborative" #meta>
          <CollaboratorsBadge v-if="collaborators.length" :collaborators />
          <span>{{ pluralize(filteredPlayables, 'item') }}</span>
          <span>{{ duration }}</span>
        </template>

        <template #controls>
          <PlayableListControls
            :config="controlsConfig"
            @refresh="fetchDetails(true)"
            @play-all="playAll"
            @play-selected="playSelected"
          >
            <Btn gray @click="requestContextMenu">
              <Icon :icon="faEllipsis" fixed-width />
              <span class="sr-only">More Actions</span>
            </Btn>
          </PlayableListControls>
        </template>
      </ScreenHeader>
      <ScreenHeaderSkeleton v-else />
    </template>

    <PlayableListSkeleton v-if="loading" class="-m-6" />
    <template v-else>
      <PlayableList
        v-if="filteredPlayables.length"
        ref="playableList"
        class="-m-6"
        @reorder="onReorder"
        @sort="sort"
        @press:delete="removeSelected"
        @press:enter="onPressEnter"
        @swipe="onSwipe"
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
import { faEllipsis } from '@fortawesome/free-solid-svg-icons'
import { differenceBy } from 'lodash'
import { ref, watch } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { pluralize } from '@/utils/formatters'
import { playlistStore } from '@/stores/playlistStore'
import { playableStore } from '@/stores/playableStore'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import { defineAsyncComponent } from '@/utils/helpers'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlaylistContentManagement } from '@/composables/usePlaylistContentManagement'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { useContextMenu } from '@/composables/useContextMenu'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import CollaboratorsBadge from '@/components/playlist/PlaylistCollaboratorsBadge.vue'
import PlaylistThumbnail from '@/components/ui/PlaylistThumbnail.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenHeaderSkeleton from '@/components/ui/ScreenHeaderSkeleton.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'
import Btn from '@/components/ui/form/Btn.vue'

const ContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistContextMenu.vue'))

// Since this component is responsible for all playlists, we keep track of the state for each,
// so that filter and sort settings are preserved when switching between them.
// Playlists and content are (re)fetched (and cached) by the stores on demand, so we don't need to keep them in state.
interface PlaylistScreenState {
  filterKeywords: string
  sortField: MaybeArray<PlayableListSortField> | null
  sortOrder: SortOrder | null
}

const { triggerNotFound, getRouteParam, onScreenActivated, go, url } = useRouter()
const { openContextMenu } = useContextMenu()

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
const allPlayables = ref<Playable[]>([])
const collaborators = ref<PlaylistCollaborator[]>([])

const playlistId = ref<Playlist['id']>()
const playlist = ref<Playlist>()
const loading = ref(false)

const {
  PlayableList,
  ThumbnailStack,
  headerLayout,
  playables: filteredPlayables,
  playableList,
  duration,
  thumbnails,
  selectedPlayables,
  context,
  filterKeywords,
  onPressEnter,
  playAll,
  playSelected,
  onSwipe,
  sort: baseSort,
  config: listConfig,
} = usePlayableList(allPlayables, { type: 'Playlist' })

const { PlayableListControls, config: controlsConfig } = usePlayableListControls('Playlist')
const { removeFromPlaylist } = usePlaylistContentManagement()

watch(filterKeywords, keywords => {
  // keep track of the keywords in the state
  currentState.filterKeywords = keywords
})

const sort = (field: MaybeArray<PlayableListSortField> | null, order: SortOrder) => {
  listConfig.reorderable = field === 'position'

  currentState.sortField = field
  currentState.sortOrder = order

  // We always call the base sort function, which will handle the actual sorting logic.
  // For the 'position' field, which actually doesn't use the base sort function, we call it anyway
  // to properly keep track of sortField and sortOrder in useSongList, ensuring the UI reflects these correctly.
  baseSort(field, order)

  if (field === 'position') {
    // To sort by position, we simply re-assign the playable array from the playlist, which maintains the original order.
    allPlayables.value = playlist.value!.playables!
  }
}

const editPlaylist = () => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist.value!)

const removeSelected = async () => await removeFromPlaylist(playlist.value!, selectedPlayables.value)

const fetchDetails = async (refresh = false) => {
  if (loading.value) {
    return
  }

  try {
    loading.value = true

    ;[allPlayables.value, collaborators.value] = await Promise.all([
      playableStore.fetchForPlaylist(playlist.value!, refresh),
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

const onReorder = (target: Playable, placement: Placement) => {
  playlistStore.moveItemsInPlaylist(playlist.value!, selectedPlayables.value, target, placement)
}

watch(playlistId, async id => {
  if (!id) {
    return
  }

  playlist.value = playlistStore.byId(id)

  if (!playlist.value) {
    return triggerNotFound()
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

  currentState.sortField ??= (playlist.value?.is_smart ? 'title' : 'position')
  currentState.sortOrder ??= 'asc'

  sort(currentState.sortField, currentState.sortOrder)
})

onScreenActivated('Playlist', () => (playlistId.value = getRouteParam('id')!))

const requestContextMenu = (event: MouseEvent) => openContextMenu<'PLAYLIST'>(ContextMenu, event, {
  playlist: playlist.value!,
})

eventBus
  .on('PLAYLIST_UPDATED', async ({ id }) => id === playlistId.value && await fetchDetails())
  .on('PLAYLIST_COLLABORATOR_REMOVED', async ({ id }) => id === playlistId.value && await fetchDetails())
  .on('PLAYLIST_CONTENT_REMOVED', async ({ id }, removed) => {
    if (id === playlistId.value) {
      allPlayables.value = differenceBy(allPlayables.value, removed, 'id')
    }
  })
  .on('PLAYLIST_DELETED', async ({ id }) => id === playlistId.value && go(url('home')))
</script>

<style lang="postcss" scoped>
:deep(.meta) > *:not(:first-child)::before {
  content: '•';
  margin: 0 0.25em 0 0;
}
</style>
