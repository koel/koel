<template>
  <base-context-menu extra-class="song-menu" ref="base" data-testid="song-context-menu">
    <template v-show="onlyOneSongSelected">
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
    <li class="download" v-if="sharedState.allowDownload" @click="download">Download</li>
    <li
      class="copy-url"
      v-if="copyable && onlyOneSongSelected"
      @click="copyUrl"
    >
      Copy Shareable URL
    </li>
  </base-context-menu>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { BaseContextMenu } from 'koel/types/ui'
import { eventBus, isClipboardSupported, copyText } from '@/utils'
import { sharedStore, songStore, queueStore, userStore, playlistStore } from '@/stores'
import { playback, download } from '@/services'
import router from '@/router'
import songMenuMethods from '@/mixins/song-menu-methods.ts'

export default mixins(songMenuMethods).extend({
  components: {
    BaseContextMenu: () => import('@/components/ui/context-menu.vue')
  },

  data: () => ({
    playlistState: playlistStore.state,
    sharedState: sharedStore.state,
    copyable: isClipboardSupported,
    userState: userStore.state
  }),

  computed: {
    onlyOneSongSelected (): boolean {
      return this.songs.length === 1
    },

    firstSongPlaying (): boolean {
      return this.songs[0] ? this.songs[0].playbackState === 'Playing' : false
    },

    normalPlaylists (): Playlist[] {
      return this.playlistState.playlists.filter(playlist => !playlist.is_smart)
    },

    isAdmin (): boolean {
      return this.userState.current.is_admin
    }
  },

  methods: {
    open (top: number, left: number): void {
      if (!this.songs.length) {
        return
      }

      (this.$refs.base as BaseContextMenu).open(top, left)
    },

    close (): void {
      (this.$refs.base as BaseContextMenu).close()
    },

    doPlayback (): void {
      switch (this.songs[0].playbackState) {
        case 'Playing':
          playback.pause()
          break

        case 'Paused':
          playback.resume()
          break

        default:
          queueStore.contains(this.songs[0]) || queueStore.queueAfterCurrent(this.songs[0])
          playback.play(this.songs[0])
          break
      }

      this.close()
    },

    openEditForm (): void {
      if (this.songs.length) {
        eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', this.songs)
      }

      this.close()
    },

    viewAlbumDetails (album: Album): void {
      router.go(`album/${album.id}`)
      this.close()
    },

    viewArtistDetails (artist: Artist): void {
      router.go(`artist/${artist.id}`)
      this.close()
    },

    download (): void {
      download.fromSongs(this.songs)
      this.close()
    },

    copyUrl (): void {
      copyText(songStore.getShareableUrl(this.songs[0]))
      this.close()
    }
  }
})
</script>
