<template>
  <ContextMenuBase extra-class="song-menu" ref="base" data-testid="song-context-menu">
    <template v-if="onlyOneSongSelected">
      <li class="playback" @click.stop.prevent="doPlayback">
        <span v-if="firstSongPlaying">Pause</span>
        <span v-else>Play</span>
      </li>
      <li class="go-to-album" @click="viewAlbumDetails(songs[0].album)">Go to Album</li>
      <li class="go-to-artist" @click="viewArtistDetails(songs[0].artist)">Go to Artist</li>
    </template>
    <li class="has-sub">
      Add To
      <ul class="menu submenu menu-add-to">
        <li class="after-current" @click="queueSongsAfterCurrent">After Current Song</li>
        <li class="bottom-queue" @click="queueSongsToBottom">Bottom of Queue</li>
        <li class="top-queue" @click="queueSongsToTop">Top of Queue</li>
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
      v-if="copyable && onlyOneSongSelected"
      @click="copyUrl"
    >
      Copy Shareable URL
    </li>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, Ref, toRef } from 'vue'
import { alerts, copyText, eventBus, isClipboardSupported as copyable } from '@/utils'
import { commonStore, playlistStore, queueStore, songStore, userStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import router from '@/router'
import { useAuthorization, useContextMenu, useSongMenuMethods } from '@/composables'

const {
  context,
  base,
  ContextMenuBase,
  open,
  close
} = useContextMenu()

const songs = toRef(context, 'songs') as Ref<Song[]>

const {
  queueSongsAfterCurrent,
  queueSongsToBottom,
  queueSongsToTop,
  addSongsToFavorite,
  addSongsToExistingPlaylist
} = useSongMenuMethods(songs, close)

const playlists = toRef(playlistStore.state, 'playlists')
const allowDownload = toRef(commonStore.state, 'allowDownload')
const user = toRef(userStore.state, 'current')

const onlyOneSongSelected = computed(() => songs.value.length === 1)
const firstSongPlaying = computed(() => songs.value.length ? songs.value[0].playbackState === 'Playing' : false)
const normalPlaylists = computed(() => playlists.value.filter(playlist => !playlist.is_smart))
const { isAdmin } = useAuthorization()

const doPlayback = () => {
  if (!songs.value.length) return

  switch (songs.value[0].playbackState) {
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

  close()
}

const openEditForm = () => {
  songs.value.length && eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', songs.value)
  close()
}

const viewAlbumDetails = (album: Album) => {
  router.go(`album/${album.id}`)
  close()
}

const viewArtistDetails = (artist: Artist) => {
  router.go(`artist/${artist.id}`)
  close()
}

const download = () => {
  downloadService.fromSongs(songs.value)
  close()
}

const copyUrl = () => {
  copyText(songStore.getShareableUrl(songs.value[0]))
  alerts.success('URL copied to clipboard.')
  close()
}

defineExpose({ open, close })
</script>
