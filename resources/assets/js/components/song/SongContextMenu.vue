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
        <li class="separator"/>
        <li @click="addSongsToFavorite">Favorites</li>
        <li class="separator" v-if="normalPlaylists.length"/>
        <li v-for="p in normalPlaylists" :key="p.id" @click="addSongsToExistingPlaylist(p)">{{ p.name }}</li>
      </ul>
    </li>
    <li v-if="isAdmin" @click="openEditForm">Edit</li>
    <li v-if="allowDownload" @click="download">Download</li>
    <li v-if="onlyOneSongSelected" @click="copyUrl">Copy Shareable URL</li>
    <li class="separator"/>
    <li v-if="isAdmin" @click="deleteFromFilesystem">Delete from Filesystem</li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { arrayify, copyText, eventBus, pluralize, requireInjection } from '@/utils'
import { commonStore, playlistStore, queueStore, songStore, userStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import router from '@/router'
import { useAuthorization, useContextMenu, useSongMenuMethods } from '@/composables'
import { DialogBoxKey, MessageToasterKey } from '@/symbols'

const { context, base, ContextMenuBase, open, close, trigger } = useContextMenu()

const dialogBox = requireInjection(DialogBoxKey)
const toaster = requireInjection(MessageToasterKey)
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
const currentSong = queueStore.current

const onlyOneSongSelected = computed(() => songs.value.length === 1)
const firstSongPlaying = computed(() => songs.value.length ? songs.value[0].playback_state === 'Playing' : false)
const normalPlaylists = computed(() => playlists.value.filter(playlist => !playlist.is_smart))

const { isAdmin } = useAuthorization()

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
const viewAlbumDetails = (albumId: number) => trigger(() => router.go(`album/${albumId}`))
const viewArtistDetails = (artistId: number) => trigger(() => router.go(`artist/${artistId}`))
const download = () => trigger(() => downloadService.fromSongs(songs.value))

const copyUrl = () => trigger(() => {
  copyText(songStore.getShareableUrl(songs.value[0]))
  toaster.value.success('URL copied to clipboard.')
})

const deleteFromFilesystem = () => trigger(async () => {
  const confirmed = await dialogBox.value.confirm(
    'Delete selected song(s) from the filesystem? This action is NOT reversible!'
  )

  if (confirmed) {
    await songStore.deleteFromFilesystem(songs.value)
    toaster.value.success(`Deleted ${pluralize(songs.value, 'song')} from the filesystem.`)
    eventBus.emit('SONGS_DELETED', songs.value)
  }
})

eventBus.on('SONG_CONTEXT_MENU_REQUESTED', async (e: MouseEvent, _songs: Song | Song[]) => {
  songs.value = arrayify(_songs)
  await open(e.pageY, e.pageX, { songs: songs.value })
})
</script>
