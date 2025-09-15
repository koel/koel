<template>
  <ContextMenu ref="base" data-testid="playable-context-menu" extra-class="playable-menu">
    <template v-if="onlyOneSelected">
      <li @click.stop.prevent="doPlayback">
        <span v-if="firstSongPlaying">Pause</span>
        <span v-else>Play</span>
      </li>
      <li class="separator" />
      <template v-if="isSong(playables[0])">
        <li @click="viewAlbum(playables[0])">Go to Album</li>
        <li @click="viewArtist(playables[0])">Go to Artist</li>
      </template>
      <template v-else>
        <li @click="viewPodcast(playables[0] as Episode)">Go to Podcast</li>
        <li @click="viewEpisode(playables[0] as Episode)">See Description</li>
        <li v-if="(playables[0] as Episode).episode_link" @click="visitEpisodeWebpage(playables[0] as Episode)">
          Visit Webpage
        </li>
      </template>
    </template>
    <li class="has-sub">
      Add To
      <ul class="submenu menu-add-to context-menu">
        <template v-if="queue.length">
          <li v-if="currentSong" @click="queueAfterCurrent">After Current</li>
          <li @click="queueToBottom">Bottom of Queue</li>
          <li @click="queueToTop">Top of Queue</li>
        </template>
        <li v-else @click="queueToBottom">Queue</li>
        <template v-if="!isFavoritesScreen">
          <li class="separator" />
          <li @click="addToFavorites">Favorites</li>
        </template>
        <li v-if="normalPlaylists.length" class="separator" />
        <template class="block">
          <ul v-if="normalPlaylists.length" v-koel-overflow-fade class="relative max-h-48 overflow-y-auto">
            <li v-for="p in normalPlaylists" :key="p.id" @click="addToExistingPlaylist(p)">{{ p.name }}</li>
          </ul>
        </template>
        <li class="separator" />
        <li @click="addToNewPlaylist">New Playlist…</li>
      </ul>
    </li>

    <template v-if="isQueueScreen">
      <li class="separator" />
      <li @click="removeFromQueue">Remove from Queue</li>
      <li class="separator" />
    </template>

    <template v-if="isFavoritesScreen">
      <li class="separator" />
      <li @click="removeFromFavorites">Remove from Favorites</li>
    </template>

    <template v-if="visibilityActions.length">
      <li class="separator" />
      <li v-for="action in visibilityActions" :key="action.label" @click="action.handler">{{ action.label }}</li>
    </template>

    <li v-if="allowEdit" @click="openEditForm">Edit…</li>
    <li v-if="downloadable" @click="download">Download</li>
    <li v-if="onlyOneSelected && canBeShared" @click="copyUrl">Copy Shareable URL</li>
    <li v-if="onlyOneSelected" @click="showEmbedModal">Embed…</li>

    <template v-if="canBeRemovedFromPlaylist">
      <li class="separator" />
      <li @click="removePlayablesFromPlaylist">Remove from Playlist</li>
    </template>

    <template v-if="allowEdit">
      <li class="separator" />
      <li @click="deleteFromFilesystem">Delete from Filesystem</li>
    </template>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { pluralize } from '@/utils/formatters'
import { eventBus } from '@/utils/eventBus'
import { arrayify, copyText } from '@/utils/helpers'
import { getPlayableCollectionContentType, isSong } from '@/utils/typeGuards'
import { commonStore } from '@/stores/commonStore'
import { playlistStore } from '@/stores/playlistStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useRouter } from '@/composables/useRouter'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { usePlaylistContentManagement } from '@/composables/usePlaylistContentManagement'
import { usePlayableMenuMethods } from '@/composables/usePlayableMenuMethods'
import { usePolicies } from '@/composables/usePolicies'
import { useContextMenu } from '@/composables/useContextMenu'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { playback } from '@/services/playbackManager'

const { toastSuccess, toastError, toastWarning } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, getRouteParam, isCurrentScreen, url } = useRouter()
const { base, ContextMenu, open, close, trigger } = useContextMenu()
const { removeFromPlaylist } = usePlaylistContentManagement()
const { isPlus } = useKoelPlus()

const playables = ref<Playable[]>([])

const {
  queueAfterCurrent,
  queueToBottom,
  queueToTop,
  addToFavorites,
  addToExistingPlaylist,
  removeFromFavorites,
  removeFromQueue,
  addToNewPlaylist,
} = usePlayableMenuMethods(playables, close)

const playlists = toRef(playlistStore.state, 'playlists')

const downloadable = computed(() => {
  if (!commonStore.state.allows_download) {
    return false
  }

  // If multiple playables are selected, make sure zip extension is available on the server
  return playables.value.length === 1 || commonStore.state.supports_batch_downloading
})

const queue = toRef(queueStore.state, 'playables')
const currentSong = toRef(queueStore, 'current')

