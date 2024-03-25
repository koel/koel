<template>
  <section v-if="playlist" id="playlistWrapper">
    <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout" :disabled="loading">
      {{ playlist.name }}
      <ControlsToggle v-if="songs.length" v-model="showingControls" />

      <template #thumbnail>
        <PlaylistThumbnail :playlist="playlist">
          <ThumbnailStack v-if="!playlist.cover" :thumbnails="thumbnails" />
        </PlaylistThumbnail>
      </template>

      <template v-if="songs.length || playlist.is_collaborative" #meta>
        <CollaboratorsBadge v-if="collaborators.length" :collaborators="collaborators" />
        <span>{{ pluralize(songs, 'song') }}</span>
        <span>{{ duration }}</span>
        <a
          v-if="allowDownload"
          role="button"
          title="Download all songs in playlist"
          @click.prevent="download"
        >
          Download All
        </a>
      </template>

      <template #controls>
        <SongListControls
          v-if="!isPhone || showingControls"
          :config="controlsConfig"
          @delete-playlist="destroy"
          @filter="applyFilter"
          @play-all="playAll"
          @play-selected="playSelected"
          @refresh="fetchDetails(true)"
        />
      </template>
    </ScreenHeader>

    <SongListSkeleton v-show="loading" />
    <SongList
      v-if="!loading && songs.length"
      ref="songList"
      @sort="sort"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @scroll-breakpoint="onScrollBreakpoint"
      @reorder="onReorder"
    />

    <ScreenEmptyState v-if="!songs.length && !loading">
      <template #icon>
        <Icon :icon="faFile" />
      </template>

      <template v-if="playlist?.is_smart">
        No songs match the playlist's
        <a @click.prevent="editPlaylist">criteria</a>.
      </template>
      <template v-else>
        The playlist is currently empty.
        <span class="d-block secondary">
          Drag songs into its name in the sidebar or use the &quot;Add To…&quot; button to fill it up.
        </span>
      </template>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { faFile } from '@fortawesome/free-regular-svg-icons'
import { differenceBy } from 'lodash'
import { computed, reactive, ref, toRef, watch } from 'vue'
import { arrayify, eventBus, logger, pluralize } from '@/utils'
import { commonStore, playlistStore, songStore } from '@/stores'
import { downloadService, playlistCollaborationService } from '@/services'
import { usePlaylistManagement, useRouter, useSongList, useAuthorization, useSongListControls } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import CollaboratorsBadge from '@/components/playlist/PlaylistCollaboratorsBadge.vue'
import PlaylistThumbnail from '@/components/ui/PlaylistThumbnail.vue'

const { currentUser } = useAuthorization()
const { triggerNotFound, getRouteParam, onScreenActivated } = useRouter()

const playlistId = ref<string>()
const playlist = ref<Playlist>()
const loading = ref(false)

const {
  SongList,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  songs,
  songList,
  duration,
  thumbnails,
  selectedSongs,
  showingControls,
  isPhone,
  context,
  sortField,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onScrollBreakpoint,
  sort: baseSort,
  config: listConfig
} = useSongList(ref<Song[] | CollaborativeSong[]>([]), { type: 'Playlist' })

const { SongListControls, config: controlsConfig } = useSongListControls('Playlist')
const { removeSongsFromPlaylist } = usePlaylistManagement()

const allowDownload = toRef(commonStore.state, 'allows_download')

const destroy = () => eventBus.emit('PLAYLIST_DELETE', playlist.value!)
const download = () => downloadService.fromPlaylist(playlist.value!)
const editPlaylist = () => eventBus.emit('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist.value!)

const removeSelected = async () => await removeSongsFromPlaylist(playlist.value!, selectedSongs.value)
let collaborators = ref<PlaylistCollaborator[]>([])

const fetchDetails = async (refresh = false) => {
  if (loading.value) return

  try {
    [songs.value, collaborators.value] = await Promise.all([
      songStore.fetchForPlaylist(playlist.value!, refresh),
      playlist.value!.is_collaborative
        ? playlistCollaborationService.fetchCollaborators(playlist.value!)
        : Promise.resolve<PlaylistCollaborator[]>([])
    ])

    sortField.value ??= (playlist.value?.is_smart ? 'title' : 'position')
    sort(sortField.value, 'asc')
  } catch (e) {
    logger.error(e)
  } finally {
    loading.value = false
  }
}

const sort = (field: SongListSortField | null, order: SortOrder) => {
  listConfig.reorderable = field === 'position'

  if (field !== 'position') {
    return baseSort(field, order)
  }

  // To sort by position, we simply re-assign the songs array from the playlist, which maintains the original order.
  songs.value = playlist.value!.songs!
}

const onReorder = (target: Song, type: MoveType) => {
  playlistStore.moveSongsInPlaylist(playlist.value!, selectedSongs.value, target, type)
}

watch(playlistId, async id => {
  if (!id) return

  // sort field will be determined later by the playlist's type
  sortField.value = null

  playlist.value = playlistStore.byId(id)
  context.entity = playlist.value

  // reset this config value to its default to not cause rows to be mal-rendered
  listConfig.collaborative = false

  if (playlist.value) {
    await fetchDetails()
    listConfig.collaborative = playlist.value.is_collaborative
    listConfig.hasCustomSort = !playlist.value.is_smart
    controlsConfig.deletePlaylist = playlist.value.user_id === currentUser.value?.id
  } else {
    await triggerNotFound()
  }
})

onScreenActivated('Playlist', () => (playlistId.value = getRouteParam('id')!))

eventBus
  .on('PLAYLIST_UPDATED', async ({ id }) => id === playlistId.value && await fetchDetails())
  .on('PLAYLIST_COLLABORATOR_REMOVED', async ({ id }) => id === playlistId.value && await fetchDetails())
  .on('PLAYLIST_SONGS_REMOVED', async ({ id }, removed) => {
    if (id !== playlistId.value) return
    songs.value = differenceBy(songs.value, removed, 'id')
  })
</script>
