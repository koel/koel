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
          <li class="separator"/>
          <li @click="addSongsToFavorite">Favorites</li>
        </template>
        <li v-if="normalPlaylists.length" class="separator"/>
        <li v-for="p in normalPlaylists" :key="p.id" @click="addSongsToExistingPlaylist(p)">{{ p.name }}</li>
      </ul>
    </li>

    <template v-if="isQueueScreen">
      <li class="separator"/>
      <li @click="removeFromQueue">Remove from Queue</li>
      <li class="separator"/>
    </template>

    <template v-if="isFavoritesScreen">
      <li class="separator"/>
      <li @click="removeFromFavorites">Remove from Favorites</li>
      <li class="separator"/>
    </template>

    <li v-if="isAdmin" @click="openEditForm">Edit</li>
    <li v-if="allowDownload" @click="download">Download</li>
    <li v-if="onlyOneSongSelected" @click="copyUrl">Copy Shareable URL</li>

    <template v-if="canBeRemovedFromPlaylist">
      <li class="separator"/>
      <li @click="removeFromPlaylist">Remove from Playlist</li>
    </template>

    <template v-if="isAdmin">
      <li class="separator"/>
      <li @click="deleteFromFilesystem">Delete from Filesystem</li>
    </template>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { arrayify, copyText, eventBus, pluralize } from '@/utils'
import { commonStore, favoriteStore, playlistStore, queueStore, songStore, userStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import {
  useAuthorization,
  useContextMenu,
  useDialogBox,
  useMessageToaster,
  usePlaylistManagement,
  useRouter,
  useSongMenuMethods
} from '@/composables'

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, getRouteParam, isCurrentScreen } = useRouter()
const { isAdmin } = useAuthorization()
const { context, base, ContextMenuBase, open, close, trigger } = useContextMenu()
const { removeSongsFromPlaylist } = usePlaylistManagement()

const songs = ref<Song[]>([])

const {
  queueSongsAfterCurrent,
  queueSongsToBottom,
  queueSongsToTop,
  addSongsToFavorite,
  addSongsToExistingPlaylist
} = useSongMenuMethods(songs, close)

const playlists = toRef(playlistStore.state, 'playlists')
const allowDownload = toRef(commonStore.state, 'allow_download')
const user = toRef(userStore.state, 'current')
const queue = toRef(queueStore.state, 'songs')
const currentSong = toRef(queueStore, 'current')

const onlyOneSongSelected = computed(() => songs.value.length === 1)
const firstSongPlaying = computed(() => songs.value.length ? songs.value[0].playback_state === 'Playing' : false)
const normalPlaylists = computed(() => playlists.value.filter(playlist => !playlist.is_smart))

const canBeRemovedFromPlaylist = computed(() => {
  if (!isCurrentScreen('Playlist')) return false
  const playlist = playlistStore.byId(parseInt(getRouteParam('id')!))
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
  const playlist = playlistStore.byId(parseInt(getRouteParam('id')!))
  if (!playlist) return

  await removeSongsFromPlaylist(playlist, songs.value)
})

const removeFromQueue = () => trigger(() => queueStore.unqueue(songs.value))
const removeFromFavorites = () => trigger(() => favoriteStore.unlike(songs.value))

const copyUrl = () => trigger(() => {
  copyText(songStore.getShareableUrl(songs.value[0]))
  toastSuccess('URL copied to clipboard.')
})

const deleteFromFilesystem = () => trigger(async () => {
  if (await showConfirmDialog('Delete selected song(s) from the filesystem? This action is NOT reversible!')) {
    await songStore.deleteFromFilesystem(songs.value)
    toastSuccess(`Deleted ${pluralize(songs.value, 'song')} from the filesystem.`)
    eventBus.emit('SONGS_DELETED', songs.value)
  }
})

eventBus.on('SONG_CONTEXT_MENU_REQUESTED', async (e, _songs) => {
  songs.value = arrayify(_songs)
  await open(e.pageY, e.pageX, { songs: songs.value })
})
</script>
