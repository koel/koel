<template>
  <ContextMenuBase ref="base" data-testid="song-context-menu" extra-class="song-menu">
    <template v-if="onlyOneSongSelected">
      <li @click.stop.prevent="doPlayback">
        <span v-if="firstSongPlaying">Pause</span>
        <span v-else>Play</span>
      </li>
      <li @click="viewAlbumDetails(songs[0].album_id)">Go to Album</li>
      <li @click="viewArtistDetails(songs[0].artist_id)">Go to Artist</li>
    </template>
    <li class="has-sub">
      Add To
      <ul class="menu submenu menu-add-to">
        <template v-if="queue.length">
          <li v-if="currentSong" @click="queueSongsAfterCurrent">After Current Song</li>
          <li @click="queueSongsToBottom">Bottom of Queue</li>
          <li @click="queueSongsToTop">Top of Queue</li>
        </template>
        <li v-else @click="queueSongsToBottom">Queue</li>
        <template v-if="!isFavoritesScreen">
          <li class="separator" />
          <li @click="addSongsToFavorite">Favorites</li>
        </template>
        <li v-if="normalPlaylists.length" class="separator" />
        <template class="d-block">
          <ul v-if="normalPlaylists.length" v-koel-overflow-fade class="playlists">
            <li v-for="p in normalPlaylists" :key="p.id" @click="addSongsToExistingPlaylist(p)">{{ p.name }}</li>
          </ul>
        </template>
        <li class="separator" />
        <li @click="addSongsToNewPlaylist">New Playlist…</li>
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

    <li v-if="canModify" @click="openEditForm">Edit…</li>
    <li v-if="allowsDownload" @click="download">Download</li>
    <li v-if="onlyOneSongSelected && canBeShared" @click="copyUrl">Copy Shareable URL</li>

    <template v-if="canBeRemovedFromPlaylist">
      <li class="separator" />
      <li @click="removeFromPlaylist">Remove from Playlist</li>
    </template>

    <template v-if="canModify">
      <li class="separator" />
      <li @click="deleteFromFilesystem">Delete from Filesystem</li>
    </template>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { arrayify, copyText, eventBus, pluralize } from '@/utils'
import { commonStore, favoriteStore, playlistStore, queueStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import {
  useAuthorization,
  useContextMenu,
  useDialogBox,
  useKoelPlus,
  useMessageToaster,
  usePlaylistManagement,
  useRouter,
  useSongMenuMethods
} from '@/composables'

const { toastSuccess, toastError, toastWarning } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, getRouteParam, isCurrentScreen } = useRouter()
const { isAdmin, currentUser } = useAuthorization()
const { base, ContextMenuBase, open, close, trigger } = useContextMenu()
const { removeSongsFromPlaylist } = usePlaylistManagement()
const { isPlus } = useKoelPlus()

const songs = ref<Song[]>([])

const {
  queueSongsAfterCurrent,
  queueSongsToBottom,
  queueSongsToTop,
  addSongsToFavorite,
  addSongsToExistingPlaylist,
  addSongsToNewPlaylist
} = useSongMenuMethods(songs, close)

const playlists = toRef(playlistStore.state, 'playlists')
const allowsDownload = toRef(commonStore.state, 'allows_download')
const queue = toRef(queueStore.state, 'songs')
const currentSong = toRef(queueStore, 'current')

const canModify = computed(() => {
  if (isPlus.value) return songs.value.every(({ owner_id }) => owner_id === currentUser.value?.id)
  return isAdmin.value
})

const onlyOneSongSelected = computed(() => songs.value.length === 1)
const firstSongPlaying = computed(() => songs.value.length ? songs.value[0].playback_state === 'Playing' : false)
const normalPlaylists = computed(() => playlists.value.filter(({ is_smart }) => !is_smart))

const makePublic = () => trigger(async () => {
  await songStore.publicize(songs.value)
  toastSuccess(`Unmarked ${pluralize(songs.value, 'song')} as private.`)
})

const makePrivate = () => trigger(async () => {
  const privatizedIds = await songStore.privatize(songs.value)

  if (!privatizedIds.length) {
    toastError('Songs cannot be marked as private if they’part of a collaborative playlist.')
    return
  }

  if (privatizedIds.length < songs.value.length) {
    toastWarning('Some songs cannot be marked as private as they’re part of a collaborative playlist.')
    return
  }

  toastSuccess(`Marked ${pluralize(songs.value, 'song')} as private.`)
})

const canBeShared = computed(() => !isPlus.value || songs.value[0].is_public)

const visibilityActions = computed(() => {
  if (!isPlus.value) return []

  // If some songs don't belong to the current user, no actions are available.
  if (songs.value.some(({ owner_id }) => owner_id !== currentUser.value?.id)) return []

  const visibilities = Array.from(new Set(songs.value.map(song => song.is_public)))

  if (visibilities.length === 2) {
    return [
      {
        label: 'Unmark as Private',
        handler: makePublic
      },
      {
        label: 'Mark as Private',
        handler: makePrivate
      }
    ]
  }

  return visibilities[0]
    ? [{ label: 'Mark as Private', handler: makePrivate }]
    : [{ label: 'Unmark as Private', handler: makePublic }]
})

const canBeRemovedFromPlaylist = computed(() => {
  if (!isCurrentScreen('Playlist')) return false
  const playlist = playlistStore.byId(getRouteParam('id')!)
  return playlist && !playlist.is_smart
})

const isQueueScreen = computed(() => isCurrentScreen('Queue'))
const isFavoritesScreen = computed(() => isCurrentScreen('Favorites'))

const doPlayback = () => trigger(() => {
  if (!songs.value.length) return

  switch (songs.value[0].playback_state) {
    case 'Playing':
      playbackService.pause()
      break

    case 'Paused':
      playbackService.resume()
      break

    default:
      queueStore.queueIfNotQueued(songs.value[0])
      playbackService.play(songs.value[0])
      break
  }
})

const openEditForm = () => trigger(() => songs.value.length && eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', songs.value))
const viewAlbumDetails = (albumId: number) => trigger(() => go(`album/${albumId}`))
const viewArtistDetails = (artistId: number) => trigger(() => go(`artist/${artistId}`))
const download = () => trigger(() => downloadService.fromSongs(songs.value))

const removeFromPlaylist = () => trigger(async () => {
  const playlist = playlistStore.byId(getRouteParam('id')!)
  if (!playlist) return

  await removeSongsFromPlaylist(playlist, songs.value)
})

const removeFromQueue = () => trigger(() => queueStore.unqueue(songs.value))
const removeFromFavorites = () => trigger(() => favoriteStore.unlike(songs.value))

const copyUrl = () => trigger(async () => {
  await copyText(songStore.getShareableUrl(songs.value[0]))
  toastSuccess('URL copied to clipboard.')
})

const deleteFromFilesystem = () => trigger(async () => {
  if (await showConfirmDialog('Delete selected song(s) from the filesystem? This action is NOT reversible!')) {
    await songStore.deleteFromFilesystem(songs.value)
    toastSuccess(`Deleted ${pluralize(songs.value, 'song')} from the filesystem.`)
    eventBus.emit('SONGS_DELETED', songs.value)
  }
})

eventBus.on('SONG_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _songs) => {
  songs.value = arrayify(_songs)
  await open(pageY, pageX)
})
</script>

<style lang="scss" scoped>
ul.playlists {
  position: relative;
  max-height: 192px;
  overflow-y: auto;
}
</style>
