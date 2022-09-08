<template>
  <ContextMenuBase ref="base" data-testid="song-context-menu" extra-class="song-menu">
    <template v-if="onlyOneSongSelected">
      <li class="playback" @click.stop.prevent="doPlayback">
        <span v-if="firstSongPlaying">Pause</span>
        <span v-else>Play</span>
      </li>
      <li class="go-to-album" @click="viewAlbumDetails(songs[0].album_id)">Go to Album</li>
      <li class="go-to-artist" @click="viewArtistDetails(songs[0].artist_id)">Go to Artist</li>
    </template>
    <li class="has-sub">
      Add To
      <ul class="menu submenu menu-add-to">
        <template v-if="queue.length">
          <li v-if="currentSong" class="after-current" @click="queueSongsAfterCurrent">After Current Song</li>
          <li class="bottom-queue" @click="queueSongsToBottom">Bottom of Queue</li>
          <li class="top-queue" @click="queueSongsToTop">Top of Queue</li>
        </template>
        <li v-else @click="queueSongsToBottom">Queue</li>
        <li class="separator"></li>
        <li class="favorite" @click="addSongsToFavorite">Favorites</li>
        <li class="separator" v-if="normalPlaylists.length"></li>
        <li
          class="playlist"
          v-for="p in normalPlaylists"
          :key="p.id"
          @click="addSongsToExistingPlaylist(p)"
        >{{ p.name }}
        </li>
      </ul>
    </li>
    <li class="open-edit-form" v-if="isAdmin" @click="openEditForm">Edit</li>
    <li class="download" v-if="allowDownload" @click="download">Download</li>
    <li
      class="copy-url"
      v-if="onlyOneSongSelected"
      @click="copyUrl"
    >
      Copy Shareable URL
    </li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { arrayify, copyText, eventBus, requireInjection } from '@/utils'
import { commonStore, playlistStore, queueStore, songStore, userStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import router from '@/router'
import { useAuthorization, useContextMenu, useSongMenuMethods } from '@/composables'
import { MessageToasterKey } from '@/symbols'

const { context, base, ContextMenuBase, open, close, trigger } = useContextMenu()

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

eventBus.on('SONG_CONTEXT_MENU_REQUESTED', async (e: MouseEvent, _songs: Song | Song[]) => {
  songs.value = arrayify(_songs)
  await open(e.pageY, e.pageX, { songs: songs.value })
})
</script>