const { currentUserCan } = usePolicies()

const contentType = computed(() => getPlayableCollectionContentType(playables.value))
const allowEdit = computed(() => contentType.value === 'songs' && currentUserCan.editSong(playables.value as Song[]))
const onlyOneSelected = computed(() => playables.value.length === 1)
const firstSongPlaying = computed(() => playables.value.length ? playables.value[0].playback_state === 'Playing' : false)
const normalPlaylists = computed(() => playlists.value.filter(({ is_smart }) => !is_smart))
const canBeShared = computed(() => !isPlus.value || (isSong(playables.value[0]) && playables.value[0].is_public))

const makePublic = () => trigger(async () => {
  if (contentType.value !== 'songs') {
    throw new Error('Only songs can be marked as public or private')
  }

  await playableStore.publicizeSongs(playables.value as Song[])
  toastSuccess(`Unmarked ${pluralize(playables.value, 'song')} as private.`)
})

const makePrivate = () => trigger(async () => {
  if (contentType.value !== 'songs') {
    throw new Error('Only songs can be marked as public or private')
  }

  const privatizedIds = await playableStore.privatizeSongs(playables.value as Song[])

  if (!privatizedIds.length) {
    toastError('Songs cannot be marked as private if they’re part of a collaborative playlist.')
    return
  }

  if (privatizedIds.length < playables.value.length) {
    toastWarning('Some songs cannot be marked as private as they’re part of a collaborative playlist.')
    return
  }

  toastSuccess(`Marked ${pluralize(playables.value, 'song')} as private.`)
})

const visibilityActions = computed(() => {
  if (contentType.value !== 'songs' || !allowEdit.value) {
    return []
  }

  if (!isPlus.value) {
    return []
  }

  const visibilities = Array.from(new Set((playables.value as Song[]).map(song => song.is_public
    ? 'public'
    : 'private',
  )))

  if (visibilities.length === 2) {
    return [
      {
        label: 'Unmark as Private',
        handler: makePublic,
      },
      {
        label: 'Mark as Private',
        handler: makePrivate,
      },
    ]
  }

  return visibilities[0] === 'public'
    ? [{ label: 'Mark as Private', handler: makePrivate }]
    : [{ label: 'Unmark as Private', handler: makePublic }]
})

const canBeRemovedFromPlaylist = computed(() => {
  if (!isCurrentScreen('Playlist')) {
    return false
  }
  const playlist = playlistStore.byId(getRouteParam('id')!)
  return playlist && !playlist.is_smart
})

const isQueueScreen = computed(() => isCurrentScreen('Queue'))
const isFavoritesScreen = computed(() => isCurrentScreen('Favorites'))

const doPlayback = () => trigger(async () => {
  if (!playables.value.length) {
    return
  }

  switch (playables.value[0].playback_state) {
    case 'Playing':
      playback().pause()
      break

    case 'Paused':
      await playback().resume()
      break

    default:
      await playback().play(playables.value[0])
      break
  }
})

const openEditForm = () => trigger(() =>
  playables.value.length
  && contentType.value === 'songs'
  && eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', playables.value as Song[]),
)

const viewAlbum = (song: Song) => trigger(() => go(url('albums.show', { id: song.album_id })))
const viewArtist = (song: Song) => trigger(() => go(url('artists.show', { id: song.artist_id })))
const viewPodcast = (episode: Episode) => trigger(() => go(url('podcasts.show', { id: episode.podcast_id })))
const viewEpisode = (episode: Episode) => trigger(() => go(url('episodes.show', { id: episode.id })))
const visitEpisodeWebpage = (episode: Episode) => trigger(() => window.open(episode.episode_link!, '_blank'))
const download = () => trigger(() => downloadService.fromPlayables(playables.value))

const removePlayablesFromPlaylist = () => trigger(async () => {
  const playlist = playlistStore.byId(getRouteParam('id')!)
  if (!playlist) {
    return
  }

  await removeFromPlaylist(playlist, playables.value)
})

const copyUrl = () => trigger(async () => {
  await copyText(playableStore.getShareableUrl(playables.value[0]))
  toastSuccess('URL copied to clipboard.')
})

const showEmbedModal = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_EMBED_FORM', playables.value[0]))

const deleteFromFilesystem = () => trigger(async () => {
  if (await showConfirmDialog('Delete selected playable(s) from the filesystem? This action is NOT reversible!')) {
    await playableStore.deleteSongsFromFilesystem(playables.value as Song[])
    toastSuccess(`Deleted ${pluralize(playables.value, 'song')} from the filesystem.`)
    eventBus.emit('SONGS_DELETED', playables.value as Song[])
  }
})

eventBus.on('PLAYABLE_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _songs) => {
  playables.value = arrayify(_songs)
  await open(pageY, pageX)
})
</script>
