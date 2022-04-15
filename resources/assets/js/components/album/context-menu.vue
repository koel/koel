<template>
  <base-context-menu extra-class="album-menu" ref="base" data-testid="album-context-menu">
    <template v-if="album">
      <li data-test="play" @click="play">Play All</li>
      <li data-test="shuffle" @click="shuffle">Shuffle All</li>
      <li class="separator"></li>
      <li data-test="view-album" @click="viewAlbumDetails" v-if="isStandardAlbum">Go to Album</li>
      <li data-test="view-artist" @click="viewArtistDetails" v-if="isStandardArtist">Go to Artist</li>
      <template v-if="isStandardAlbum && sharedState.allowDownload">
        <li class="separator"></li>
        <li data-test="download" @click="download" >Download</li>
      </template>
    </template>
  </base-context-menu>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { BaseContextMenu } from 'koel/types/ui'
import { albumStore, artistStore, sharedStore } from '@/stores'
import { download, playback } from '@/services'
import router from '@/router'

export default Vue.extend({
  components: {
    BaseContextMenu: () => import('@/components/ui/context-menu.vue')
  },

  props: {
    album: {
      type: Object
    } as PropOptions<Album>
  },

  data: () => ({
    sharedState: sharedStore.state
  }),

  computed: {
    isStandardAlbum (): boolean {
      return !albumStore.isUnknownAlbum(this.album)
    },

    isStandardArtist (): boolean {
      return !artistStore.isUnknownArtist(this.album.artist) && !artistStore.isVariousArtists(this.album.artist)
    }
  },

  methods: {
    open (top: number, left: number): void {
      (this.$refs.base as BaseContextMenu).open(top, left)
    },

    play (): void {
      playback.playAllInAlbum(this.album)
    },

    shuffle (): void {
      playback.playAllInAlbum(this.album, true /* shuffled */)
    },

    viewAlbumDetails (): void {
      router.go(`album/${this.album.id}`)
      this.close()
    },

    viewArtistDetails (): void {
      router.go(`artist/${this.album.artist.id}`)
      this.close()
    },

    download (): void {
      download.fromAlbum(this.album)
      this.close()
    },

    close (): void {
      (this.$refs.base as BaseContextMenu).close()
    }
  }
})
</script>
