<template>
  <base-context-menu extra-class="artist-menu" ref="base" data-testid="artist-context-menu">
    <template v-if="artist">
      <li data-test="play" @click="play">Play All</li>
      <li data-test="shuffle" @click="shuffle">Shuffle All</li>
      <template v-if="isStandardArtist">
        <li class="separator"></li>
        <li data-test="view-artist" @click="viewArtistDetails">Go to Artist</li>
      </template>
      <template v-if="isStandardArtist && sharedState.allowDownload">
        <li class="separator"></li>
        <li data-test="download" @click="download">Download</li>
      </template>
    </template>
  </base-context-menu>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { BaseContextMenu } from 'koel/types/ui'
import { artistStore, sharedStore } from '@/stores'
import { download, playback } from '@/services'
import router from '@/router'

export default Vue.extend({
  components: {
    BaseContextMenu: () => import('@/components/ui/context-menu.vue')
  },

  props: {
    artist: {
      type: Object
    } as PropOptions<Artist>
  },

  data: () => ({
    sharedState: sharedStore.state
  }),

  computed: {
    isStandardArtist (): boolean {
      return !artistStore.isUnknownArtist(this.artist) && !artistStore.isVariousArtists(this.artist)
    }
  },

  methods: {
    open (top: number, left: number): void {
      (this.$refs.base as BaseContextMenu).open(top, left)
    },

    play (): void {
      playback.playAllByArtist(this.artist)
    },

    shuffle (): void {
      playback.playAllByArtist(this.artist, true /* shuffled */)
    },

    viewArtistDetails (): void {
      router.go(`artist/${this.artist.id}`)
      this.close()
    },

    download (): void {
      download.fromArtist(this.artist)
      this.close()
    },

    close (): void {
      (this.$refs.base as BaseContextMenu).close()
    }
  }
})
</script>
